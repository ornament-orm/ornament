<?php

/**
 * Monolyth Models are data stores primarily intended to work like array in
 * access ($model['property']), whilst supporting certain method calls (to be
 * defined by specific implementations).
 *
 * Models are NOT intended to function as an ORM (though they could of course be
 * implemented in such a fashion). Hence, an object of class Foo does NOT
 * necessarily represent a row in table foo. Following from this, the default
 * base Model is actually something of an empty shell; there are no default
 * save/update/delete-type methods.
 *
 * Models work in conjunction with Finders, which return arrays of data (which
 * may or may not already contain Models; for most purposes, a call to
 * $adapter->rows would suffice since action methods generally only get called
 * on single instances of models, not on a whole bunch at the same time).
 * A Finder's find method, conversely, generally DOES return a Model object.
 *
 * @package Ornament
 * @author Marijn Ophorst <marijn@monomelodies.nl>
 * @copyright MonoMelodies 2008, 2009, 2010, 2011, 2012, 2014, 2015
 */
namespace monolyth\core;
use ArrayObject;
use Adapter_Access;

/**
 * The base Model class. This defines a few universal helper methods, and
 * further can be useful in type-hinting. Aditionally, it provides a default
 * framework for implementing observer patterns.
 */
abstract class Model extends ArrayObject
{
    use Adapter_Access;

    private $_new = true,
            $_orig = [],
            $_after = [],
            $_observers = [];

    /**
     * Constructor. When instantiated with data directly using PDO, this
     * flags if the model was or was not initially filled with data.
     */
    public function __construct($adapter = '_current')
    {
        $class = get_class($this);
        $this->_orig = [];
        $this->_after = self::describe($this);
        if ($this->_orig != $this->_after) {
            $this->_new = false;
        }
        $this->adapter = self::adapter($adapter);
    }

    /**
     * Internal helper-method to get native object properties.
     *
     * @param mixed $what Classname or actual object.
     * @return array Array of native properties (i.e., not added by loading).
     */
    private static function describe($what)
    {
        $fn = is_string($what) ? 'get_class_vars' : 'get_object_vars';
        return array_keys($fn($what));
    }

    /**
     * Populate the Model with data.
     *
     * @param array $data Key/value pairs of data (e.g., a database row).
     * @return Model Returns itself.
     */
    public function load(array $data)
    {
        $this->_new = false;
        foreach ($data as $name => $value) {
            $this[$name] = $value;
        }
        $this->_orig = $data;
        return $this;
    }

    /**
     * Get an array of fields that were changed since the initial loading.
     *
     * @return mixed Array of dirty fields, or null if not dirty.
     */
    protected function getDirtyFields()
    {
        $fields = [];
        foreach ((array)$this as $key => $value) {
            if (!isset($this->_orig[$key]) || $value != $this->_orig[$key]) {
                $fields[$key] = $value;
            }
        }
        return $fields ? $fields : null;
    }

    
    /**
     * Check if the Model was changed since initial loading.
     *
     * @return bool True if the Model is changed ('dirty'), else false.
     */
    protected function isDirty()
    {
        foreach ((array)$this as $key => $value) {
            if (!isset($this->_orig[$key]) || $value != $this->_orig[$key]) {
                return true;
            }
        }
        return false;
    }

    /**
     * Convert the Model to an actual array. The returned array excludes
     * any native properties (i.e., it contains solely the data).
     *
     * @return array Array of key/value pairs.
     */
    protected function toArray()
    {
        $vars = array_diff($this->_after, $this->_orig);
        $return = [];
        if (!$vars) {
            foreach ($this as $key => $value) {
                if (!in_array($key, $this->_orig)) {
                    $return[$key] = $value;
                }
            }
        } else {
            foreach ($vars as $key) {
                $return[$key] = $this->$key;
            }
        }
        return $return;
    }

    /**
     * Check if the load method was called on this Model.
     *
     * @return bool True if it has been called, else false.
     */
    public function isLoaded()
    {
        return $this->_new == false;
    }
}

