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
     * Stores the "parent" adapter, e.g. a PDO instance.
     */
    private $adapter;
    /**
     * Stores the identifier for this model (i.e., whatever name needs to be
     * used when communicating with its backend storage, like a table name).
     */
    private $identifier;
    /**
     * The fields (properties) on the Model handled by this adapter.
     */
    private $fields;
    /**
     * The fields to be considered primary keys a.k.a. unique identifiers.
     */
    private $primaryKey = [];

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
}

