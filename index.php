<?php
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

$app->get('/hello/:name', function ($name) {
    echo "Hello, $name";
});

$app->run();
