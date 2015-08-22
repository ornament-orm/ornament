<?php

namespace monolyth\model;
use monad\model\TabularInline;

class Text_I18nAdminInline extends Text_I18nAdmin implements TabularInline
{
    public static $tabBy = 'language_str';
    protected $noneditable = ['language_str'];
    
    public function fields()
    {
        return ['content'];
    }
}

