<?php

/**
 * @package monolyth
 */

namespace monolyth;
use ErrorException;
use Adapter_Access;

class User_Model implements User_Settings
{
    use utils\Translatable;
    use render\Url_Helper;
    use Session_Access;
    use Message_Access;
    use Adapter_Access;

    public function getArrayCopy()
    {
        return self::session()->get('User');
    }

    public function loggedIn()
    {
        try {
            return (bool)self::session()->get('User')['id'];
        } catch (ErrorException $e) {
            return false;
        }
    }

    public function get($field)
    {
        try {
            return self::session()->get('User')[$field];
        } catch (ErrorException $e) {
            return null;
        }
    }

    public function getNameById($id)
    {
        return self::adapter()->field('monolyth_auth', 'name', compact('id'));
    }

    public function groups()
    {
        $groups = self::session()->get('Groups');
        if ($groups) {
            return $groups;
        }
        return [];
    }

    public function inGroup($group)
    {
        $groups = $this->groups();
        foreach (func_get_args() as $group) {
            if (in_array($group, $groups)) {
                return true;
            }
        }
        return false;
    }

    public function __call($name, $arguments)
    {
        if ($arguments) {
            $User = self::session()->get('User');
            $User[$name] = array_shift($arguments);
            self::session()->set(compact('User'));
        }
        return $this->get($name);
    }

    public function id2dir($id)
    {
        return implode('/', str_split($id, 3));
    }

    public function login(core\Post_Form $form, $salted = false)
    {
        if (!($error = call_user_func(
            new account\Login_Model,
            $form,
            $salted
        ))) {
            if ($this->status() & $this::STATUS_GENERATED_PASS) {
                self::message()->add(
                    'info',
                    'monolyth\account\pass/generated'
                );                    
            }
        }
        return $error;
    }

    public function logout(&$redir = null)
    {
        call_user_func_array(new account\Logout_Model, [&$redir]);
    }

    public function active()
    {
        return !($this->status() & $this::STATUS_INACTIVE);
    }
}

