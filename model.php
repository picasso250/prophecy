<?php

function exec_sql($sql, $values = array())
{
    global $db;
    $stmt = $db->prepare($sql);
    if (!$stmt->execute($values)) {
        throw new Exception("db error: ".implode(',', $db->errorInfo()).' of sql '.$sql, 1);
    }
    return $stmt;
}

function insert($table, $values)
{
    global $db;
    $keys = array_keys($values);
    $keystr = implode(',', array_map(function($e){return "`$e`";}, $keys));
    $place_holder = implode(',', array_map(function($e){return ":$e";}, $keys));
    $sql = "INSERT INTO `$table` ($keystr) VALUES ($place_holder)";
    exec_sql($sql, $values);
    return $db->lastInsertId();
}

function get_attitude($predict_id, $user_id)
{
    $sql = 'SELECT is_defend FROM user_predict WHERE predict_id=? and user_id=?';
    $stmt = exec_sql($sql, [$predict_id, $user_id]);
    return $stmt->fetchColumn();
}
function get_predict_list()
{
    $stmt = exec_sql('SELECT * FROM predict ORDER BY id desc limit 100');
    $predict_list = $stmt->fetchAll(Pdo::FETCH_ASSOC);
    $user_id = get_user_id();
    foreach ($predict_list as &$predict) {
        $predict_id = $predict['id'];
        $predict['my_attitude'] = $user_id ? get_attitude($predict_id, $user_id) : false;
        $predict['bet_pts'] = 100;
        $defend = get_defend($predict_id);
        $predict['defend'] = $defend;
        $predict['total_points'] = $defend[0]['total'] + $defend[1]['total'];
    }
    return $predict_list;
}

function get_defend($predict_id)
{
    $sql = 'SELECT is_defend, count(*) as `count`, sum(points) as `total` FROM user_predict WHERE predict_id=? GROUP BY is_defend ORDER BY is_defend ASC';
    $stmt = exec_sql($sql, [$predict_id]);
    $rows = $stmt->fetchAll(Pdo::FETCH_ASSOC);
    $default = ['count' => 0, 'total' => 0];
    $ret = [$default, $default];
    foreach ($rows as $row) {
        $ret[$row['is_defend']] = $row;
    }
    return $ret;
}

function get_predict($id)
{
    global $app;
    $stmt = exec_sql('SELECT p.*, u.name FROM predict AS p JOIN user AS u ON p.creator=u.id WHERE p.id=? limit 1', [$id]);
    $predict = $stmt->fetch(Pdo::FETCH_ASSOC);
    if (empty($predict)) {
        $app->log->error("no predict {$id}");
        throw new Exception("no predict", 1);
    }
    var_dump($predict);
    $user_id = get_user_id();
    $predict['my_attitude'] = $user_id ? get_attitude($predict['id'], $user_id) : false;
    $predict['defend'] = $defend = get_defend($id);
    $predict['total_points'] = $defend[0]['total'] + $defend[1]['total'];
    return $predict;
}

function get_user_id()
{
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
}

function get_user($id)
{
    $stmt = exec_sql('SELECT * FROM user WHERE id=? limit 1', [$id]);
    return $stmt->fetch(Pdo::FETCH_ASSOC);
}

function create_predict($request)
{
    $creator = get_user_id();
    if (!$creator) {
        throw new Exception("you should login", 1);
    }
    $title = $request->post('title');
    if (empty($title)) {
        return [null, ['title' => 'title empty']];
    }
    if (strlen($title) > 250) {
        return [null, ['title' > 'title more than 250']];
    }
    $descript = $request->post('descript');
    if (empty($descript)) {
        return [null, ['descript' => 'description empty']];
    }
    if (strlen($descript) > 250) {
        return [null, ['descript' > 'description more than 250']];
    }
    $valid_date = $request->post('valid_date');
    if (empty($valid_date)) {
        return [null, ['valid_date' => 'date empty']];
    }
    if (!preg_match('/\d{2}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $valid_date)) {
        return [null, ['valid_date' => 'should be like 2014-03-01 16:40:32']];
    }
    $id = insert('predict', [
        'creator' => $creator,
        'title' => $request->post('title'),
        'descript' => $request->post('descript'),
        'valid_date' => $request->post('valid_date'),
    ]);
    return [$id, []];
}

function get_user_by_name($username, $password)
{
    $stmt = exec_sql('SELECT * FROM user WHERE name=? limit 1', [$username]);
    $user = $stmt->fetch(Pdo::FETCH_ASSOC);
    list($salt, $encrypt) = explode(':', $user['password']);
    if (sha1($salt.$password) === $encrypt) {
        return $user;
    }
    return false;
}

function create_attitude($predict_id, $is_defend, $points)
{
    $user_id = get_user_id();
    $user = get_user($user_id);
    if ($user['points'] < $points) {
        return [null, 'you have only $user[points] points, not enough'];
    }

    exec_sql('UPDATE user SET points=points-?', [$points]);

    $sql = 'SELECT user_id FROM user_predict WHERE predict_id=? and user_id=? LIMIT 1';
    $stmt = exec_sql($sql, [$predict_id, $user_id]);
    if ($stmt->fetchColumn()) {
        return [null, 'you have showed your attitude'];
    }

    $id = insert('user_predict', [
        'user_id' => $user_id,
        'predict_id' => $predict_id,
        'is_defend' => $is_defend,
        'points' => $points
    ]);
    return [$id, null];
}

function get_attitude_list($predict_id)
{
    $sql = 'SELECT u.id, u.name, up.is_defend, up.points FROM user_predict AS up JOIN user AS u ON up.user_id=u.id WHERE predict_id=?';
    $stmt = exec_sql($sql, [$predict_id]);
    return $stmt->fetchAll(Pdo::FETCH_ASSOC);
}

function determin_predict($predict_id, $result)
{
    exec_sql('UPDATE predict SET result=? WHERE predict_id=?', [$result, $predict_id]);
    $rows = get_attitude_list($predict_id);
    foreach ($rows as $e) {
        $user_id = $e['id'];
        $points = $e['points'];
        $points = $result ^ $e['is_defend'] ? -$points : $points;
        exec_sql('UPDATE user SET points=points+? WHERE user_id=?', [$points, $user_id]);
    }
}
