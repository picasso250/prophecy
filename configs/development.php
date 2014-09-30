<?php

return [
    'debug' => true,
    'log.writer' => new \Slim\LogWriter(ROOT.'/app.log'),
    'log.level' => \Slim\Log::DEBUG,
    'db.dsn' => 'mysql:host=localhost;dbname=predict',
    'db.username' => 'root',
    'db.password' => '',
];
