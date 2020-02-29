<?php

namespace Ornament\Demo;

use Ornament\Core\Model;

class DecoratedModel
{
    use Model;

    /**
     * @var Ornament\Demo\SubtractOne
     */
    public $field;

    /**
     * @get virtual_property
     * @return string
     */
    protected function getVirtualPropertyDemo() : string
    {
        return $this->field;
    }
}

