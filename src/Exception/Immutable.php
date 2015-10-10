<?php

namespace Ornament\Exception;

use Exception;

class Immutable extends Exception
{
    public function __construct($model, $code = 0, Ex $prev = null)
    {
        $message = sprintf(
            'Models of class %s are marked Immutable',
            get_class($model)
        );
        parent::__construct($message, $code, $prev);
    }
}

