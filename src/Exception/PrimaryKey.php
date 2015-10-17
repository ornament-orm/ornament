<?php

namespace Ornament\Exception;

use Exception as Ex;

class PrimaryKey extends Ex
{
    public function __construct($identifier, $field, $code = 0, Ex $prev = null)
    {
        $message = sprintf(
            'Missing primary key %s on model with identifier %s',
            $field,
            $identifier
        );
        parent::__construct($message, $code, $prev);
    }
}

