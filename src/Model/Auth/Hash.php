<?php

namespace monolyth\model;

abstract class Auth_Hash extends Auth
{
    public static function get()
    {
        $hashes = hash_algos();
        foreach (['whirlpool', 'sha512', 'sha1', 'md5'] as $hash) {
            if (in_array($hash, $hashes)) {
                return $hash;
            }
        }
        return null;
    }
}

