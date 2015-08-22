<?php

/**
 * @package monolyth
 * @subpackage model
 */

namespace monolyth\model;
use monolyth;

abstract class Auth_Delete extends Auth
{
    public static function __invoke($id = null)
    {
        if (!isset($id)) {
            $id = monolyth\User::id();
        }
        if ($id == 1) {
            return 'root';
        }
        monolyth\db\DB::beginTransaction();
        try {
            monolyth\db\DB::delete(
                'monolyth_auth',
                compact('id')
            );
            if ($error = Auth::afterSuccessDelete($id)) {
                monolyth\db\DB::rollback();
                return $error;
            }
            monolyth\db\DB::commit();
            return null;
        } catch (monolyth\db\Exception $e) {
            monolyth\db\DB::rollback();
            return 'error';
        }
    }
}

