<?php

namespace Ornament\Core;

use StdClass;

abstract class Decorator implements DecoratorInterface
{
    protected $source;

    public function __construct(StdClass $model, string $property, ...$args)
    {
        $this->source =& $model->$property;
    }

    public function getSource()
    {
        return $this->source;
    }

    public function __toString() : string
    {
        return (string)$this->getSource();
    }
}

