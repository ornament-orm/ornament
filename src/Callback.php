<?php

namespace Ornament;

trait Callback
{
    private static $callbacks = [];

    private function callback($fn, $args = null)
    {
        if (is_callable($args)) {
            self::$callbacks[$fn] = $args;
            return;
        }
        if (!isset(self::$callbacks[$fn])) {
            throw new Exception\UndefinedCallback;
        }
        if (!isset($args)) {
            // Just checking...
            return true;
        }
        return call_user_func_array(self::$callbacks[$fn], $args);
    }

    public function __call($fn, array $args = [])
    {
        return $this->callback($fn, $args);
    }

    private function listVirtualCallbackProperties()
    {
        $props = [];
        foreach (self::$callbacks as $name => $value) {
            $props[] = Helper::normalize(substr($name, 3));
        }
        return $props;
    }
}

