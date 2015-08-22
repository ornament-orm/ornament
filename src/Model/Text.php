<?php

/**
 * Monolyth implementation of i18n functionality.
 *
 * As with sessions, you should define an empty Text-class extending the
 * library-class you want to use. Currently only Text_File for flat file
 * text handling is supported; future releases will support Text_DB for
 * databased handling, Text_Get for gettext handling and possibly
 * Text_SomeLibrary for standard libraries.
 *
 * Most variations use textids to identify texts. Textids are essentially
 * freeform. Monolyth adheres to the following scheme:
 * <code>
 * [module]/[pagename or section]/[slash/separated/identifiers]/id
 * </code>
 * ...where sections can be slice or message. But it's really just a
 * convention, so feel free to use your own layout.
 *
 * Example uses:
 * <code>
 * <?php
 *
 * require_once 'class/text/file.php';
 * class Text extends Text_File {}
 *
 * ?>
 *
 * <?php
 *
 * // assuming current language is Dutch:
 * echo Text::get('samplestring');
 * // Sample output (should be in ./samplestring.nl.txt):
 * // Een string om te vertalen.
 * $name = 'Marijn';
 * echo Text::get('greeting', $name);
 * // Sample output (should be in ./greeting.nl.txt), and
 * // assuming its contents are "Hoi, %1$s.":
 * // Hoi, Marijn.
 *
 * ?>
 * </code>
 * The get method defaults to the current language.
 * To override this, use the getbylanguage method:
 * <code>
 * <?php
 *
 * Text::load('SomeIdenfitier/SomeSubIdentifier');
 * echo Text::getbylanguage(
 *     'samplestring',
 *     LanguageConfig::EN
 * );
 * // Sample output: A string to translate.
 *
 * ?>
 * </code>
 *
 * @package monolyth
 * @author Marijn Ophorst <marijn@monomelodies.nl>
 * @copyright MonoMelodies 2008, 2009, 2010, 2011, 2012, 2014
 */

namespace monolyth;
use ErrorException;
use Adapter_Access;

/**
 * Base Text model. If you prefer handling i18n via gettext, you can override
 * this in your custom project.
 *
 * @see Adapter_Access
 * @see monolyth\utils\Name_Helper
 */
class Text_Model
{
    use utils\Name_Helper;
    use Language_Access {
        Language_Access::language as _language;
    }
    use Adapter_Access;

    const STATUS_HTML = 1;

    private static $texts = [];
    private $object;

    public function __construct($object)
    {
        $this->object = $object;
    }

    public function __invoke($id)
    {
        $args = func_get_args();
        $id = array_shift($args);
        array_unshift($args, $id, $this->object);
        return call_user_func_array(
            [$this, 'getForObject'],
            $args
        );
    }

    /**
     * Get a translated string for the current language. If an array is passed
     * for ids, the first one found is used.
     *
     * @param string|array $id The text(s) to translate.
     * @param mixed $arg,... Additional arguments to sprintf in the translated
     *                       string.
     * @return string Perpared HTML that is recognised by the Translate_Parser.
     * @see monolyth\render\Translate_Parser
     */
    public function get($id)
    {
        $args = func_get_args();
        $id = array_shift($args);
        $fn = null;
        if (!isset($fn)) {
            $fn = function($glue, $pieces) use(&$fn) {
                if (!is_array($pieces)) {
                    return "$pieces";
                }
                $return = [];
                foreach ($pieces as &$piece) {
                    $piece = is_array($piece) ? $fn($glue, $piece) : "$piece";
                }
                return implode($glue, $pieces);
            };
        }
        foreach ($args as &$arg) {
            $arg = $fn(',', $arg);
        }
        array_unshift($args, null);
        array_unshift($args, $id);
        return call_user_func_array(
            [$this, 'getByLanguage'],
            $args
        );
    }

    /**
     * Get a translated string for a specific language. If an array is passed
     * for ids, the first one found is used.
     *
     * If the third argument (i.e., the first parameter to pass to the text)
     * is callable, it's treated as the callback function and applied to the
     * translated text by the Translate_Parser.
     *
     * @param string|array $id The text(s) to get.
     * @param mixed $language The language to get it for.
     * @param mixed $arg,... Additional arguments to sprintf in the translated
     *                       string.
     * @return string Prepared HTML that is recognised by the Translate_Parser.
     * @see monolyth\render\Translate_Parser
     */
    public function getByLanguage($ids, $language)
    {
        $args = func_get_args();
        $ids = array_shift($args);
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $language = $this->language(array_shift($args));
        $parts = [$ids, $language];
        $callback = null;
        if (isset($args[0]) && is_callable($args[0])) {
            $callback = array_shift($args);
        }
        $parts[] = $args;
        $parts[] = $callback;
        $cache = self::cache();
        if (isset($cache)) {
            foreach ($ids as $id) {
                try {
                    $str = $cache->get(
                        "text/$id/$language"
                    );
                } catch (adapter\nosql\KeyNotFound_Exception $e) {
                }
            }
            if (isset($str) && strlen($str)) {
                return vsprintf($str, $args);
            }
        }
        return sprintf('$translate(%s)', base64_encode(serialize($parts)));
    }

