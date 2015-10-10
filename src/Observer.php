<?php

namespace Ornament;

use SplSubject;
use ReflectionClass;
use ReflectionMethod;
use zpt\anno\Annotations;

trait Observer
{
    public function update(SplSubject $subject)
    {
        $ref = new ReflectionClass($this);
        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $args = $method->getParameters();
            if (count($args) != 1 || $method->getName() == 'update') {
                continue;
            }
            if ($hinted = $args[0]->getClass()
                and $subject instanceof $hinted->name
            ) {
                $fn = $method->getName();
                $annotations = new Annotations($method);
                if (isset($annotations['Blind'])) {
                    continue;
                }
                if (isset($annotations['NotifyForState'])) {
                    $states = [];
                    if (is_array($annotations['NotifyForState'])) {
                        $states = $annotations['NotifyForState'];
                    } else {
                        $states[] = $annotations['NotifyForState'];
                    }
                    if (!in_array($subject->state(), $states)) {
                        continue;
                    }
                }
                $this->$fn($subject);
            }
        }
    }
}

