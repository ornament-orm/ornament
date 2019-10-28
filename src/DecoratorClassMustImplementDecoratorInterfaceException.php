<?php

namespace Ornament\Core;

use RuntimeException;

class DecoratorClassMustImplementDecoratorInterfaceException extends RuntimeException
{
    public function __construct(string $class)
    {
        parent::__construct("Class $class does not implement Ornament\\Core\\DecoratorInterface.");
    }
}

