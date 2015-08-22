<?php

namespace monolyth;
use monolyth\adapter\Adapter;
use monolyth\adapter\nosql\Cache;
use monolyth\adapter\sql\NoResults_Exception;
use monolyth\adapter\nosql\KeyNotFound_Exception;
use Adapter_Access;

class Country_Model extends core\I18n_Model
{
    use Adapter_Access;
    use Language_Access;

    public function __construct()
    {
        $cache = self::cache();
        if (isset($cache)) {
            try {
                $rows = $cache->get('countries');
            } catch (KeyNotFound_Exception $e) {
            }
        }
        if (!isset($rows)) {
            try {
                $rows = self::adapter()->rows(
                    "monolyth_country c
                     JOIN monolyth_country_i18n i USING(id)",
                    '*',
                    ['language' => self::language()->current->id],
                    ['order' => 'LOWER(title)']
                );
                if (isset($cache)) {
                    $cache->set('countries', $rows);
                }
            } catch (NoResults_Exception $e) {
                if (isset($cache)) {
                    $cache->set('countries', false);
                }
            }
        }
        if (isset($rows) && $rows) {
            $this->build($rows);
        }
    }
}

