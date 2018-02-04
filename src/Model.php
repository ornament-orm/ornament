<?php

namespace Ornament\Core;

use zpt\anno\Annotations;
use ReflectionClass;
use ReflectionProperty;
use SplObjectStorage;
use StdClass;
use Error;

/**
 * `use` this trait to turn any vanilla class into an Ornament model.
 */
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

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct()
    {
        $this->__ornamentalize();
    }

    /**
     * Setup the model for decoration using Ornament. Typically called
     * automatically on construction, but for manual implementations (e.g., a
     * non-PDO environment) call manually when appropriate.
     *
     * @return array Hash of Ornament annotations for this class.
     */
    private function __ornamentalize() : array
    {
        static $cache = [];
        $class = get_called_class();
        if (!isset($cache[$class])) {
            $cache[$class] = [];
            $annotator = get_class($this);
            $reflector = new ReflectionClass($annotator);
            $cache[$class]['class'] = new Annotations($reflector);
            $cache[$class]['methods'] = [];
            foreach ($reflector->getMethods() as $method) {
                $anns = new Annotations($method);
                $name = $method->getName();
                $cache[$class]['methods'][$name] = $anns;
            }
            $properties = $reflector->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED & ~ReflectionProperty::IS_STATIC);
            $cache[$class]['properties'] = [];
            foreach ($properties as $property) {
                $name = $property->getName();
                $anns = new Annotations($property);
                $anns['readOnly'] = $property->isProtected();
                $cache[$class]['properties'][$name] = $anns;
            }
        }
        if (!isset($this->__state, $this->__initial)) {
            $this->__state = new StdClass;
            $this->__initial = new StdClass;
            foreach ($cache[$class]['properties'] as $name => $anns) {
                $this->__initial->$name = $this->$name;
                $this->__state->$name = $this->$name;
                unset($this->$name);
            }
        }
        return $cache[$class];
    }

    /**
     * Overloaded getter. All public and protected properties on a model are
     * exposed this way. Non-public properties are read-only.
     *
     * If a method was specified as `@get` for this property, its return value
     * is used instead. If the property has a `@var` annotation _and_ it is an
     * instance of `Ornament\Core\DecoratorInterface`, the corresponding
     * decorator class is initialised with the property's current value and
     * returned instead.
     *
     * @param string $prop Name of the property.
     * @return mixed The property's (optionally computed) value.
     * @throws Error if the property is unknown.
     */
    public function __get(string $prop)
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
                    $debug['file'] ?? 'unknown',
                    $debug['line'] ?? 'unknown'
                ),
                0
            );
        }
        if (isset($annotations['properties'][$prop]['var'])
            && class_exists($annotations['properties'][$prop]['var'])
            && array_key_exists(
                'Ornament\Core\DecoratorInterface',
                class_implements($annotations['properties'][$prop]['var'])
            )
            && !($this->__state->$prop instanceof $annotations['properties'][$prop]['var'])
        ) {
            $class = $annotations['properties'][$prop]['var'];
            $args = [];
            if (isset($annotations['properties'][$prop]['construct'])) {
                $args = is_array($annotations['properties'][$prop]['construct'])
                    && isset($annotations['properties'][$prop]['construct'][0])
                    && count($annotations['properties'][$prop]['construct']) > 1
                    ? $annotations['properties'][$prop]['construct']
                    : [$annotations['properties'][$prop]['construct']];
            }
            $this->__state->$prop = new $class($this->__state, $prop, ...$args);
        }
        if ($this->checkBaseType($annotations['properties'][$prop]) && !is_null($this->__state->$prop)) {
            settype($this->__state->$prop, $annotations['properties'][$prop]['var']);
        }
        return $this->__state->$prop;
    }

    /**
     * Overloaded setter. Will only work for public properties (since protected
     * properties are read-only).
     *
     * If the value supplied is an instance of the `@var` annotation _and_ this
     * is an instance of `Ornament\Core\DecoratorInterface`, it is first
     * converted to the result of `getSource()`. Next, if a method was specified
     * as `@set` for this property, it is called with the supplied value as a
     * single argument and its return value is used.
     *
     * Otherwise, the corresponding property in `__state` is simply mutated. If
     * an `@var` annotation is given with a scalar type, the type is coerced.
     *
     * @param string $prop The property to set.
     * @param mixed $value The new value.
     * @return void
     * @throws Error if the property is private, unknown or read-only.
    */
    public function __set(string $prop, $value)
    {
        if (!property_exists($this->__state, $prop)) {
            $debug = debug_backtrace()[0];
            throw new Error(
                sprintf(
                    "Cannot access private or unknown property %s::%s in %s:%d",
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
            // Modifying a readOnly (protected) property is only valid from
            // within the same class context. This is a hacky way to check
            // that, since `__set` obviously by default means "inside class
            // context".
            $debugs = debug_backtrace();
            do {
                $debug = array_shift($debugs);
            } while ($debug['function'] == '__set' && $debugs);

            $error = function () use ($debugs, $prop) {
                $debug = $debugs[1] ?? $debugs[0];
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
            };

            // No class context? That's definitely illegal.
            if (!isset($debug['class'])) {
                $error();
            }

            // Is the calling class context not either ourselves, or a subclass?
            // That's also illegal.
            $reflection = new ReflectionClass($debug['class']);
            $myclass = get_class($this);
            if (!($reflection->getName() == $myclass || $reflection->isSubclassOf($myclass))) {
                $error();
            }
        }
        if (isset($annotations['properties'][$prop]['var'])
            && class_exists($annotations['properties'][$prop]['var'])
            && array_key_exists(
                'Ornament\Core\DecoratorInterface',
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
        if ($this->checkBaseType($annotations['properties'][$prop]) && !is_null($value)) {
            settype($value, $annotations['properties'][$prop]['var']);
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
    public function __isset(string $prop) : bool
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

    /**
     * Internal helper method to check if the given property is annotated as one
     * of PHP's internal base types (int, float etc).
     *
     * @param zpt\anno\Annotations $prop
     * @return bool
     */
    protected function checkBaseType(Annotations $prop) : bool
    {
        static $baseTypes = ['bool', 'int', 'float', 'string', 'array', 'object', 'null'];
        return in_array($prop['var'] ?? null, $baseTypes);
    }
}

