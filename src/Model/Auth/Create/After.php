<?php

namespace monolyth\model;

abstract class Auth_Create_After extends Auth
{
    protected static function __invoke($fields, $data)
    {
        return Auth::requestActivate($data);
    }
}

