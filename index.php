<?php

define('ROOT', __DIR__);
define('ENV_MODE', isset($_ENV['USER']) && $_ENV['USER'] === 'bae' ? 'production' : 'development');

require 'vendor/autoload.php';

$config_root = __DIR__.'/config';
if (!is_dir($config_root)) {
    throw new Exception("no dir ".$config_root, 1);
}
$config = 
    require "$config_root/main.php",
$app = new \Slim\Slim($config);

// Only invoked if mode is "production"
$app->configureMode('production', function () use ($app) {
    $app->config([
        'debug' => false,
        'log.writer' => new \Slim\LogWriter('/home/bae/log'),
        'log.level' => \Slim\Log::WARN
    ]);
});

// Only invoked if mode is "development"
$app->configureMode('development', function () use ($app) {
    $app->config([
        'debug' => true,
        'log.writer' => new \Slim\LogWriter(ROOT.'/app.log'),
        'log.level' => \Slim\Log::DEBUG
    ]);
});

$app->get('/', function () {
    echo "Hello, $name";
});

$app->group('/api', function () use ($app) {
    $app->get('/:id', function ($id) {
        $predict = get_predict($id);
        $app->render('predict/show', compact('predict'));
    })->conditions(array('id' => '\d+'));

    $create_func = function () use ($app) {
        $err_msg = false;
        $request = $app->request;
        if ($request->isPost()) {
            list($id, $err_msg) = create_predict($request);
            if ($id) {
                $app->redirect('/predict/'.$id)
            }
        }
        $app->render('predict/create', compact('err_msg'));
    };
    $app->get('/create', $create_func);
    $app->post('/create', $create_func);
});

$login_func = function () use ($app) {
    $username = '';
    $password = '';
    $msg = '';
    $request = $app->request;
    if ($request->isPost()) {
        $username = $request->post('username');
        $password = $request->post('password');
        if (check_user_name($username, $password)) {
            $app->redirect('/');
        } else {
            $msg = 'username or password not correct';
        }
    }
    $app->render('login', compact('username', 'password', 'msg'));
};
$app->get('/login', $login_func);
$app->post('/login', $login_func);

$app->run();
