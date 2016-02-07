<?php

namespace Ornament;

use zpt\anno\Annotations;
use ReflectionClass;

trait Annotate
{
    /**
     * Helper method to get this object's annotations. The annotations are
     * cached for speed.
     *
     * @return array An array with `class`, `methods` and `properties` entries
     *  containing an Annotations object (for classes) or a hash of
     *  name/Annotations object pairs (for methods/properties).
     * @see zpt\anno\Annotations
     */
    public function annotations()
    {
        static $annotations;
        if (!isset($annotations)) {
            $annotator = get_class($this);
            if (strpos($annotator, '@anonymous')) {
                $annotator = (new ReflectionClass($this))
                    ->getParentClass()->name;
            }
            $reflector = new ReflectionClass($annotator);
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

