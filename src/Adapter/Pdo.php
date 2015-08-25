<?php

namespace Ornament\Adapter;

use Ornament\Adapter;
use Ornament\Repository;
use Ornament\Model;
use PDO as Base;
use PDOException;
use InvalidArgumentException;

class Pdo implements Adapter
{
    private $adapter;
    private $table;
    private $fields;
    private $primaryKey;
    private $statements = [];

    public function __construct(Base $adapter, $table, array $fields)
    {
        if (!($adapter instanceof Base)) {
            throw new InvalidArgumentException;
        }
        $this->adapter = $adapter;
        $this->table = $table;
        $this->fields = $fields;
    }

    public function setPrimaryKey($field)
    {
        $this->primaryKey = func_get_args();
        return $this;
    }

    public function query(
        $object,
        array $parameters,
        array $opts = [],
        array $ctor = []
    )
    {
        $keys = [];
        $values = [];
        foreach ($parameters as $key => $value) {
            $keys[$key] = sprintf('%s = ?', $key);
            $values[] = $value;
        }
        if ($keys) {
            $sql = "SELECT * FROM %1\$s WHERE %2\$s";
            $sql = sprintf(
                $sql,
                $this->table,
                implode(' AND ', $keys)
            );
        } else {
            $sql = "SELECT * FROM {$this->table}";
        }
        if (isset($opts['order'])) {
            $sql .= sprintf(
                ' ORDER BY %s',
                preg_replace('@[^\w,\s\(\)]', '', $opts['order'])
            );
        }
        if (isset($opts['limit'])) {
            $sql .= sprintf(' LIMIT %d', $opts['limit']);
        }
        if (isset($opts['offset'])) {
            $sql .= sprintf(' OFFSET %d', $opts['offset']);
        }
        $stmt = $this->getStatement($sql);
        $stmt->setFetchMode(Base::FETCH_INTO, $object);
        $stmt->execute($values);
        $class = get_class($object);
        return $stmt->fetchAll(Base::FETCH_CLASS, $class, $ctor);
    }

    public function load(Model $object)
    {
        $pks = [];
        $values = [];
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
        $object->markClean();
    }

    protected function getStatement($sql)
    {
        if (!isset($this->statements[$sql])) {
            $this->statements[$sql] = $this->adapter->prepare($sql);
        }
        return $this->statements[$sql];
    }

    public function create(Model $object)
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
        $retval = $stmt->execute($values);
        if (count($this->primaryKey) == 1) {
            $pk = $this->primaryKey[0];
            try {
                $object->$pk = $this->adapter->lastInsertId($this->table);
                $this->load($object);
            } catch (PDOException $e) {
                // Means this is not supported by this engine.
            }
        }
        return $retval;
    }

    public function update(Model $object)
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
        $retval = $stmt->execute($values);
        $this->load($object);
        return $retval;
    }

    public function delete(Model $object)
    {
        $sql = "DELETE FROM %1\$s WHERE %2\$s";
        $primaries = [];
        foreach ($this->primaryKey as $key) {
            $primaries[] = sprintf('%s = ?', $key);
            $values[] = $object->$key;
        }
        $sql = sprintf(
            $sql,
            $this->table,
            implode(' AND ', $primaries)
        );
        $stmt = $this->getStatement($sql);
        $retval = $stmt->execute($values);
        return $retval;
    }
}

