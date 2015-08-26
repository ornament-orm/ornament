<?php

namespace Ornament;

use zpt\anno\Annotations;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

trait Storage
{
    /**
     * Generic method to add an Ornament adapter. Specific implementations
     * should generally supply a trait with an addImplementationAdapter that
     * takes care of wrapping the adapter in an Adapter-compatible object.
     *
     * @param Adapter $adapter The Ornament adapter to register.
     * @param string $id The identifier to use (table, endpoint, filename etc.)
     * @param array $fields The members of the object this adapter is valid for.
     *                      Should default to "all known public non-virtual
     *                      members".
     * @return Adapter The registered adapter, for easy chaining.
     */
    protected function addAdapter(Adapter $adapter, $id, array $fields)
    {
        Repository::registerAdapter($this, $adapter, $id, $fields);
        return $adapter;
    }

    /**
     * Internal helper method to get this model's annotations. The annotations
     * are cached for speed.
     *
     * @return array An array with `class`, `methods` and `properties` entries
     *               containing an Annotations object (for classes) or a hahsh
     *               of name/Annotations object pairs (for methods/properties).
     * @see zpt\anno\Annotations
     */
    private function annotations()
    {
        static $annotations;
        if (!isset($annotations)) {
            $reflector = new ReflectionClass($this);
            $annotations['class'] = new Annotations($reflector);
            $annotations['methods'] = [];
            foreach ($reflector->getMethods() as $method) {
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

    /**
     * (Re)loads the current model based on the specified adapters.
     * Optionally also calls methods annotated with @onLoad.
     *
     * @param bool $includeBase If set to true, loads the base model; if false,
     *                          only (re)loads linked models. Defaults to true.
     * @return void
     */
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

    /**
     * Query this model for instances of itself matching $parameters and $opts,
     * optionally instantiad using $ctor arguments.
     *
     * The possible values of $parameters and $opts are dependent on the
     * adapter's implementation. Do note that they are passed verbatim to any
     * (sub)adapters, so custom adapters are encouraged to adhere to the
     * following rules:
     * - $parameters is a one-dimensional hash of key/value pairs to match;
     * - $opts is a one-dimensional hash of options. To limit the number of
     *   results, use ['limit' => $number]. To offset the results, use
     *   ['offset' => $number]. To order the results (if the adapter supports
     *   that; Pdo does but maybe some API's don't) use ['order' => $bywhat].
     * It's okay for an adapter to support more extensive parameters or
     * additional options, but they'll likely be silently dropped by other
     * adapters in use.
     *
     * @param array $parameters Key/value pair of parameters (e.g. ['id' => 1]).
     * @param array $opts Key/value pair of options.
     * @param array $ctor Optional constructor arguments.
     * @return Ornament\Collection An Ornament\Collection of models found (which
     *                             might be empty of course) of type __CLASS__.
     */
    public function query(array $parameters, array $opts = [], array $ctor = [])
    {
        $annotations = $this->annotations();
        $adapters = Repository::getAdapters($this);
        $errors = [];
        foreach ($adapters as $model) {
            return new Collection($model->query($this, $parameters));
        }
    }

    /**
     * Identical to Ornament\Storage::query, except that it returns the first
     * model found instead of an Ornament\Collection.
     *
     * @param array $parameters Key/value pair of parameters (e.g. ['id' => 1]).
     * @param array $opts Key/value pair of options.
     * @param array $ctor Optional constructor arguments.
     * @return mixed A model of type __CLASS__, or false on failure.
     * @see Ornament\Storage::query
     */
    public function find(array $parameters, array $opts = [], array $ctor = [])
    {
        if ($res = $this->query($parameters, $opts, $ctor)) {
            return $res[0];
        }
        return false;
    }
    
    /**
     * Persists the model back to storage based on the specified adapters.
     * If an adapter supports transactions, you are encouraged to use them;
     * but you should do so in your own code.
     *
     * @return null|array null on success, or an array of errors encountered.
     */
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
                    if ($error = $value->save()) {
                        $errors[] = $error;
                    }
                }
            } elseif (is_object($value) && $value instanceof Collection) {
                if ($value->dirty()) {
                    if ($errs = $value->save()) {
                        $errors = array_merge($errors, $errs);
                    }
                }
            } elseif (is_array($value)) {
                foreach ($this->$prop as $index => $model) {
                    if (is_object($model) && Helper::isModel($model)) {
                        $model->__index($index);
                        if (!method_exists($model, 'isDirty')
                            || $model->isDirty()
                        ) {
                            if ($error = $model->save()) {
                                $errors[] = $error;
                            }
                        }
                    }
                }
            }
        }
        $this->markClean();
        return $errors ? $errors : null;
    }

    /**
     * Deletes the current model from storage based on the specified adapters.
     * If an adapter supports transactions, you are encouraged to use them;
     * but you should do so in your own code.
     *
     * @return null|array null on success, or an array of errors encountered.
     */
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

    /**
     * Mark the current model as 'clean', i.e. not dirty. Useful if you manually
     * set values after loading from storage that shouldn't count towards
     * "dirtiness". Called automatically after saving.
     *
     * @return void
     */
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

    /**
     * Overloaded getter. All properties on a model are exposed this way,
     * _unless_ their name starts with an underscore. Hence, protected or
     * private properties are read-only by default.
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
        if (property_exists($this, $prop) && $prop{0} != '_') {
            return $this->$prop;
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
     *         immutable.
     */
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

    /**
     * Check if a property is defined, but not public or virtual. Note that it
     * will return true if a property _is_ defined, but has a value of null.
     *
     * @param string $prop The property to check.
     * @return boolean True if set, otherwise false.
     */
    public function __isset($prop)
    {
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
     * You'll want to specify a custom implementation for this. For models in an
     * array (on another model, of course) it is called with the current index.
     *
     * @param integer $index The current index in the array.
     * @return void
     */
    public function __index($index)
    {
    }
}

