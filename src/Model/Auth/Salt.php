<?php

namespace monolyth\model;

abstract class Auth_Salt extends Auth
{
    protected static function generate()
    {
        $setting = '255:abcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
        $parts = explode(':', $setting, 2);
        $salt = '';
        for ($i = 0; $i < $parts[0]; $i++) {
            $salt .= substr($parts[1], rand(0, strlen($parts[1]) - 1), 1);
        }
        return $salt;
    }
}

