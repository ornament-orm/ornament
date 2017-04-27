<?php

namespace Ornament\Core;

use zpt\anno\Annotations;
use ReflectionClass;
use ReflectionProperty;
use SplObjectStorage;
use StdClass;
use Error;

trait Model
{
    /**
     * @var StdClass
     *
     * Private storage of the model's initial state.
     */
    private $__initial;

    /**
     * @var StdClass
     *
     * Private storage of the model's current state.
     */
    private $__state;

    public function __construct()
    {
        $this->__ornamentalize();
    }

    private function __ornamentalize() : array
    {
        static $reflector;
        static $properties;
        static $annotations;
        if (!isset($reflector, $properties, $annotations)) {
            $annotator = get_class($this);
            while (strpos($annotator, '@anonymous')) {
                $annotator = (new ReflectionClass($annotator))->getParentClass()->name;
            }
            $reflector = new ReflectionClass($annotator);
            $properties = $reflector->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED & ~ReflectionProperty::IS_STATIC);
            $annotations['class'] = new Annotations($reflector);
            $annotations['methods'] = [];
            foreach ($reflector->getMethods() as $method) {
                $anns = new Annotations($method);
                $name = $method->getName();
                $annotations['methods'][$name] = $anns;
            }
            $defaults = $reflector->getDefaultProperties();
        }
        if (!isset($this->__state, $this->__initial)) {
            $this->__state = new StdClass;
            $this->__initial = new StdClass;
            foreach ($properties as $property) {
                $name = $property->getName();
                $anns = new Annotations($property);
                $anns['readOnly'] = $property->isProtected();
                $annotations['properties'][$name] = $anns;
                if (isset($this->$name, $defaults[$name])
                    && $this->$name != $defaults[$name]
                ) {
                    $this->__initial->$name = $this->$name;
                }
                $this->__state->$name = $this->$name;
                unset($this->$name);
            }
        }
        return $annotations;
    }

    /**
     * Overloaded getter. All public and protected properties on a model are
     * exposed this way. Non-public properties are read-only.
     *
     * If a method was specified as `@get` for this property, its return value
     * is used instead. If the property has a `@var` annotation _and_ it is an
     * instance of `Ornament\Core\Decorator`, the corresponding decorator class
     * is initialised with the property's current value and returned instead.
     *
     * @param string $prop Name of the property.
     * @return mixed The property's (optionally computed) value.
     * @throws Error if the property is unknown.
     */
    public function __get($prop)
    {
        $annotations = $this->__ornamentalize();
        foreach ($annotations['methods'] as $name => $anns) {
            if (isset($anns['get']) && $anns['get'] == $prop) {
                return call_user_func([$this, $name]);
            }
        }
        if (!property_exists($this->__state, $prop)) {
            $debug = debug_backtrace()[0];
            throw new Error(
                sprintf(
                    "Cannot access private property %s::%s in %s:%d",
                    get_class($this),
                    $prop,
                    $debug['file'],
                    $debug['line']
                ),
                0
            );
        }
        if (isset($annotations['properties'][$prop]['var'])
            && array_key_exists(
                'Ornament\Core\Decorator',
                class_implements($annotations['properties'][$prop]['var'])
            )
        ) {
            $class = $annotations['properties'][$prop]['var'];
            $args = [];
            if (isset($annotations['properties'][$prop]['construct'])) {
                $args = is_array($annotations['properties'][$prop]['construct']) ?
                    $annotations['properties'][$prop]['construct'] :
                    [$annotations['properties'][$prop]['construct']];
            }
            return new $class($this->__state->$prop, ...$args);
        }
        return $this->__state->$prop;
    }

    /**
     * Overloaded setter. Will only work for public properties (since protected
     * properties are read-only).
     *
     * If the value supplied is an instance of the `@var` annotation _and_ this
     * is an instance of `Ornament\Core\Decorator`, it is first converted to the
     * result of `getSource()`. Next, if a method was specified as `@set` for
     * this property, it is called with the supplied value as a single argumenti
     * and its return value is used.
     *
     * Otherwise, the corresponding property in `__state` is simply mutated. If
     * an `@var` annotation is given with a scalar type, the type is coerced.
     *
     * @param string $prop The property to set.
     * @param mixed $value The new value.
     * @return void
     * @throws Error if the property is unknown or immutable.
    */
    public function __set($prop, $value)
    {
        if (!property_exists($this->__state, $prop)) {
            $debug = debug_backtrace()[0];
            throw new Error(
                sprintf(
                    "Cannot access private property %s::%s in %s:%d",
                    get_class($this),
                    $prop,
                    $debug['file'],
                    $debug['line']
                ),
                0
            );
        }
        $annotations = $this->__ornamentalize();
        if ($annotations['properties'][$prop]['readOnly']) {
            $debug = debug_backtrace()[0];
            throw new Error(
                sprintf(
                    "Cannot access protected property %s::%s in %s:%d",
                    get_class($this),
                    $prop,
                    $debug['file'],
                    $debug['line']
                ),
                0
            );
        }
        if (isset($annotations['properties'][$prop]['var'])
            && array_key_exists(
                'Ornament\Core\Decorator',
                class_implements($annotations['properties'][$prop]['var'])
            )
            && $value instanceof $annotations['properties'][$prop]['var']
        ) {
            $value = $value->getSource();
        }
        foreach ($annotations['methods'] as $name => $anns) {
            if (isset($anns['set']) && $anns['set'] == $prop) {
                $value = call_user_func([$this, $name], $value);
                break;
            }
        }
        if (isset($annotations['properties'][$prop]['var'])
            && in_array(
                $annotations['properties'][$prop]['var'],
                [
                    'bool',
                    'int',
                    'float',
                    'string',
                    'array',
                    'object',
                    'null',
                ]
            )
        ) {
            $value = settype($value, $annotations['properties'][$prop]['var']);
        }
        $this->__state->$prop = $value;
    }
    
    /**
     * Check if a property is defined. Note that this will return true for
     * protected properties.
     *
     * @param string $prop The property to check.
     * @return boolean True if set, otherwise false.
     */
    public function __isset($prop) : bool
    {
        return property_exists($this->__state, $prop)
            && !is_null($this->__state->$prop);
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

