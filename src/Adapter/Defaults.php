<?php

namespace Ornament\Adapter;

use Ornament\Adapter;
use Ornament\Container;
use PDO as Base;
use PDOException;
use InvalidArgumentException;

/**
 * Simple trait custom adapters may `use` to quickly get setup.
 */
trait Defaults
{
    /**
     * @var Stores the "parent" adapter, e.g. a PDO instance.
     */
    protected $adapter;
    /**
     * @var Stores the identifier for this model (i.e., whatever name needs to
     *  be used when communicating with its backend storage, like a table name).
     */
    protected $identifier;
    /**
     * @var The fields (properties) on the Model handled by this adapter.
     */
    protected $fields = [];
    /**
     * @var The fields to be considered primary keys a.k.a. unique identifiers.
     */
    protected $primaryKey = [];
    /**
     * @var Private store for model annotations that might be needed during data
     *  manipulation.
     */
    protected $annotations = [];

    /**
     * Set the identifier to be used for this adapter.
     *
     * @param string $identifier An identifier (table name, API endpoint etc.)
     * @return self
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * Set the fields valid for this adapter.
     *
     * @param array $fields Array of field names.
     * @return self
     */
    public function setFields(array $fields)
    {
        $this->fields = $fields;
        return $this;
    }

    /**
     * Set the field(s) to be used as primary key(s).
     *
     * @param string $field... One or more field names, which must correspond to
     *  properties defined on the model in question.
     * @return self
     */
    public function setPrimaryKey($field)
    {
        $this->primaryKey = func_get_args();
        return $this;
    }

    /**
     * Set the annotations for this adapter.
     *
     * @param array $annotations
     * @return self
     */
    public function setAnnotations(array $annotations)
    {
        $this->annotations = $annotations;
        return $this;
    }

    /**
     * Internal helper to "flatten" all values associated with an operation.
     * This is mainly useful for being able to store models on foreign keys and
     * automatically "flatten" them to the associated key during saving.
     *
     * @param array &$values The array of values to flatten.
     */
    protected function flattenValues(&$values)
    {
        array_walk($values, function (&$value) {
            if ($this->isOrnamentModel($value)) {
                $value = $value->getPrimaryKey();
                if (is_array($value)) {
                    $value = '('.implode(', ', $value).')';
                }
            }
        });
    }

    /**
     * Quick and dirty check to see if a value seems like an Ornament model.
     *
     * @param mixed $value The value to check.
     * @return bool True if it seems a model, else false.
     */
    public function isOrnamentModel($value)
    {
        if (!is_object($value)) {
            return false;
        }
        if (method_exists($value, 'getPrimaryKey')) {
            return true;
        }
        return false;
    }
}

