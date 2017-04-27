<?php

namespace Ornament\Demo;

use Ornament\Core\Decorator;

class DecoratorWithConstructorArguments extends Decorator
{
    public function __construct($value, int $one, int $two)
    {
        parent::__construct($value);
        $this->one = $one;
        $this->two = $two;
    }

    public function getSource() : int
    {
        return $this->source * $this->one * $this->two;
    }
}

