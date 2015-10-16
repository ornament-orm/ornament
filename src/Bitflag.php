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
    private $source;
    private $map;

    public function __construct($source, array $valueMap = [])
    {
        $this->source = $source;
        $this->map = $valueMap;
    }

    public function __set($prop, $value)
    {
        if ($value) {
            $this->source |= $this->map[$prop];
        } else {
            $this->source &= ~$this->map[$prop];
        }
    }

    public function __get($prop)
    {
        return $this->source & $this->map[$prop];
    }

    public function __isset($prop)
    {
        return isset($this->map[$prop]);
    }

    public function __toString()
    {
        return (string)$this->source;
    }

    public function jsonSerialize()
    {
        $arr = new StdClass;
        foreach ($this->map as $key => $value) {
            $arr->$key = (bool)($this->source & $value);
        }
        return $arr;
    }
}

