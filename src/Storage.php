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
        $method = 'get'.Helper::denormalize($prop);
        if (method_exists($this, $method)) {
            return $this->$method();
        }
        throw new UnknownVirtualPropertyException;
    }

    public function __set($prop, $value)
    {
        $method = 'set'.Helper::denormalize($prop);
        if (method_exists($this, $method)) {
            return $this->$method($value);
        }
        throw new UnknownVirtualPropertyException;
    }
}

