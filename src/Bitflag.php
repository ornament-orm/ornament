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
 * e.g. @Bitglag on = 1, valid = 2
 *
 * <code>
 * // Now this works, assuming `$model` is the instance:
 * var_dump($model->status->on); // true or false, depending
 * $model->status->on = true; // bit 1 is now on (status |= 1)
 * $model->status->on = false; // bit 1 is now off (status &= ~1)
 */
class Bitflag implements JsonSerializable
{
    /** Private storage for the actual intager value. */
    private $source;
    /** Private storage of the name/bitvalue map for this object. */
    private $map;

    /**
     * Constructor.
     *
     * @param integer $source The initial byte value.
     * @param array $valueMap Key/value map of bitnames/bits to use. Normally
     *  passed on construction from an @Bitflag annotation, but can also be set
     *  manually.
     */
    public function __construct($source, array $valueMap = [])
    {
        $this->source = $source;
        $this->map = $valueMap;
    }

    /**
     * Magic setter so "properties" can be set to true or false instead of
     * manually needing to juggle bits.
     *
     * @param string $prop The property to toggle.
     * @param mixed $value Its new status. Anything truthy sets the bit to on
     *  (|=), anything falsy to off (&=~).
     * @return void
     */
    public function __set($prop, $value)
    {
        if ($value) {
            $this->source |= $this->map[$prop];
        } else {
            $this->source &= ~$this->map[$prop];
        }
    }

    /**
     * Magic getter for bits.
     *
     * @param string $prop The name of the flag to check.
     * @return bool True if the bit is currently set, else false.
     */
    public function __get($prop)
    {
        return (bool)($this->source & $this->map[$prop]);
    }

    /**
     * Returns the actual value of the byte as a string.
     *
     * @return string Integer casted as string.
     */
    public function __toString()
    {
        return (string)$this->source;
    }

    /**
     * Exports the bitflag as a JSON-serializable StdClass.
     *
     * @return StdClass Simple object containing the bits with true/false
     *  values.
     */
    public function jsonSerialize()
    {
        $arr = new StdClass;
        foreach ($this->map as $key => $value) {
            $arr->$key = (bool)($this->source & $value);
        }
        return $arr;
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

