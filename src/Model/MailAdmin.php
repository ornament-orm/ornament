<?php

namespace monolyth\model;
use monad\model\Admin;

class MailAdmin extends Admin
implements Noncreateable, Nondeleteable, Multilanguage
{
    protected $tblI18n = 'monolyth_mail';

    public function listFields()
    {
        return ['id', 'language_str', 'template', 'sender', 'subject'];
    }
}

