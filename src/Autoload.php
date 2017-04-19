<?php

namespace Ornament\Ornament;

use ReflectionClass;

trait Autoload
{
    /** @onLoad */
    public function autoload(array $annotations)
    {
        foreach ($annotations as $property => $anns) {
            if (isset($anns['Model'])) {
                $class = $anns['Model'];
                if (isset($anns['Mapping'])) {
                    $maps = $anns['Mapping'];
                } else {
                    $maps = ['id' => $property];
                }
                $ctorargs = [];
                if (isset($anns['Constructor'])) {
                    foreach ($anns['Constructor'] as $arg) {
                        $ctorargs[$arg] = $this->$arg;
                    }
                }
                $ref = new ReflectionClass($class);
                $model = $ref->newInstanceArgs($ctorargs);
                if (!(is_array($this->$property)
                    || is_object($this->$property)
                        and $this->$property instanceof Collection
                )) {
                    foreach ($maps as $field => $mapto) {
                        $model->$field = $this->$mapto;
                    }
                    try {
                        $model->load();
                    } catch (Exception\PrimaryKey $e) {
                    }
                    $this->$property = $model;
                } else {
                    $args = [];
                    foreach ($maps as $key => $arg) {
                        $args[$key] = $this->$arg;
                    }
                    $options = isset($anns['Options']) ? $anns['Options'] : [];
                    $collection = $class::query($args, $options);
                    $this->$property = $collection ?:
                        new Collection([], $this, $maps);
                }
            }
        }
    }
}

