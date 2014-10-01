<?php

return [
    'debug' => true,
    'log.path' => ROOT.'/app.log',
    'log.level' => \Slim\Log::DEBUG,
    'db.dsn' => 'mysql:host=localhost;dbname=predict',
    'db.username' => 'root',
    'db.password' => '',
];
