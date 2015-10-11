<?php

namespace Ornament;

use JsonSerializable;
use ReflectionClass;
use StdClass;

trait JsonModel
{
    /**
     * Returns a hash of properties/values suitable for Json serialization.
     *
     * @return array Json serializable array representation of this model.
     */
    public function jsonSerialize()
    {
        static $reflected, $callbacks;
        if (!isset($reflected, $callbacks)) {
            $annotations = $this->annotations();
            $refclass = new ReflectionClass($this);
            $reflected = [];
            $callbacks = [];
            foreach ($refclass->getProperties() as $prop) {
                if ($prop->isStatic()) {
                    continue;
                }
                if (!$prop->isPublic()) {
                    if (isset(
                        $annotations['properties'][$prop->getName()],
                        $annotations['properties'][$prop->getName()]['Private']
                    )) {
                        continue;
                    }
                }
                $reflected[] = $prop->getName();
            }
            foreach ($refclass->getMethods() as $method) {
                if (preg_match('@^get[A-Z]@', $method->getName())) {
                    $name = Helper::normalize(preg_replace(
                        '@^get@',
                        '',
                        $method->getName()
                    ));
                    $callbacks[$name] = $method->getName();
                }
            }
        }
        $json = new StdClass;
        foreach ($reflected as $prop) {
            $json->$prop = $this->$prop;
        }
        foreach ($callbacks as $name => $callback) {
            $json->$name = call_user_func([$this, $callback]);
        }
        foreach ($json as &$value) {
            if (is_object($value) && $value instanceof JsonSerializable) {
                $value = $value->jsonSerialize();
            }
        }
        return $json;
    }
}

