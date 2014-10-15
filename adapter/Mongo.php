<?php

namespace adapter;

use MongoClient;

class Mongo
{
    public static function get_collection()
    {
        static $collection;
        if ($collection === null) {
            $m = new MongoClient(); // connect
            $collection = $m->zhihu;
        }
        return $collection;
    }

    public static function getTable($table)
    {
        $db = self::get_collection();
        $t = $db->{$table};
        switch ($table) {
            case 'answer':
                $t->ensureIndex(array('vote' => -1, 'q_id' => -1,));
                break;

            case 'question':
                $t->ensureIndex(array('id' => 1));
                break;

            case 'user':
                $t->ensureIndex(array('name' => -1));
                break;
            
            default:
                # code...
                break;
        }
        return $t;
    }

    public function update($table, $set, $where)
    {
        $t = self::getTable($table);
        $rs = $t->update($where, array('$set' => $set));
        if (!$rs['ok']) {
            echo basename(__FILE__).':'.__LINE__.' '.$rs['err']."\n";
        }
        return $rs;
    }

    public function upsert($table, $set, $where)
    {
        $t = self::getTable($table);
        $rs = $t->update($where, array('$set' => $set), array('upsert' => true));
        if (!$rs['ok']) {
            echo basename(__FILE__).':'.__LINE__.' '.$rs['err']."\n";
        }
        return $rs;
    }

    public function fields($fields)
    {
        $arr = array();
        foreach ($fields as $field) {
            $arr[$field] = true;
        }
        $this->cursor->fields($arr);
        return $this;
    }
    public function find($table, $where)
    {
        $t = self::getTable($table);
        $this->cursor = $t->find($where);
        return $this;
    }

    public function queryAll()
    {
        return $this->cursor;
    }

    /**
     * limit count
     */
    public function __call($name, $params)
    {
        return call_user_func_array(array($this->cursor, $name), $params);
    }

}