    public function getForObject($ids, $o)
    {
        $args = func_get_args();
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        foreach ($ids as &$id) {
            if (!strlen($id)) {
                continue;
            }
            $id = $this->generate($o, $id);
        }
        $args[0] = $ids;
        unset($args[1]);
        return call_user_func_array(
            [$this, 'get'],
            array_values($args)
        );
    }

    public function load(array $matches)
    {
        $where = [];
        foreach ($matches as $i => $data) {
            $language = $this->language($data[1]);
            $where[0][] = [
                'id' => ['IN' => $data[0]],
                'language' => [
                    'IN' => self::_language()->fallbacks($language),
                ],
            ];
        }
        $cache = self::cache();
        try {
            foreach (self::adapter()->rows(
                'monolyth_text JOIN monolyth_text_i18n USING(id)',
                ['id', 'content', 'language'],
                $where
            ) as $row) {
                $language = $this->language($row['language']);
                self::$texts[$language][$row['id']] = $row['content'];
                if (isset($cache)) {
                    $cache->set(
                        "text/{$row['id']}/{$row['language']}",
                        $row['content']
                    );
                }
            }
        } catch (adapter\sql\NoResults_Exception $e) {
        }
        foreach ($matches as $data) {
            $fallbacks = self::_language()->fallbacks($data[1]);
            $text = null;
            if (!is_array($data[0])) {
                $data[0] = [$data[0]];
            }
            foreach ($fallbacks as $fallback) {
                foreach ($data[0] as $id) {
                    if (isset(self::$texts[$fallback][$id])) {
                        continue 3;
                    }
                    try {
                        $text = rtrim(file_get_contents(
                            $this->toFilename($id, $fallback),
                            true
                        ));
                        if (isset($cache)) {
                            $cache->set("text/$id/$fallback", $text);
                        }
                    } catch (ErrorException $e) {
                    }
                    if (isset($text)) {
                        self::$texts[$data[1]][$id] = $text;
                        continue 3;
                    }
                }
            }
        }
    }

    private function toFilename($id, $language = null)
    {
        $language = $this->language($language);
        $orig = explode('\\', $id);
        $parts = [array_pop($orig)];
        $parts = array_merge($orig, $parts);
        $fileid = implode(DIRECTORY_SEPARATOR, $parts);
        if ($fileid{0} == DIRECTORY_SEPARATOR) {
            $fileid = substr($fileid, 1);
        }
        return "$fileid.$language.txt";
    }

    private function notFound($id, $language, array $args = [])
    {
        return "[$id:$language".($args ?
            ' ('.implode(', ', $args).')' :
            ''
        ).']';
    }

    private function language($language)
    {
        if (!isset($language)) {
            try {
                $language = self::_language()->current->code;
            } catch (ErrorException $e) {
                $language = self::_language()->default->code;
            }
        } elseif (is_numeric($language)) {
            $language = call_user_func(
                [self::_language(), 'get'],
                $language
            )->code;
        }
        return $language;
    }

    public function retrieve($ids, $language, array $arguments = [],
        callable $callback = null
    )
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $language = $this->language($language);
        $fallbacks = self::_language()->fallbacks($language);
        foreach ($fallbacks as $flang) {
            $flang = $this->language($flang);
            foreach ($ids as $id) {
                if (!isset(self::$texts[$flang][$id])) {
                    continue;
                }
                try {
                    $res = vsprintf(self::$texts[$flang][$id], $arguments);
                    break;
                } catch (ErrorException $e) {
                }
            }
            if (!isset($res)) {
                continue;
            }
            if (isset($callback) && is_callable($callback)) {
                try {
                    $res = $callback($res);
                } catch (ErrorException $e) {
                }
            }
            return $res;
        }
        return $this->notFound($ids[0], $language);
    }

    public function exists($ids, $language = null)
    {
        $language = $this->language($language);
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $cache = self::cache();
        if (isset($cache)) {
            foreach ($ids as $id) {
                try {
                    $cache->get("text/$id/$language");
                    return true;
                } catch (adapter\nosql\KeyNotFound_Exception $e) {
                }
            }
        }
        try {
            foreach ($ids as $id) {
                fclose(fopen($this->toFilename($id, $language), 'r', true));
                return true;
            }
        } catch (ErrorException $e) {
            try {
                self::adapter()->field(
                    'monolyth_text_i18n',
                    'id',
                    [
                        'id' => ['IN' => $ids],
                        'language' => self::_language()->{$language}->id,
                    ]
                );
            } catch (adapter\sql\NoResults_Exception $e) {
                return false;
            }
        }
        return true;
    }
}

