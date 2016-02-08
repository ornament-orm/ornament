<?php

namespace Ornament;

use StdClass;
use ReflectionProperty;

/**
 * A container is a simple internal object representation of (part of) a model.
 */
class Container
{
    /** @var Private Adapter storage. */
    private $adapter;
    /** @var Private store for last check of model's state. */
    private $lastCheck = [];

    /**
     * Constructor. Initialize with the Adapter to use.
     */
    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
        $this->lastCheck = $this->export();
    }

    /**
     * Query multiple models in an Adapter-independent way.
     *
     * @param object $parent The actual parent model to load into.
     * @param array $parameters Key/value pair of parameters to query on (e.g.,
     *                          ['parent' => 1]).
     * @param array $opts Optional hash of options.
     * @param array $ctor Optional contructor arguments.
     * @return array Array of models of the same type as $parent.
     */
    public function query($parent, array $parameters, array $opts = [], array $ctor = [])
    {
        return $this->adapter->query($parent, $parameters, $opts, $ctor);
    }

    /**
     * (Re)load the model based on existing settings.
     */
    public function load()
    {
        $this->adapter->load($this);
    }

    /**
     * Persist this model back to whatever storage Adapter it was constructed
     * with.
     *
     * @return boolean true on success, false on error.
     */
    public function save()
    {
        if ($this->isNew()) {
            return $this->adapter->create($this);
        } else {
            return $this->adapter->update($this);
        }
    }

    /**
     * Delete this model from whatever storage Adapter it was constructed with.
     *
     * @return boolean true on success, false on error.
     */
    public function delete()
    {
        return $this->adapter->delete($this);
    }

    /**
     * Internal helper method to export the model's public properties.
     *
     * @return array An array of key/value pairs.
     */
    private function export()
    {
        $exported = new StdClass;
        foreach ($this as $name => $field) {
            if ((new ReflectionProperty($this, $name))->isPublic()) {
                if (is_object($field)) {
                    $traits = class_uses($field);
                    if (isset($traits['Ornament\Model'])
                        || isset($traits['Ornament\JsonModel'])
                    ) {
                        $exported->$name = $field->getPrimaryKey();
                    } else {
                        $exported->$name = "$field";
                    }
                } else {
                    $exported->$name = $field;
                }
            }
        }
        return $exported;
    }

    /**
     * Marks this model as being "new" (i.e., save proxies to create, not
     * update).
     */
    public function markNew()
    {
        $this->lastCheck = [];
    }

    /**
     * Marks this model as "deleted". The class will still contain old
     * properties and values, but deletion has taken place.
     */
    public function markDeleted()
    {
        $this->lastCheck = null;
    }

    /**
     * Marks this model as "clean", i.e. set clean state to current state,
     * no questions asked.
     *
     * @see Ornament\Storage::markClean
     */
    public function markClean()
    {
        $this->lastCheck = $this->export();
    }

    /**
     * Checks if this model is "new".
     *
     * @return boolean true if new, otherwise false.
     */
    public function isNew()
    {
        return $this->lastCheck == [];
    }

    /**
     * Checks if this model has been deleted.
     *
     * @return boolean true if deleted, otherwise false.
     */
    public function isDeleted()
    {
        return !isset($this->lastCheck);
    }

    /**
     * Checks if this model is "dirty" compared to the last known "clean"
     * state.
     *
     * @return boolean true if dirty, otherwise false.
     */
    public function isDirty()
    {
        return $this->lastCheck != $this->export();
    }
}

