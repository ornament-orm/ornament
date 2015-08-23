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
        $method = 'get'.ucfirst(Helper::denormalize($prop));
        if (method_exists($this, $method)) {
            return $this->$method();
        }
        if (method_exists($this, 'callback')) {
            try {
                return $this->callback($method);
            } catch (Exception\UndefinedCallback $e) {
                var_dump($method);
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
                var_dump($method);
            }
        }
        throw new Exception\UnknownVirtualProperty;
    }
}

