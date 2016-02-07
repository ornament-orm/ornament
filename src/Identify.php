<?php

namespace Ornament;

use ReflectionClass;

/**
 * Helper trait to guesstimate the identifier (table name, API endpoint etc.)
 * for a given model. Mostly useful for PDO-like adapters, but theoretically
 * other adapters could implement similar logic.
 */
trait Identify
{
    /**
     * Return the guesstimated identifier, optionally by using the callback
     * passed as an argument.
     *
     * @param callable $fn Optional callback doing the guesstimating.
     * @return string A guesstimated identifier.
     */
    public function guessIdentifier(callable $fn = null)
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
        $class = get_class($this);
        if (strpos($class, '@anonymous') !== false) {
            $class = (new ReflectionClass($this))->getParentClass()->name;
        }
        return $guesser($class);
    }
}

