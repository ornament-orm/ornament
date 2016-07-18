<?php

namespace Ornament;

use JsonSerializable;
use StdClass;

/**
 * Object to emulate a bitflag in Ornament models.
 *
 * For a model Foo with a property 'status', we often want to define a number of
 * bitflags, e.g. 'status_on = 1', 'status_valid = 2' etc. The Bitflag trait
 * makes this easy.
 *
 * Annotate the bitflag property with @Bitflag followed by a map of names/flags,
 * e.g. @Bitflag on = 1, valid = 2
 *
 * <code>
 * // Now this works, assuming `$model` is the instance:
 * var_dump($model->status->on); // true or false, depending
 * $model->status->on = true; // bit 1 is now on (status |= 1)
 * $model->status->on = false; // bit 1 is now off (status &= ~1)
 * </code>
 */
class Bitflag implements JsonSerializable
{
    /** Private storage for the actual intager value. */
    private $source;
    /** Private storage of the name/bitvalue map for this object. */
    private $map;

    /**
     * Constructor. Normally called by models based on the @Bitflag annotation,
     * but you can also construct manually.
     *
     * @param integer $source The initial value of the byte storing the
     *  bitflags.
     * @param array $valueMap Key/value pair of bit names/values, e.g. "on" =>
     *  1, "female" => 2 etc.
     */
    public function __construct($source, array $valueMap = [])
    {
        $this->source = (int)"$source";
        $this->map = $valueMap;
    }

    /**
     * Magic setter. Silently fails if the specified property was not available
     * in the $valueMap used during construction.
     *
     * @param string $prop Name of the bit to set.
     * @param mixed $value Truthy to turn on, falsy to turn off.
     */
    public function __set($prop, $value)
    {
        if (!isset($this->map[$prop])) {
            return;
        }
        if ($value) {
            $this->source |= $this->map[$prop];
        } else {
            $this->source &= ~$this->map[$prop];
        }
    }

    /**
     * Magic getter to retrieve the status of a bit.
     *
     * @param string $prop Name of the bit to check.
     * @return boolen True if the bit is on, false if off or unknown.
     */
    public function __get($prop)
    {
        if (!isset($this->map[$prop])) {
            return false;
        }
        return (bool)($this->source & $this->map[$prop]);
    }

    /**
     * Check if a bit exists in this bitflag.
     *
     * @param string $prop Name of the bit to check.
     * @return boolean True if the bit is known in this bitflag, false
     *  otherwise.
     */
    public function __isset($prop)
    {
        return isset($this->map[$prop]);
    }

    /**
     * Return the original source byte as a string.
     *
     * @return string Integer casted to string containing the current value.
     */
    public function __toString()
    {
        return (string)$this->source;
    }

    /**
     * Export this bitflag as a Json object. All known bits are exported as
     * properties with true or false depending on their status.
     *
     * @return StdClass A standard class suitable for json_encode.
     */
    public function jsonSerialize()
    {
        $ret = new StdClass;
        foreach ($this->map as $key => $value) {
            $ret->$key = (bool)($this->source & $value);
        }
        return $ret;
    }

    /**
     * Set all flags to "off". Useful for reinitialization.
     *
     * @return void
     */
    public function allOff()
    {
        $this->source = 0;
    }
}

