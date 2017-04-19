<?php

namespace Ornament\Ornament\Demo;

use Ornament\Ornament\Model;

class DecoratedModel
{
    use Model;

    /**
     * @Test -1
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

