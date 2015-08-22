<?php

namespace Ornament;

trait Storage
{
    protected function addAdapter(Adapter $adapter)
    {
        Repository::registerAdapter($this, $adapter);
        return $adapter;
    }

    public function save($pk = null)
    {
        $adapters = Repository::getAdapters($this);
        $errors = [];
        foreach ($adapters as $adapter) {
            if ($error = $adapter->store($this, $pk)) {
                $errors[] = $error;
            }
        }
        return $errors ? $errors : null;
    }

    public function dirty()
    {
        return Repository::isDirty($this);
    }
}

