<?php

$cache = new Memcache();
$cache->connect('nmg01-ssl-gxt-test-meta.nmg01.baidu.com', 8201);

return [
    'debug' => true,
    'log.path' => ROOT.'/app.log',
    'log.level' => \Slim\Log::DEBUG,
    'db.dsn' => 'mysql:host=localhost;dbname=predict',
    'db.username' => 'root',
    'db.password' => '',
    'cache' => $cache,
];
