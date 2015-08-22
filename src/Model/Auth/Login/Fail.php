<?php

namespace monolyth\model;

abstract class Auth_Login_Fail extends Auth
{
    public static function __invoke($form)
    {
        return null;
    }
}

