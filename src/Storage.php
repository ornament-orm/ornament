<?php

namespace Ornament;

trait Storage
{
    protected function addAdapter(Adapter $adapter, $id = null)
    {
        Repository::registerAdapter($this, $adapter, $id);
        return $adapter;
    }

    public function save()
    {
        $adapters = Repository::getAdapters($this);
        $errors = [];
        foreach ($adapters as $adapter) {
            if ($error = $adapter->store($this)) {
                $errors[] = $error;
            }
        }
        foreach (Helper::export($this) as $prop => $value) {
            if (is_object($value) && Helper::isModel($value)) {
                if (!method_exists($value, 'dirty') || $value->dirty()) {
                    $value->save();
                }
            } elseif (is_array($value)) {
                foreach ($this->$prop as $index => $model) {
                    if (is_object($model) && Helper::isModel($model)) {
                        $model->__index($index);
                        if (!method_exists($model, 'dirty')
                            || $model->dirty()
                        ) {
                            $model->save();
                        }
                    }
                }
            }
        }
        return $errors ? $errors : null;
    }

    public function delete()
    {
        $adapters = Repository::getAdapters($this);
        $errors = [];
        foreach ($adapters as $adapter) {
            if ($error = $adapter->delete($this)) {
                $errors[] = $error;
            }
        }
        return $errors ? $errors : null;
    }

    public function dirty()
    {
        return Repository::isDirty($this);
    }

    public function __get($prop)
    {
        $method = 'get'.ucfirst(Helper::denormalize($prop));
        if (method_exists($this, $method)) {
            return $this->$method();
        }
        if (method_exists($this, 'callback')) {
            try {
                return $this->callback($method);
            } catch (Exception\UndefinedCallback $e) {
            }
        }
        throw new Exception\UnknownVirtualProperty;
    }

    public function __set($prop, $value)
    {
        $method = 'set'.ucfirst(Helper::denormalize($prop));
        if (method_exists($this, $method)) {
            return $this->$method($value);
        }
        if (method_exists($this, 'callback')) {
            try {
                return $this->callback($method, [$value]);
            } catch (Exception\UndefinedCallback $e) {
            }
        }
        throw new Exception\UnknownVirtualProperty;
    }

    public function __isset($prop)
    {
        $method = 'get'.ucfirst(Helper::denormalize($prop));
        if (method_exists($this, $method)) {
            return true;
        }
        if (method_exists($this, 'callback')) {
            try {
                $this->callback($method, [$value]);
                return true;
            } catch (Exception\UndefinedCallback $e) {
            }
        }
        return false;
    }

    /**
     * You'll want to specify a custom implementation for this.
     */
    public function __index($index)
    {
    }
}

