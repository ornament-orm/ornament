<?php

/**
 * Base language configuration.
 *
 * Please extend this for your own projects. The default behaviour is to query
 * monolyth_language for available languages. For performance reasons, you could
 * change this to simply use hardcoded values. As long as the resulting object
 * adheres to the same interface (see the add method), this is perfectly fine.
 *
 * @package monolyth
 * @author Marijn Ophorst <marijn@monomelodies.nl>
 * @copyright MonoMelodies 2008, 2009, 2010, 2011, 2012, 2013
 * @see monolyth\core\I18n_Model
 */

namespace monolyth;
use monolyth\core\I18n_Model;
use monolyth\core\NoDefaultLanguage_Exception;
use monolyth\adapter\Adapter;
use monolyth\adapter\nosql\Adapter as Nadapter;
use monolyth\adapter\sql\NoResults_Exception;
use monolyth\adapter\nosql\KeyNotFound_Exception;
use Adapter_Access;

class Language_Model extends I18n_Model
{
    use Adapter_Access;

    protected $exception = 'monolyth\LanguageNotFound_Exception';

    protected function __construct()
    {
        $cache = self::cache();
        if (isset($cache)) {
            try {
                $rows = unserialize($cache->get('languages'));
            } catch (KeyNotFound_Exception $e) {
            }
        }
        if (!(isset($rows) && $rows)) {
            $adapter = self::adapter();
            try {
                $rows = $adapter->rows(
                    "monolyth_language_all a
                     JOIN monolyth_language l USING(id)",
                    '*',
                    [],
                    ['order' => 'sortorder']
                );
                if (isset($cache)) {
                    $cache->set('languages', serialize($rows), 60 * 45);
                }
            } catch (NoResults_Exception $e) {
                throw new NoDefaultLanguage_Exception();
            }
        }
        $this->build($rows);
    }
}

