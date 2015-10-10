<?php

namespace Ornament;

use SplSubject;
use ReflectionClass;
use ReflectionMethod;

trait Observer
{
    public function update(SplSubject $subject)
    {
        static $reflection;
        if (!isset($reflection)) {
            $ref = new ReflectionClass($this);
            foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $args = $method->getParameters();
                if (count($args) != 1 || $method->getName() == 'update') {
                    continue;
                }
                if ($hinted = $args[0]->getClass()
                    and $subject instanceof $hinted->name
                ) {
                    call_user_func([$this, $method->getName()], $subject);
                }
            }
        }
    }
}

