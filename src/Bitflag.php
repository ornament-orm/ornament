<?php

namespace Ornament;

/**
 * Trait to quickly add bitflags to a model.
 *
 * For a model Foo with a property 'status', we often want to define a number of
 * bitflags, e.g. 'status_on = 1', 'status_valid = 2' etc. The Bitflag trait
 * makes this easy:
 *
 * <code>
 * $this->addBitflag('on', 1, 'status');
 * // Now this works, assuming `$model` is the instance:
 * var_dump($model->on); // true or false, depending
 * $model->on = true; // bit 1 is now on (status |= 1)
 * $model->on = false; // bit 1 is now off (status &= ~1)
 */
trait Bitflag
{
    use Callback;

    /**
     * Define a bitflag.
     *
     * @param string $name The name of the flag/property.
     * @param integer $bit The bit to flip.
     * @param string $source The property name of the source byte on the model. 
     */
    public function addBitflag($name, $bit, $source)
    {
        $name = ucfirst(Helper::denormalize($name));
        $this->callback("get$name", function () use ($bit, $source) {
            return (bool)($this->$source & $bit);
        });
        $this->callback("set$name", function ($val) use ($bit, $source) {
            if ($val) {
                $this->$source |= $bit;
            } else {
                $this->$source &= ~$bit;
            }
        });
    }
}

