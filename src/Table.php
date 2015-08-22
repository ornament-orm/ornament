<?php

namespace Ornament;

trait Table
{
    public function guessTableName(callable $fn = null)
    {
        static $guesser;
        static $table;
        if (isset($table)) {
            return $table;
        }
        if (isset($fn)) {
            $guesser = $fn;
        }
        if (!isset($guesser)) {
            $guesser = function ($class) {
                $class = preg_replace('@\\\\?Model$@', '', $class);
                return Helper::normalize($class);
            };
        }
        return $guesser(get_class($this));
    }
}

