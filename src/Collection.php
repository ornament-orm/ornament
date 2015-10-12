<?php

namespace Ornament;

use SplObjectStorage;
use JsonSerializable;

class Collection extends SplObjectStorage implements JsonSerializable
{
    private $_deleted;
    private $_meta;

    public function __construct(array $input, $parent = null, array $map = [])
    {
        $this->_original = count($input);
        $this->_meta = compact('parent', 'map');
        foreach ($input as $value) {
            $this->attach($value);
        }
        $this->_deleted = new SplObjectStorage;
    }

    /**
     * Save the collection. This creates new models, updates dirty models and
     * deletes removed models (since the last save). Note that this is not in a
     * transaction; the programmer must implement that for compatible adapters.
     *
     * @return null|array null on success, or an array of errors.
     */
    public function save()
    {
        $foundkeys = [];
        $errors = [];
        $i = 0;
        foreach ($this as $model) {
            if (!Helper::isModel($model)) {
                continue;
            }
            $model->__index($i++);
            if ($error = $model->save()) {
                $errors[] = $error;
            }
        }
        foreach ($this->_deleted as $model) {
            if ($error = $model->delete()) {
                $errors[] = $error;
            }
            $this->_deleted->detach($model);
        }
        $this->_original = count($this);
        return $errors ? $errors : null;
    }

    /**
     * Check if the collection is "dirty", i.e. its contents have changed since
     * the last save.
     *
     * @return boolean true if dirty, otherwise false.
     */
    public function dirty()
    {
        if (count($this) != $this->_original) {
            return true;
        }
        if (count($this->_deleted)) {
            return true;
        }
        foreach ($this as $model) {
            if (is_object($model)
                && ($model->isDirty() || $model->isNew())
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Mark the collection as "clean", i.e. in pristine state.
     *
     * @return void
     */
    public function markClean()
    {
        foreach ($this as $model) {
            if (is_object($model)) {
                $model->markClean();
            }
        }
        $this->_original = count($this);
    }

    /**
     * Export the Collection as a regular PHP array for Json serialization.
     *
     * @return array The Collection represented as an array.
     */
    public function jsonSerialize()
    {
        $out = [];
        foreach ($this as $model) {
            if (!is_object($model)) {
                continue;
            }
            if ($model instanceof JsonSerializable) {
                $out[] = $model->jsonSerialize();
            } else {
                $out[] = (object)(array)$model;
            }
        }
        return $out;
    }

    public function offsetGet($object)
    {
        if (is_integer($object)) {
            $i = 0;
            foreach ($this as $o) {
                if (!Helper::isModel($o)) {
                    continue;
                }
                if ($i++ == $object) {
                    return $o;
                }
            }
            throw new UnexpectedValueException;
        } elseif (is_object($object)) {
            return parent::offsetGet($object);
        }
    }

    public function offsetSet($object, $data)
    {
        $this->_deleted->detach($object);
        if (isset($this->_meta['parent'], $this->_meta['map'])) {
            foreach ($this->_meta['map'] as $cf => $pf) {
                $object->$cf =& $this->_meta['parent']->$pf;
            }
        }
        return parent::offsetSet($object, $data);
    }

    public function offsetUnset($object)
    {
        $this->_deleted->attach($object);
        return parent::offsetUnset($object);
    }
}

