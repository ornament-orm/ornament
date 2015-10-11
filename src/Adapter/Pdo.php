<?php

namespace Ornament\Adapter;

use Ornament\Adapter;
use Ornament\Container;
use PDO as Base;
use PDOException;
use InvalidArgumentException;

final class Pdo implements Adapter
{
    use Defaults;

    private $statements = [];

    public function __construct(Base $adapter)
    {
        if (!($adapter instanceof Base)) {
            throw new InvalidArgumentException;
        }
        $this->adapter = $adapter;
    }

    public function query($object, array $parameters, array $opts = [])
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
                $this->identifier,
                implode(' AND ', $keys)
            );
        } else {
            $sql = "SELECT * FROM {$this->identifier}";
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
        $stmt->execute($values);
        return $stmt->fetchAll(Base::FETCH_CLASS, get_class($object));
    }

    public function load(Container $object)
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
            $this->identifier,
            implode(' AND ', $pks)
        ));
        $stmt->setFetchMode(Base::FETCH_INTO, $object);
        $stmt->execute($values);
        $stmt->fetch();
        $object->markClean();
    }

    private function getStatement($sql)
    {
        if (!isset($this->statements[$sql])) {
            $this->statements[$sql] = $this->adapter->prepare($sql);
        }
        return $this->statements[$sql];
    }

    public function create(Container $object)
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
            $this->identifier,
            implode(', ', array_keys($placeholders)),
            implode(', ', $placeholders)
        );
        $stmt = $this->getStatement($sql);
        $retval = $stmt->execute($values);
        if (count($this->primaryKey) == 1) {
            $pk = $this->primaryKey[0];
            try {
                $object->$pk = $this->adapter->lastInsertId($this->identifier);
                $this->load($object);
            } catch (PDOException $e) {
                // Means this is not supported by this engine.
            }
        }
        return $retval;
    }

    public function update(Container $object)
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
            $this->identifier,
            implode(', ', $placeholders),
            implode(' AND ', $primaries)
        );
        $stmt = $this->getStatement($sql);
        $retval = $stmt->execute($values);
        $this->load($object);
        return $retval;
    }

    public function delete(Container $object)
    {
        $sql = "DELETE FROM %1\$s WHERE %2\$s";
        $primaries = [];
        foreach ($this->primaryKey as $key) {
            $primaries[] = sprintf('%s = ?', $key);
            $values[] = $object->$key;
        }
        $sql = sprintf(
            $sql,
            $this->identifier,
            implode(' AND ', $primaries)
        );
        $stmt = $this->getStatement($sql);
        $retval = $stmt->execute($values);
        return $retval;
    }
}

