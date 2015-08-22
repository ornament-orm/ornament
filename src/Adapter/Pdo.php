<?php

namespace Ornament\Adapter;

use Ornament\Adapter;
use Ornament\Repository;
use PDO as Base;
use PDOException;

class Pdo implements Adapter
{
    private $adapter;
    private $table;
    private $fields;
    private $primaryKey;
    private $statements = [];

    public function __construct(Base $adapter)
    {
        $this->adapter = $adapter;
    }

    public function setTable($table)
    {
        $this->table = $table;
        return $this;
    }

    public function setFields($field)
    {
        $this->fields = func_get_args();
        return $this;
    }

    public function setPrimaryKey($field)
    {
        $this->primaryKey = func_get_args();
        return $this;
    }

    public function store($object)
    {
        $type = 'update';
        foreach ($this->primaryKey as $key) {
            if (!isset($object->$key)) {
                $type = 'insert';
                break;
            }
        }
        $retval = $this->$type($object, $pk = null);
        if ($type == 'insert' && count($this->primaryKey) == 1) {
            $pk = $this->primaryKey[0];
            try {
                $object->$pk = $this->adapter->lastInsertId($pk);
            } catch (PDOException $e) {
                // Means this is not supported by this engine.
            }
        }
        $pks = [];
        foreach ($this->primaryKey as $key) {
            if (isset($object->$key)) {
                $pks[$key] = sprintf('%s = ?', $key);
                $values[] = $object->$key;
            } else {
                throw new PrimaryKeyException($object);
            }
        }
        $sql = "SELECT * FROM %1\$s WHERE %2\$s";
        $stmt = $this->getStatement(sprintf(
            $sql,
            $this->table,
            implode(' AND ', $pks)
        ));
        $stmt->setFetchMode(Base::FETCH_INTO, $object);
        $stmt->execute($values);
        $stmt->fetch();
        Repository::markClean($object);
        return $retval;
    }

    private function getStatement($sql)
    {
        if (!isset($this->statements[$sql])) {
            $this->statements[$sql] = $this->adapter->prepare($sql);
        }
        return $this->statements[$sql];
    }

    private function insert($object, $pk = null)
    {
        $sql = "INSERT INTO %1\$s (%2\$s) VALUES (%3\$s)";
        $placeholders = [];
        $values = [];
        foreach ($this->fields as $field) {
            if (isset($object->$field)) {
                $placeholders[$field] = '?';
                $values[] = $object->$field;
            }
        }
        $sql = sprintf(
            $sql,
            $this->table,
            implode(', ', array_keys($placeholders)),
            implode(', ', $placeholders)
        );
        $stmt = $this->getStatement($sql);
        return $stmt->execute($values);
    }

    private function update($object)
    {
        $sql = "UPDATE %1\$s SET %2\$s WHERE %3\$s";
        $placeholders = [];
        $values = [];
        foreach ($this->fields as $field) {
            $placeholders[$field] = sprintf('%s = ?', $field);
            $values[] = $object->$field;
        }
        $primaries = [];
        foreach ($this->primaryKey as $key) {
            $primaries[] = sprintf('%s = ?', $key);
            $values[] = $object->$key;
        }
        $sql = sprintf(
            $sql,
            $this->table,
            implode(', ', $placeholders),
            implode(' AND ', $primaries)
        );
        $stmt = $this->getStatement($sql);
        return $stmt->execute($values);
    }
}

