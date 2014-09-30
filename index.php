<?php

define('ROOT', __DIR__);
define('ENV_MODE', isset($_ENV['USER']) && $_ENV['USER'] === 'bae' ? 'production' : 'development');

require 'vendor/autoload.php';

$config_root = __DIR__.'/config';
if (!is_dir($config_root)) {
    throw new Exception("no dir ".$config_root, 1);
}
$config = array_merge(
    require "$config_root/config.php",
    require "$config_root/config.$env.php"
);
$app = new \Slim\Slim($config);

$app->get('/', function () {
    echo "Hello, $name";
});
$app->get('/predict/:id', function ($id) {
    echo "predict, $id";
});
$app->get('/predict/create', function () {
    echo "Hello, $name";
});
$app->post('/predict/create', function () {
    echo "Hello, $name";
});
$app->get('/login', function () {
    echo "Hello, $name";
});
$app->post('/login', function () {
    echo "Hello, $name";
});

$app->run();
