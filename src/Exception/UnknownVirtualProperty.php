<?php

namespace Ornament\Exception;

class UnknownVirtualProperty extends \Exception
{
    public function __construct($prop, $object, Exception $prev = null)
    {
        $message = sprintf(
            'Unknown virtual property %s on %s',
            $prop,
            get_class($object)
        );
        parent::__construct($message, 0, $prev);
    }
}

