<?php

namespace Ornament;

use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

abstract class Repository
{
    private static $adapters = [];
    private static $cleanModels = [];
    private static $reflected = [];

    public static function registerAdapter($obj, $adapter, $id, array $fields)
    {
        self::markClean($obj);
        $key = spl_object_hash($obj);
        if (!isset(self::$adapters[$key])) {
            self::$adapters[$key] = [];
        }
        $adapter_key = spl_object_hash($adapter)."#$id";
        $model = new Model($adapter);
        $new = true;
        foreach ($fields as $field) {
            if (isset($obj->$field)) {
                $new = false;
            }
            $model->$field =& $obj->$field;
        }
        if ($new) {
            $model->markNew();
        } else {
            $model->markClean();
        }
        self::$adapters[$key][$adapter_key] = $model;
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
            $data[$prop] = $obj->$prop;
        }
        return $data;
    }

    public static function getProperties($obj)
    {
        $class = get_class($obj);
        if (!isset(self::$reflected[$class])) {
            $reflected = new ReflectionClass($obj);
            self::$reflected[$class] = [];
            foreach ($reflected->getProperties(
                ReflectionProperty::IS_PUBLIC
            ) as $prop) {
                self::$reflected[$class][] = $prop->getName();
            }
            foreach ($reflected->getMethods(
                ReflectionMethod::IS_PUBLIC
            ) as $method) {
                if (preg_match('@^[gs]et@', $method->getName())) {
                    self::$reflected[$class][] = Helper::normalize(preg_replace(
                        '@^[gs]et@',
                        '',
                        $method->getName()
                    ));
                }
            }
            self::$reflected[$class] = array_unique(self::$reflected[$class]);
        }
        return self::$reflected[$class];
    }
}

