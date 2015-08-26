<?php

namespace Ornament;

trait Callback
{
    private function callback($fn, $args = null)
    {
        static $callbacks = [];
        if (is_callable($args)) {
            $callbacks[$fn] = $args;
            return;
        }
        if (!isset($callbacks[$fn])) {
            throw new Exception\UndefinedCallback;
        }
        if (!isset($args)) {
            // Just checking...
            return true;
        }
        return call_user_func_array($callbacks[$fn], $args);
    }

    public function __call($fn, array $args = [])
    {
        return $this->callback($fn, $args);
    }
}

