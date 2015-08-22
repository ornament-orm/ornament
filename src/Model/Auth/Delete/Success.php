<?php

namespace monolyth\model;

abstract class Auth_Delete_Success extends Auth
{
    public static function after($id)
    {
        return null;
    }
}

