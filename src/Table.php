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
                $table = strtolower(preg_replace_callback(
                    '@([a-z0-9])(_|\\\\)?([A-Z])@',
                    function ($match) {
                        return $match[1].'_'.strtolower($match[3]);
                    },
                    $class
                ));
                return trim(strtolower($table), '_');
            };
        }
        return $guesser(get_class($this));
    }
}

