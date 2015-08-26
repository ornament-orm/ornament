<?php

namespace Ornament;

use ArrayObject;

class Collection extends ArrayObject
{
    private $original = [];
    private $map = [];

    public function __construct($input)
    {
        if (!is_array($input)) {
            parent::__construct([]);
            return;
        }
        foreach ($input as $index => $value) {
            $key = spl_object_hash($value);
            $this->original[$key] = $value;
            $this->map[$index] = $key;
        }
        parent::__construct($input);
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
        foreach ($this->getArrayCopy() as $i => $model) {
            $key = spl_object_hash($model);
            $model->__index($i);
            $foundkeys[$key] = true;
            if ($error = $model->save()) {
                $errors[] = $error;
            }
            $this->original[$key] = $model;
        }
        foreach ($this->original as $key => $model) {
            if (!isset($foundkeys[$key])) {
                if ($error = $model->delete()) {
                    $errors[] = $error;
                }
                unset($this->original[$key]);
            }
        }
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
        return $this->getArrayCopy() != array_values($this->original);
    }

    /**
     * Mark the collection as "clean", i.e. in pristine state.
     *
     * @return void
     */
    public function markClean()
    {
        $this->storage = [];
        foreach ($this->getArrayCopy() as $model) {
            $key = spl_object_hash($model);
            $this->storage[$key] = $model;
        }
    }
}

