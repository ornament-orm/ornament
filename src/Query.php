<?php

namespace Ornament;

use ReflectionClass;

trait Query
{
    /**
     * Query this model for instances of itself matching $parameters and $opts,
     * optionally instantiad using $ctor arguments.
     *
     * The possible values of $parameters and $opts are dependent on the
     * adapter's implementation. Do note that they are passed verbatim to any
     * (sub)adapters, so custom adapters are encouraged to adhere to the
     * following rules:
     * - $parameters is a one-dimensional hash of key/value pairs to match;
     * - $opts is a one-dimensional hash of options. To limit the number of
     *   results, use ['limit' => $number]. To offset the results, use
     *   ['offset' => $number]. To order the results (if the adapter supports
     *   that; Pdo does but maybe some API's don't) use ['order' => $bywhat].
     * It's okay for an adapter to support more extensive parameters or
     * additional options, but they'll likely be silently dropped by other
     * adapters in use.
     *
     * @param mixed $where Something your adapter can use as a filter.
     * @param array $opts Key/value pair of options.
     * @param array $ctor Optional constructor arguments.
     * @return Ornament\Collection|null An Ornament\Collection of models found
     *  of course) of type __CLASS__, or null if nothing matched.
     */
    public static function query($where, array $opts = [], array $ctor = [])
    {
        $instance = self::modelInstance($ctor);
        $annotations = $instance->annotations();
        $errors = [];
        foreach ($instance->__adapters as $model) {
            $result = $model->query($instance, $where, $opts, $ctor);
            if ($result) {
                return new Collection($result);
            }
        }
        return null;
    }

    /**
     * Identical to Ornament\Storage::query, except that it returns the first
     * model found instead of an Ornament\Collection.
     *
     * @param mixed $where Something your adapter can use as a filter.
     * @param array $opts Key/value pair of options.
     * @param array $ctor Optional constructor arguments.
     * @return mixed A model of type __CLASS__, or false on failure.
     * @see Ornament\Storage::query
     */
    public static function find($where, array $opts = [], array $ctor = [])
    {
        if ($res = self::query($where, $opts, $ctor)) {
            foreach ($res as $result) {
                $result->load(false);
                return $result;
            }
        }
        return false;
    }

    /**
     * Internal helper to retrieve a cached, anonymous instance of the actual
     * model to work on. Needed to ensure dependencies are properly injected
     * etc.
     *
     * @param array $ctor Optional constructor arguments.
     * @return object An instance of the Model being queried.
     */
    private static function modelInstance(array $ctor = [])
    {
        static $cached;
        if (!isset($cached)) {
            $class = new ReflectionClass(get_called_class());
            while ($class->isAbstract()) {
                $class = $class->getParentClass();
            }
            $cached = $class->newInstanceArgs($ctor);
        }
        return $cached;
    }
}

