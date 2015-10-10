<?php

namespace Ornament;

trait Bitflag
{
    use Callback;

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

