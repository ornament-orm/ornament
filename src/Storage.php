<?php

namespace Ornament;

use zpt\anno\Annotations;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

trait Storage
{
    protected function addAdapter(Adapter $adapter, $id, array $fields)
    {
        Repository::registerAdapter($this, $adapter, $id, $fields);
        return $adapter;
    }

    private function annotations()
    {
        static $annotations;
        if (!isset($annotations)) {
            $reflector = new ReflectionClass($this);
            $annotations['class'] = new Annotations($reflector);
            $annotations['methods'] = [];
            foreach ($reflector->getMethods(
                ReflectionMethod::IS_PUBLIC
            ) as $method) {
                $annotations['methods'][$method->getName()]
                    = new Annotations($method);
            }
            foreach ($reflector->getProperties(
                ReflectionProperty::IS_PUBLIC
            ) as $property) {
                $annotations['properties'][$property->getName()]
                    = new Annotations($property);
            }
        }
        return $annotations;
    }

    public function load($includeBase = true)
    {
        $annotations = $this->annotations();
        if ($includeBase) {
            $adapters = Repository::getAdapters($this);
            $errors = [];
            foreach ($adapters as $model) {
                $model->load();
            }
        }
        foreach ($annotations['methods'] as $method => $anns) {
            if (isset($anns['onLoad']) && $anns['onLoad']) {
                $this->$method($annotations['properties']);
            }
        }
    }

    public function query(array $parameters)
    {
        $annotations = $this->annotations();
        $adapters = Repository::getAdapters($this);
        $errors = [];
        foreach ($adapters as $model) {
            return $model->query($this, $parameters);
        }
    }

    public function save()
    {
        $adapters = Repository::getAdapters($this);
        $errors = [];
        foreach ($adapters as $model) {
            if ($model->isDirty()) {
                if ($error = $model->save()) {
                    $errors[] = $error;
                }
            }
        }
        foreach (Helper::export($this) as $prop => $value) {
            if (is_object($value) && Helper::isModel($value)) {
                if (!method_exists($value, 'isDirty') || $value->isDirty()) {
                    $value->save();
                }
            } elseif (is_array($value)) {
                foreach ($this->$prop as $index => $model) {
                    if (is_object($model) && Helper::isModel($model)) {
                        $model->__index($index);
                        if (!method_exists($model, 'isDirty')
                            || $model->isDirty()
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

    public function markClean()
    {
        $adapters = Repository::getAdapters($this);
        foreach ($adapters as $model) {
            $model->markClean();
        }
        foreach (Helper::export($this) as $prop => $value) {
            if (is_object($value) && Helper::isModel($value)) {
                if (method_exists($value, 'markClean')) {
                    $value->markClean();
                }
            } elseif (is_array($value)) {
                foreach ($this->$prop as $index => $model) {
                    if (is_object($model) && Helper::isModel($model)) {
                        if (method_exists($model, 'markClean')) {
                            $model->markClean();
                        }
                    }
                }
            }
        }
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
        if (property_exists($this, $prop)
            && substr($prop, 0, 2) != '__'
        ) {
            return $this->$prop;
        }
        trigger_error(sprintf(
            "Trying to get undefined virtual property %s on %s.",
            $prop,
            get_class($this)
        ), E_USER_NOTICE);
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
        trigger_error(sprintf(
            "Trying to set undefined or immutable virtual property %s on %s.",
            $prop,
            get_class($this)
        ), E_USER_NOTICE);
    }

    public function __isset($prop)
    {
        $method = 'get'.ucfirst(Helper::denormalize($prop));
        if (method_exists($this, $method)) {
            return true;
        }
        if (method_exists($this, 'callback')) {
            try {
                return $this->callback($method, null);
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

