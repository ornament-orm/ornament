<?php

namespace Ornament;

trait Storage
{
    protected function addAdapter(Adapter $adapter)
    {
        Repository::registerAdapter($this, $adapter);
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
}

