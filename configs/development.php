<?php

return [
    'debug' => true,
    'log.writer' => new \Slim\LogWriter(ROOT.'/app.log'),
    'log.level' => \Slim\Log::DEBUG
];
