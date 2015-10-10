<?php

namespace Ornament\Exception;

use Exception;

class Undeleteable extends Exception
{
    public function __construct($model, $code = 0, Ex $prev = null)
    {
        $message = sprintf(
            'Models of class %s are marked Undeleteable',
            get_class($model)
        );
        parent::__construct($message, $code, $prev);
    }
}

