<?php

namespace Ornament\Ornament;

use zpt\anno\Annotations;
use ReflectionClass;
use ReflectionProperty;

trait Model
{
    //use Annotate;

    /**
     * @var Ornament\Ornament\State
     * Private storage of model's current state.
     * @Private
     */
    private $__state;

    protected function ornamentalize()
    {
        static $annotations;
        if (!isset($annotations)) {
            $annotator = get_class($this);
            if (strpos($annotator, '@anonymous')) {
                $annotator = (new ReflectionClass($this))
                    ->getParentClass()->name;
            }
            $reflector = new ReflectionClass($annotator);
            $annotations['class'] = new Annotations($reflector);
            $annotations['methods'] = [];
            foreach ($reflector->getMethods() as $method) {
                $annotations['methods'][$method->getName()]
                    = new Annotations($method);
            }
            foreach ($reflector->getProperties(
                ReflectionProperty::IS_PUBLIC |
                ReflectionProperty::IS_PROTECTED
            ) as $property) {
                $annotations['properties'][$property->getName()]
                    = new Annotations($property);
            }
        }
        if (!isset($this->__state)) {
            $this->__state = new State($this, $annotations);
        }
        return $this->__state;
    }

    /**
     * Overloaded getter. All protected properties on a model are exposed this
     * way, _unless_ their name starts with an underscore or they are marked as
     * @Private. Non-public properties are read-only by default.
     *
     * Also checks if a getProperty exists on the model, or a magic callback
     * for 'get' was registered under this property's name.
     *
     * @param string $prop Name of the property.
     * @return mixed The property's (optionally computed) value.
     * @throws An error of type E_USER_NOTICE if the property is unknown.
     */
    public function __get($prop)
    {
        $state = $this->ornamentalize();
        if ($state->hasProperty($prop)) {
            $method = 'get'.ucfirst($state->unCamelCase($prop));
            if (method_exists($this, $method)) {
                return $this->$method();
            }
            if (method_exists($this, 'callback')) {
                try {
                    return $this->callback($method, []);
                } catch (Exception\UndefinedCallback $e) {
                }
            }
            if (property_exists($this, $prop) && $prop{0} != '_') {
                return $this->$prop;
            }
        }
        trigger_error(sprintf(
            "Trying to get undefined virtual property %s on %s.",
            $prop,
            get_class($this)
        ), E_USER_NOTICE);
    }

    /**
     * Overloaded setter. For protected, private or virtual properties, a
     * setPropertyname method must exist on the model, or a magic callback of
     * the same name must have been defined.
     *
     * @param string $prop The property to set.
     * @param mixed $value The new value.
     * @return void
     * @throws An error of type E_USER_NOTICE if the property is unknown or
     *  immutable.
    */
    public function __set($prop, $value)
    {
        $state = $this->ornamentalize();
        if ($state->hasProperty($prop)) {
            $method = 'set'.ucfirst($state->denormalize($prop));
            if (method_exists($this, $method)) {
                return $this->$method($value);
            }
            if (method_exists($this, 'callback')) {
                try {
                    return $this->callback($method, [$value]);
                } catch (Exception\UndefinedCallback $e) {
                }
            }
            if (!isset($this->__adapters)) {
                $this->$prop = $value;
                return;
            }
        }
        trigger_error(sprintf(
            "Trying to set undefined or immutable virtual property %s on %s.",
            $prop,
            get_class($this)
        ), E_USER_NOTICE);
    }
    
    /**
     * Check if a property is defined, but not public or virtual. Note that it
     * will return true if a property _is_ defined, but has a value of null.
     *
     * @param string $prop The property to check.
     * @return boolean True if set, otherwise false.
     */
    public function __isset($prop)
    {
        $state = $this->ornamentalize();
        if (!$state->hasProperty($prop)) {
            return false;
        }
        if (property_exists($this, $prop) && $prop{0} != '_') {
            return true;
        }
        $method = 'get'.ucfirst(Helper::denormalize($prop));
        if (method_exists($this, $method)) {
            return true;
        }
        if (method_exists($this, 'callback')) {
            try {
                $this->callback($method, null);
                return true;
            } catch (Exception\UndefinedCallback $e) {
            }
        }
        return false;
    }

    /**
     * Overloader for private/protected methods.
     */
    public function __call($method, array $args = [])
    {
        if (!method_exists($this, $method)) {
            return;
        }
    }

    /**
     * Returns true if any of the associated containers is new.
     *
     * @return bool
     */
    protected function isNew()
    {
        $this->ornamentalize();
        foreach ($this->__adapters as $model) {
            if ($model->isNew()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns true if any of the associated containers is dirty.
     *
     * @return bool
     */
    protected function isDirty()
    {
        $this->ornamentalize();
        foreach ($this->__adapters as $model) {
            if ($model->isDirty()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns true if a specific property on the model is dirty.
     *
     * @return bool
     */
    protected function isModified($property)
    {
        $this->ornamentalize();
        foreach ($this->__adapters as $model) {
            if (property_exists($model, $property)) {
                return $model->isModified($property);
            }
        }
    }

    /**
     * Get the current state of the model (new, clean, dirty or deleted).
     *
     * @return string The current state.
     */
    protected function state()
    {
        return $this->ornamentalize()->getState();
        // Do just-in-time checking for clean/dirty:
        if ($this->__state == 'clean') {
            foreach ($this->__adapters as $model) {
                if ($model->isDirty()) {
                    $this->__state = 'dirty';
                    break;
                }
            }
        }
        return $this->__state;
    }

    /**
     * Mark the current model as 'clean', i.e. not dirty. Useful if you manually
     * set values after loading from storage that shouldn't count towards
     * "dirtiness". Called automatically after saving.
     *
     * @return void
     */
    protected function markClean()
    {
        $this->ornamentalize();
        foreach ($this->__adapters as $model) {
            $model->markClean();
        }
        $annotations = $this->annotations()['properties'];
        foreach ($annotations as $prop => $anns) {
            if (isset($anns['Private']) || $prop{0} == '_') {
                continue;
            }
            $value = $this->$prop;
            if (is_object($value) and method_exists($value, 'markClean')) {
                $value->markClean();
            }
        }
        $this->__state = 'clean';
    }

    /**
     * You'll want to specify a custom implementation for this. For models in an
     * array (on another model, of course) it is called with the current index.
     * Obviously, overriding is only needed if the index is relevant.
     *
     * @param integer $index The current index in the array.
     * @return void
     */
    public function __index($index)
    {
    }
}

