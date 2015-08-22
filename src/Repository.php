<?php

namespace Ornament;

use ReflectionClass;
use ReflectionProperty;

abstract class Repository
{
    private static $adapters = [];
    private static $cleanModels = [];
    private static $reflected = [];

    public static function registerAdapter($obj, $adapter)
    {
        self::markClean($obj);
        $key = spl_object_hash($obj);
        if (!isset(self::$adapters[$key])) {
            self::$adapters[$key] = [];
        }
        $adapter_key = spl_object_hash($adapter);
        self::$adapters[$key][$adapter_key] = $adapter;
    }

    public static function getAdapters($obj)
    {
        $key = spl_object_hash($obj);
        if (!isset(self::$adapters[$key])) {
            throw new NoAdaptersRegisteredException($obj);
        }
        return self::$adapters[$key];
    }

    public static function markClean($obj)
    {
        $key = spl_object_hash($obj);
        self::$cleanModels[$key] = self::getModelValues($obj);
    }

    public static function isDirty($obj)
    {
        $key = spl_object_hash($obj);
        return !isset(self::$cleanModels[$key])
            || self::$cleanModels[$key] != self::getModelValues($obj);
    }

    private static function getModelValues($obj)
    {
        $properties = self::getProperties($obj);
        $data = [];
        foreach ($properties as $prop) {
            $name = $prop->getName();
            $data[$name] = $obj->$name;
        }
        return $data;
    }

    public static function getProperties($obj)
    {
        $class = get_class($obj);
        if (!isset(self::$reflected[$class])) {
            $reflected = new ReflectionClass($obj);
            self::$reflected[$class] = $reflected->getProperties(
                ReflectionProperty::IS_PUBLIC
            );
            foreach ($reflected->getMethods(
                ReflectionMethod::IS_PUBLIC
            ) as $method) {
                
            }                
        }
        return self::$reflected[$class];
    }
}

