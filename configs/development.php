<?php

return [
    'debug' => true,
    'log.writer' => new \Slim\LogWriter(fopen(ROOT.'/app.log', 'a')),
    'log.level' => \Slim\Log::DEBUG,
    'db.dsn' => 'mysql:host=localhost;dbname=predict',
    'db.username' => 'root',
    'db.password' => '',
];
