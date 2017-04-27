<?php

namespace Ornament\Demo;

use Ornament\Core\Model;
use Ornament\Core\Decorator;

class SubtractOne implements Decorator
{
    public function __construct($value)
    {
        $this->value = $value;
    }

    public function getSource() : int
    {
        return $this->value - 1;
    }

    public function __toString() : string
    {
        return (string)$this->getSource();
    }
}

class DecoratorWithConstructorArguments implements Decorator
{
    public function __construct($value, int $one, int $two)
    {
        $this->value = $value;
        $this->one = $one;
        $this->two = $two;
    }

    public function getSource() : int
    {
        return $this->value * $this->one * $this->two;
    }

    public function __toString() : string
    {
        return (string)$this->getSource();
    }
}

class DecoratedModel
{
    use Model;

    /**
     * @var Ornament\Demo\SubtractOne
     * @param -1
     */
    public $field;

    /**
     * @var Ornament\Demo\DecoratorWithConstructorArguments
     * @construct 2
     * @construct 3
     */
    public $anotherField;
}

