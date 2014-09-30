<?php

namespace core;

class LogWriter
{

    public function write($msg)
    {
        return error_log("$msg\n", 3, $this->path);
    }
}
