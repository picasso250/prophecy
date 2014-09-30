<?php

function exec_sql($sql, $values = array())
{
    global $db;
    $stmt = $db->prepare($sql);
    if (!$stmt->execute($values)) {
        $throw new Exception("db error", 1, $db->errorInfo());
    }
    return $stmt;
}

function get_predict_list()
{
    $stmt = exec_sql('SELECT * FROM predict ORDER BY id desc limit 100');
    return $stmt->fetchAll(Pdo::FETCH_ASSOC);
}

function get_predict($id)
{
    $stmt = exec_sql('SELECT * FROM predict WHERE id=? limit 1', [$id]);
    return $stmt->fetchRow(Pdo::FETCH_ASSOC);
}

function get_user_id()
{
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
}

function create_predict($request)
{
    insert([
        'creator' => get_user_id(),
        'title' => $request->post('title'),
        'descript' => $request->post('descript'),
        'valid_date' => $request->post('valid_date'),
    ]);
    return [$db->lastInsertId(), []];
}

function get_user_by_name($username, $password)
{
    $stmt = exec_sql('SELECT * FROM user WHERE name=? limit 1', [$username, sha1($password)]);
    $user = $stmt->fetchRow(Pdo::FETCH_ASSOC);
    list($salt, $encrypt) = explode(':', $user['password']);
    if (sha1($salt.$password) === $encrypt) {
        return $user;
    }
    return false;
}

function create_attitude($request)
{
    insert([
        'user_id' => get_user_id(),
        'predict_id' => $request->post('predict_id'),
        'is_defend' => $request->post('is_defend'),
        'reason' => $request->post('reason'),
    ]);
    return [$db->lastInsertId(), []];
}

function get_attitude_list($predict_id)
{
    $sql = 'SELECT u.user_id, u.name, up.is_defend FROM user_predict AS up JOIN user AS u ON up.user_id=u.id WHERE predict_id=?';
    $stmt = exec_sql($sql, [$predict_id]);
    return $stmt->fetchAll(Pdo::FETCH_ASSOC);
}
