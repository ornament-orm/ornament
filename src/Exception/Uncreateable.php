<?php

namespace Ornament\Exception;

use Exception;

class Uncreateable extends Exception
{
    public function __construct($model, $code = 0, Ex $prev = null)
    {
        $message = sprintf(
            'Models of class %s are marked Uncreateable',
            get_class($model)
        );
        parent::__construct($message, $code, $prev);
    }
}

