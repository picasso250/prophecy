<?php

define('ROOT', __DIR__);
define('ENV_MODE', isset($_ENV['USER']) && $_ENV['USER'] === 'bae' ? 'production' : 'development');

spl_autoload_register(function ($class) {
    if (strpos($class, 'core\\') === 0) {
        require __DIR__."/".str_replace('\\', '/', $class).".php";
    }
});
require 'autoload.php';
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

\DB::setAdapter(new \adapter\Mysql($app->config('db.dsn'), $app->config('db.username'), $app->config('db.password'), $app->config('db.driver_options')));
$cache = $app->config('cache');

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
    $email = $request->post('email');
    $is_password = $request->post('is_password');
    $password = $request->post('password');
    $message = 'username or password not correct';
    try {
        if (empty($email)) {
            throw new Exception("empty email", 1);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("not valid email $email", 1);
        }
        if ($is_password) {
            if (empty($password)) {
                throw new Exception("empty password", 1);
            }
            if (get_user_by_email_password($email, $password)) {
                set_user_session($user['id']);
                $app->redirect('/');
            } else {
                throw new Exception("password not correct", 1);
            }
        } else {
            if (login_user($email)) {
                $app->redirect('/');
            } else {
                $app->flash('email', $email);
                $app->redirect('/login/send');
            }
        }
    } catch (Exception $e) {
        $app->flash('email', $email);
        $app->flash('password', $password);
        $app->flash('is_password', $is_password);
        $app->flash('message', $e->getMessage());
        $app->redirect('/login');
    }
});

$app->get('/login/confirm', function () use ($app, $cache) {
    $request = $app->request;
    $email = $request->get('email');
    $code = $request->get('code');
    $remember = $request->get('remember');
    if ($user = check_email_code($email, $code, $remember)) {
        set_user_session($user['id']);
        $app->redirect('/');
    } else {
        echo 'error';
    }
});

$app->get('/login/send', function () use ($app, $cache) {
    echo 'email send to '.$flash['email'];
});

$app->get('/logout', function () use ($app) {
    set_user_session(0);
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
