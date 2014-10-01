<?php

define('ROOT', __DIR__);
define('ENV_MODE', isset($_ENV['USER']) && $_ENV['USER'] === 'bae' ? 'production' : 'development');

spl_autoload_register(function ($class) {
    if (strpos($class, 'core\\') === 0) {
        require __DIR__."/".str_replace('\\', '/', $class).".php";
    }
});
require 'vendor/autoload.php';
require __DIR__.'/model.php';

$config_root = __DIR__.'/configs';
if (!is_dir($config_root)) {
    throw new Exception("no dir ".$config_root, 1);
}
$config = require "$config_root/all.php";
$app = new \Slim\Slim($config);
$app->config('mode', ENV_MODE);
$app->config(require $config_root.'/'.ENV_MODE.'.php');
$log_resourse = fopen($app->config('log.path'), 'a');
$app->config('log.writer', new \Slim\LogWriter($log_resourse));

$db = new \Pdo($app->config('db.dsn'), $app->config('db.username'), $app->config('db.password'), $app->config('db.driver_options'));

session_cache_limiter(false);
session_start();

if ($id = get_user_id()) {
    $app->view->setData('current_user', get_user($id));
}
$app->get('/', function () use($app) {
    $predict_list = get_predict_list();
    $app->render('index', compact('predict_list'));
});

$app->group('/predict', function () use ($app) {
    $app->get('/:id', function ($id) use ($app) {
        $predict = get_predict($id);
        $attitude_list = get_attitude_list($id);
        $app->render('predict/show', compact('predict', 'attitude_list'));
    })->conditions(array('id' => '\d+'));

    $app->get('/create', function () use ($app) {
        $app->render('predict/create');
    });
    $app->post('/create', function () use ($app) {
        $request = $app->request;
        list($id, $err_msg) = create_predict($request);
        if ($id) {
            $app->redirect('/predict/'.$id);
        } else {
            $app->flash('err_msg', $err_msg);
            $app->flash('request', $request->params());
            $app->redirect('/predict/create');
        }
    });

    $app->post('/:id/attitude', function ($id) use ($app) {
        global $db;
        $request = $app->request;
        $is_defend = $request->post('is_defend');
        $points = $request->post('points');
        $db->beginTransaction();
        list($_, $err_msg) = create_attitude($id, $is_defend, $points);
        if ($err_msg) {
            $db->rollback();
            echo json_encode(['code' => 1, 'message' => $err_msg]);
        } else {
            $db->commit();
            echo json_encode(['code' => 0, 'message' => 'ok']);
        }
    });
});

$app->get('/login', function () use ($app) {
    $app->render('login', compact('username', 'password', 'msg'));
});
$app->post('/login', function () use ($app) {
    $request = $app->request;
    $username = $request->post('username');
    $password = $request->post('password');
    if ($username && $password && $user = get_user_by_name($username, $password)) {
        $_SESSION['user_id'] = $user['id'];
        $app->redirect('/');
    }
    $message = 'username or password not correct';
    if (empty($username)) {
        $message = 'empty username';
    }
    if (empty($password)) {
        $message = 'empty password';
    }
    $app->flash('username', $username);
    $app->flash('password', $password);
    $app->flash('message', $message);
    $app->redirect('/login');
});

$app->get('/logout', function () use ($app) {
    $_SESSION['user_id'] = 0;
    $app->redirect('/');
});

$app->error(function (\Exception $e) use ($app) {
    $app->log->error('{$e->getCode()} {$e->getMessage()}');
    $app->render('error');
});

$app->notFound(function () use ($app) {
    $app->render('404');
});

$app->run();
