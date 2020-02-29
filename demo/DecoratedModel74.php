<?php

namespace Ornament\Demo;

use Ornament\Core\Model;

class DecoratedModel74
{
    use Model;

    /**
     * @var Ornament\Demo\SubtractOne
     */
    public SubtractOne $field;

    /**
     * @get virtual_property
     * @return string
     */
    protected function getVirtualPropertyDemo() : string
    {
        return $this->field;
    }
}

