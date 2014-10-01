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

function get_predict_list()
{
    $stmt = exec_sql('SELECT * FROM predict ORDER BY id desc limit 100');
    return $stmt->fetchAll(Pdo::FETCH_ASSOC);
}

function get_predict($id)
{
    $stmt = exec_sql('SELECT p.*, u.name FROM predict AS p JOIN user AS u ON p.creator=u.id WHERE p.id=? limit 1', [$id]);
    return $stmt->fetch(Pdo::FETCH_ASSOC);
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

function create_attitude($request)
{
    $id = insert('user_predict', [
        'user_id' => get_user_id(),
        'predict_id' => $request->post('predict_id'),
        'is_defend' => $request->post('is_defend'),
        'reason' => $request->post('reason'),
    ]);
    return [$id, []];
}

function get_attitude_list($predict_id)
{
    $sql = 'SELECT u.id, u.name, up.is_defend FROM user_predict AS up JOIN user AS u ON up.user_id=u.id WHERE predict_id=?';
    $stmt = exec_sql($sql, [$predict_id]);
    return $stmt->fetchAll(Pdo::FETCH_ASSOC);
}
