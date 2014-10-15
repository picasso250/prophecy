<?php

class DB
{
    public static $adapter;

    public static function setAdapter($adapter)
    {
        self::$adapter = $adapter;
    }

    public static function __callStatic($name, $args)
    {
        return call_user_func_array(array(self::$adapter, $name), $args);
    }
}
