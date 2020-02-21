<?php

namespace Ornament\Core;

use DomainException;

class PropertyNotDefinedException extends DomainException
{
    public function __construct(string $class, string $property)
    {
        parent::__construct("Class $class does not define $property");
    }
}

