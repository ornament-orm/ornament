<?php

namespace Ornament;

class Model
{
    private $adapter;
    private $lastCheck;

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    public function save()
    {
        var_dump($this->isNew());
        if ($this->isNew()) {
            return $this->adapter->create($this);
        } else {
            return $this->adapter->update($this);
        }
    }

    public function delete()
    {
        return $this->adapter->delete($this);
    }

    private function export()
    {
        $o = $this;
        return call_user_func(function () use ($o) {
            return get_object_vars($o);
        });
    }

    public function markNew()
    {
        $this->lastCheck = [];
    }

    public function isNew()
    {
        return $this->lastCheck == [];
    }

    public function isDirty()
    {
        var_dump($this->lastCheck, $this->export());
        return $this->lastCheck != $this->export();
    }

    public function markClean()
    {
        $this->lastCheck = $this->export();
    }
}

