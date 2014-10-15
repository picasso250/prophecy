<?php

namespace adapter;

use Pdo;
use Exception;

class Mysql
{
    private $limit;
    private $where;
    private $table;
    private $fields;

    public function __construct($dsn, $username, $password, $driver_options)
    {
        $this->pdo = new Pdo($dsn, $username, $password, $driver_options);
        $this->reset();
    }

    private function reset()
    {
        $this->limit = 1000;
        $this->where = [];
        $this->table = '';
        $this->fields = [];
    }

    public function execute($sql, $values = [])
    {
        $stmt = $this->pdo->prepare($sql);
        if (!$stmt->execute($values)) {
            print_r($stmt->errorInfo());
            slog(implode(',', $stmt->errorInfo()));
            throw new Exception("db error", 1);
        }
        return $stmt;
    }

    private static function keyEqualArray($kvs)
    {
        return array_map(function ($name) {
            return "`$name`=:$name";
        }, array_keys($kvs));
    }

    public function update($table, $set, $where)
    {
        $setStr = implode(',', self::keyEqualArray($set));
        $whereStr = implode(' AND ', self::keyEqualArray($where));
        $sql = "UPDATE `$table` SET $setStr WHERE $whereStr";
        $values = array_merge($set, $where);
        return $this->execute($sql, $values);
    }

    public function upsert($table, $set, $where)
    {
        $result = ['ok' => true];
        $whereStr = implode(',', self::keyEqualArray($where));
        $sql = "SELECT id FROM `$table` WHERE $whereStr LIMIT 1";
        $stmt = $this->execute($sql, $where);
        $id = $stmt->fetchColumn();
        $result['updatedExisting'] = !!$id;
        if ($id) {
            $this->update($table, $set, $where);
        } else {
            $this->insert($table, $set);
        }
        return $result;
    }

    public function insert($table, $set)
    {
        $setStr = implode(',', array_map(function($name){return "`$name`";}, array_keys($set)));
        $valuesStr = implode(',', array_map(function($name){return ":$name";}, array_keys($set)));
        $sql = "INSERT INTO `$table` ($setStr) VALUES ($valuesStr)";
        $this->execute($sql, $set);
        return $this->pdo->lastInsertId();
    }

    public function find($table, $where)
    {
        $this->table = $table;
        $this->where = $where;
        return $this;
    }

    public function queryAll()
    {
        $fields = implode(',', $this->fields);
        $whereStr = implode(' AND ', self::keyEqualArray($this->where));
        $sql = "SELECT $fields FROM `$this->table` WHERE $whereStr limit $this->limit";
        $stmt = $this->execute($sql, $this->where);
        $this->reset();
        return $stmt->fetchAll(Pdo::FETCH_ASSOC);
    }

    public function count()
    {
        $whereStr = implode(' AND ', self::keyEqualArray($this->where));
        $sql = "SELECT COUNT(*) FROM `$this->table` WHERE $whereStr limit 1";
        $stmt = $this->execute($sql, $this->where);
        $this->reset();
        return $stmt->fetchColumn();
    }

    /**
     * fields limit
     */
    public function __call($name, $params)
    {
        $this->$name = $params[0];
        return $this;
    }
}
