<?php

namespace Ornament\Demo;

use Ornament\Core\Model;

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

