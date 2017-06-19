<?php

namespace Ornament\Core;

use DomainException;

trait ModelCheck
{
    private function __ornamentCheck()
    {
        if (!isset($this->__initial, $this->__state)) {
            throw new DomainException(sprintf(
                "%s is not an Ornament model.",
                get_class($this)
            ));
        }
    }
}

