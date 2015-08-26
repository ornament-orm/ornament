<?php

namespace Ornament\Exception;

use Exception as Ex;

class PrimaryKey extends Ex
{
    public function __construct($model, $field, $code = 0, Ex $prev = null)
    {
        $message = sprintf(
            'Missing primary key %s on model %s',
            $field,
            get_class($model)
        );
        parent::__construct($message, $code, $prev);
    }
}

