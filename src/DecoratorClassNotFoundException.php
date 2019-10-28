<?php

namespace Ornament\Core;

use RuntimeException;

class DecoratorClassNotFoundException extends RuntimeException
{
    public function __construct(string $class)
    {
        parent::__construct("Class $class not found; maybe a spelling error?");
    }
}

