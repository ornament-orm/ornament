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

class DecoratedModel
{
    use Model;

    /**
     * @var Ornament\Demo\SubtractOne
     * @param -1
     */
    public $field;

    /**
     * @Test 2
     * @AnotherTest
     */
    public $two_decorators;

    /**
     * @Decorate Test
     */
    private function decorateTest($value, $modifier = 1)
    {
        return $value + $modifier;
    }

    /**
     * @Decorate AnotherTest
     */
    private function decorateAnother($value)
    {
        return $value + 1;
    }
}

