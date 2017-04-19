<?php

namespace Ornament\Ornament;

use PDO as Base;
use PDOException;
use InvalidArgumentException;

/**
 * Base adapter class others may extend.
 */
abstract class DefaultAdapter implements Adapter
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
     * @var The properties (properties) on the Model handled by this adapter.
     */

    protected $properties = [];
    /**
     * @var The properties to be considered primary keys a.k.a. unique identifiers.
     */

    protected $primaryKey = [];
    /**
     * @var Private store for model annotations that might be needed during data
     *  manipulation.
     */

    protected $annotations = [];

    /**
     * Guess the identifier for a model based on a callback.
     */
    public function guessIdentifier(callable $fn = null)
    {
        $class = get_class($this);
        if (strpos($class, '@anonymous') !== false) {
            $class = (new ReflectionClass($this))->getParentClass()->name;
        }
        if (!isset($fn)) {
            $fn = function ($class) {
                return $class;
            };
        }
        return $fn($class);
    }

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
     * Set the properties valid for this adapter.
     *
     * @param array $properties Array of property names this adapter managers.
     * @return self
     */
    public function setProperties(array $properties)
    {
        $this->properties = $properties;
        return $this;
    }

    /**
     * Set the propertie(s) to be used as primary key(s).
     *
     * @param string $property... One or more property names, which must correspond to
     *  properties defined on the model in question.
     * @return self
     */
    public function setPrimaryKey($property)
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

