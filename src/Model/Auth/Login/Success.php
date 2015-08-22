<?php

namespace monolyth\model;
use monolyth;

abstract class Auth_Login_Success extends Auth
{
    public static function after(monolyth\Controller $controller)
    {
        return null;
    }
}

