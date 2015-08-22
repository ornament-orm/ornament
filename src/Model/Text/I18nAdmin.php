<?php

namespace monolyth\model;
use Monad;
use monad\model\Admin;

class Text_I18nAdmin extends Admin implements Multilanguage, Noncreateable
{
    protected $editors = [
        'content' => [
            'toolbar' => 'Full',
            'height' => '300px',
        ],
    ];
}
Monad::registerHook('monolyth_text_i18n', Admin::bitflag('status'));
Monad::registerHook('monolyth_text_i18n', Admin::images('content'));

