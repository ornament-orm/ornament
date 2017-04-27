<?php

namespace Ornament\Demo;

use Ornament\Core\Decorator;
use StdClass;

class DecoratorWithConstructorArguments extends Decorator
{
    public function __construct(StdClass $model, string $property, int $one, int $two)
    {
        parent::__construct($model, $property);
        $this->one = $one;
        $this->two = $two;
    }

    public function getSource() : int
    {
        return $this->source * $this->one * $this->two;
    }
}

