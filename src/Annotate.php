<?php

namespace Ornament;

use zpt\anno\Annotations;
use ReflectionClass;

trait Annotate
{
    /**
     * Internal helper method to get this object's annotations. The annotations
     * are cached for speed.
     *
     * @return array An array with `class`, `methods` and `properties` entries
     *  containing an Annotations object (for classes) or a hash of
     *  name/Annotations object pairs (for methods/properties).
     * @see zpt\anno\Annotations
     */
    private function annotations()
    {
        static $annotations;
        if (!isset($annotations)) {
            $reflector = new ReflectionClass($this);
            $annotations['class'] = new Annotations($reflector);
            $annotations['methods'] = [];
            foreach ($reflector->getMethods() as $method) {
                $annotations['methods'][$method->getName()]
                    = new Annotations($method);
            }
            foreach ($reflector->getProperties() as $property) {
                $annotations['properties'][$property->getName()]
                    = new Annotations($property);
            }
        }
        return $annotations;
    }
}

