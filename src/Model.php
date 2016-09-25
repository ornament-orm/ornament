<?php

namespace Ornament\Ornament;

use zpt\anno\Annotations;
use ReflectionClass;
use ReflectionProperty;
use SplObjectStorage;
use StdClass;

trait Model
{
    public function __construct()
    {
        $this->ornamentalize();
    }

    /**
     * @var bool
     *
     * Stores whether the model is "new" or not.
     */
    private $__new = true;

    /**
     * @var Ornament\Ornament\State
     *
     * Private storage of model's current state.
     * @Private
     */
    private $__state;

    /**
     * @var SplObjectStorage
     *
     * Private storage of the model's data sources.
     */
    private $__sources;

    /**
     * @var array
     *
     * Hash of decorators for all properties.
     */
    private static $__decorators = [];

    /**
     * @var array
     *
     * Hash of defined decorators.
     */
    private static $__decoratorMethods = [];

    public function ornamentalize($return = null)
    {
        static $reflection;
        static $properties;
        if (!isset($reflection)) {
            $reflection = new ReflectionClass($this);
            $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED);
        }

        static $annotations;
        if (!isset($annotations)) {
            $annotator = get_class($this);
            if (strpos($annotator, '@anonymous')) {
                $annotator = $reflection->getParentClass()->name;
                $reflector = new ReflectionClass($annotator);
            } else {
                $reflector = $reflection;
            }
            $annotations['class'] = new Annotations($reflector);
            $annotations['methods'] = [];
            foreach ($reflector->getMethods() as $method) {
                $anns = new Annotations($method);
                $name = $method->getName();
                if (isset($anns['Decorate'])) {
                    self::$__decoratorMethods[$anns['Decorate']] = $name;
                }
                $annotations['methods'][$name] = $anns;
            }
            $defaults = $reflector->getDefaultProperties();
            foreach ($properties as $property) {
                $name = $property->getName();
                $anns = new Annotations($property);
                $annotations['properties'][$name] = $anns;
                if (isset($this->$name, $defaults[$name])
                    && $this->$name != $defaults[$name]
                ) {
                    $this->__new = false;
                }
                self::$__decorators[$name] = [];
                foreach (self::$__decoratorMethods as $decorator => $method) {
                    if (isset($anns[$decorator])) {
                        self::$__decorators[$name][$decorator] = [
                            $method,
                            $anns[$decorator],
                        ];
                    }
                }
            }
        }

        if (!isset($this->__sources)) {
            $this->__sources = new SplObjectStorage;
        }
        if (!isset($this->__state)) {
            $this->__state = new StdClass;
        }
        if ($this->__sources->contains($this->__state)) {
            $this->__sources->detach($this->__state);
        }
        foreach ($properties as $property) {
            $name = $property->name;
            $this->__state->$name = null;
        }
        $this->__sources->attach($this->__state);
        foreach ($properties as $property) {
            $name = $property->name;
            $this->__state->$name = $this->$name;
            unset($this->$name);
        }
        if ($return && isset($$return)) {
            return $$return;
        }
    }

    /**
     * Overloaded getter. All public and protected properties on a model are
     * exposed this way, _unless_ they are marked as `@Private`. Non-public
     * properties are read-only.
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
        $method = 'get'.ucfirst(preg_replace_callback(
            "@_(\w)@",
            function ($match) {
                return strtoupper($match[1]);
            },
            $prop
        ));
        if (method_exists($this, $method)) {
            return $this->$method();
        }
        $found = false;
        foreach ($this->__sources as $source) {
            if (property_exists($source, $prop)) {
                $val = $source->$prop;
                $found = true;
                break;
            }
        }
        if (!$found) {
            trigger_error(sprintf(
                "Trying to get undefined or private property %s on %s.",
                $prop,
                get_class($this)
            ), E_USER_NOTICE);
            return null;
        }
        array_walk(self::$__decorators[$prop], function ($decorator) use (&$val) {
            list($method, $params) = $decorator;
            $val = $this->$method($val, $params);
        });
        return $val;
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
        $method = 'set'.ucfirst(preg_replace_callback(
            "@_(\w)@",
            function ($match) {
                return strtoupper($match[1]);
            },
            $prop
        ));
        if (method_exists($this, $method)) {
            $this->$method($value);
            return;
        }
        $modified = false;
        if (!isset($this->__sources)) {
            $this->__new = false;
            return;
        }
        foreach ($this->__sources as $source) {
            if (property_exists($source, $prop)) {
                $val = $value;
                array_walk(self::$__decorators[$prop], function ($decorator) use (&$val) {
                    list($method, $params) = $decorator;
                    $val = $this->$method($val, $params);
                    if (is_object($val)) {
                        if (is_callable($val)) {
                            $val = $val();
                        } else {
                            $val = "$val";
                        }
                        $value = $val;
                    }
                });
                $source->$prop = $value;
                $modified = true;
            }
        }
        if ($modified) {
            return;
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
        foreach ($this->__sources as $source) {
            if (property_exists($source, $prop)) {
                return true;
            }
        }
        return false;

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
     * @param object $source
     * @return static
     */
    public function addDatasource($source)
    {
        $this->__sources[$source] = clone $source;
        $this->__new = false;
        return $this;
    }

    /**
     * @param object $source
     * @param mixed ...$ctor Optional construction arguments needed by the new
     *  model instance.
     * @return static
     */
    public static function fromDatasource($source, ...$ctor)
    {
        $model = new static(...$ctor);
        return $model->addDatasource($source);
    }

    /**
     * Returns true if the model doesn't have any existing data sources
     * attached, otherwise false.
     *
     * @return bool
     */
    public function isNew()
    {
        return $this->__new;
    }

    /**
     * Returns true if any of the associated containers is dirty.
     *
     * @return bool
     */
    public function isDirty()
    {
        foreach ($this->__sources as $source) {
            if ($this->__sources[$source] != $source) {
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
    public function isModified($property)
    {
        foreach ($this->__sources as $source) {
            if (property_exists($source, $property)
                && $source->$property != $this->__sources[$source]->$property
            ) {
                return true;
            }
        }
        return false;
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
        foreach ($this->__sources as $source) {
            foreach ($source as $prop => $value) {
                $source->$prop = $this->$prop;
            }
        }
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

