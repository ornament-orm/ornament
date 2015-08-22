<?php

/**
 * @package monolyth
 */

namespace monolyth;
use Project;

/**
 * Helper class to handle all sorts of HTTP stuff. Use this instead of directly
 * referring to $_SERVER-type variables.
 *
 * @package MonoLyth
 * @author Marijn Ophorst <marijn@monomelodies.nl>
 * @copyright MonoMelodies 2008, 2009, 2010, 2012, 2014
 * @todo Add code for XSS prevention.
 */
class HTTP_Model
{
    /**
     * Just a simple hash to store stuff internally.
     */
    private $vars = [
        'g' => [],
        'p' => [],
        'c' => [],
        'r' => [],
    ];

    public function __construct()
    {
        $sgs = [
            'g' => &$_GET,
            'p' => &$_POST,
            'c' => &$_COOKIE,
            'r' => &$_REQUEST
        ];
        $this->link = new utils\Link;
        foreach ($sgs as $key => &$sg) {
            $this->vars[strtolower($key)] =& $sg;
        }
        if ($_FILES) {
            $_POST = array_merge_recursive($_POST, $this->fixFiles($_FILES));
        }
        if (!isset($_SERVER['REQUEST_URI'])) {
            $_SERVER['REQUEST_URI'] = '/';
        }
        $parts = explode('?', $_SERVER['REQUEST_URI']);
        $this->url = array_shift($parts);
    }

    /**
     * For reasons only the programmers of PHP presumably understand, the
     * $_FILES superglobal is convoluted as hell on a summer day.
     * This private method recursively rewrites it to something saner.
     *
     * @param array $array An array of $_FILES elements.
     * @return array The rewritten array.
     */
    private function fixFiles(array $array)
    {
        $out = [];
        foreach ($array as $key => $value) {
            if (is_array($value['name'])) {
                $tmp = [];
                foreach ($value['name'] as $name => $dummy) {
                    $tmp[$name] = [
                        'name' => $dummy,
                        'type' => $value['type'][$name],
                        'tmp_name' => $value['tmp_name'][$name],
                        'error' => $value['error'][$name],
                        'size' => $value['size'][$name],
                    ];
                }
                $out[$key] = $this->fixFiles($tmp);
            } elseif (strlen($value['name'])) {
                $out[$key] = $value;
            }
        }
        return $out;
    }

    /**
     * Returns the value of a $_GET-variable.
     *
     * @param string $name The name of the variable.
     * @param bool $checkonly True to check for isset, false to return
     *                        its value or null (if not set).
     * @return mixed The value, a boolean if checking or null if unset.
     */
    public function getGet($name, $checkonly = false)
    {
        if ($checkonly) {
            return isset($this->vars['g'][$name]);
        }
        return isset($this->vars['g'][$name]) ? $this->vars['g'][$name] : null;
    }

    /**
     * Returns the values of a $_POST-variable.
     *
     * @param string $name The name of the variable.
     * @return mixed The value, or null if unset.
     */
    public function getPost($name)
    {
        return isset($this->vars['p'][$name]) ?
            $this->vars['p'][$name] :
            null;
    }

    /**
     * Returns the values of a $_COOKIE-variable.
     *
     * @param string $name The name of the variable.
     * @return mixed The value, or null if unset.
     */
    public function getCookie($name)
    {
        return isset($this->vars['c'][$name]) ? $this->vars['c'][$name] : null;
    }

    /**
     * Returns the values of a $_REQUEST-variable.
     *
     * @param string $name The name of the variable.
     * @return mixed The value, or null if unset.
     */
    public function getRequest($name)
    {
        return isset($this->vars['r'][$name]) ? $this->vars['r'][$name] : null;
    }

    /**
     * Check to see if a post was valid.
     *
     * This helper method attempts as best it can to deduce if a received
     * POST seems valid. Invalid means obviously non-matching domains
     * or missing $_SERVER headers.
     *
     * Note domains are taken from SitedataConfig as wel as $_SERVER superglobal.
     * This would allow you to have one site over multiple domains (e.g. for a
     * single sign-on system).
     *
     * @return bool True if valid, false if not.
     * @todo Make this work for other servers than Apache.
     */
    public function isValidPost()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return false;
        }
        // HTTP_REFERER isn't set on repost.
        if (!isset($_SERVER['HTTP_REFERER'])) {
            return true;
        }
        if (!(
            $_SERVER['REQUEST_METHOD'] == 'POST' &&
            isset($_SERVER['HTTP_REFERER'])
        )) {
            return false;
        }
        $match = [];
        $protocol = Project::instance()['secure'] ? 'https' : 'http';
        foreach (array(
            "$protocol://{$_SERVER['SERVER_NAME']}",
            Project::instance()['http'],
            Project::instance()['https'] != Project::instance()['http'] ?
                Project::instance()['https'] :
                '',
        ) as $url) {
            if (!strlen($url)) {
                continue; // presumably same as SERVER_NAME
            }
            $test = parse_url($url, PHP_URL_HOST);
            $match[] = $test;
            while (count(explode('.', $test)) > 2) {
                $test = preg_replace("@^[a-z0-9-]+\.@", '', $test);
            }
            $match[] = $test;
        }
        $match = array_unique($match);
        $refer = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
        while (count(explode('.', $refer)) > 2) {
            $refer = preg_replace("@^[a-z0-9-]+\.@", '', $refer);
        }
        return in_array($refer, $match);
    }

    /**
     * Returns the protocol we're currently using, e.g. http or https.
     *
     * @return string The protocol used.
     */
    public function getProtocol()
    {
        return Project::instance()['protocol'.(Project::instance()['secure'] ? 's' : '')];
    }

    /**
     * Returns the URL for the current page, but with redir parameter stripped.
     *
     * @return string The URL for the current page.
     */
    public function getSelf()
    {
        static $cache = null;
        if (!isset($_SERVER['REQUEST_URI'])) {
            return null;
        }
        if (isset($cache)) {
            return $cache;
        }

        /** Current URI, but without redir param. */
        $cache = preg_replace(
            '/[?&] redir (?:=[^&]*)? (?![^&])/x',
            '',
            $_SERVER['REQUEST_URI']
        );
        $cache = str_replace('&', '&amp;', $cache);
        $cache = $this->link->fixPrefix($cache, Project::instance()['secure']);
        preg_match(
            "@(https?://)@",
            Project::instance()[Project::instance()['secure'] ? 'https' : 'http'],
            $match
        );
        $cache = "{$match[1]}{$_SERVER['SERVER_NAME']}$cache";
        return $cache;
    }

    /**
     * Returns the redirect parameter for this page. If none is given,
     * use the current URL. Redirect may be passed in a GET or POST variable
     * with the name redir, or optionally as default first argument.
     *
     * @param string $default The default URL to redirect to.
     * @return string An urlencode'd URL to use in GET or POST.
     */
    public function getRedir($default = null)
    {
        if (isset($_POST['redir'])) {
            $redir = urldecode($_POST['redir']);
        } elseif (isset($_GET['redir'])) {
            $redir = urldecode($_GET['redir']);
        } elseif (isset($_SERVER['HTTP_REREFER'])) {
            $redir = $_SERVER['HTTP_REFERER'];
        } elseif (isset($default)) {
            $redir = $default;
        } else {
            $redir = $this->getSelf();
        }
        if (!isset($_SERVER['SERVER_NAME'])) {
            return $redir;
        }
        if (!preg_match('@^https?://@', $redir)) {
            return sprintf(
                'http%s://%s%s',
                isset($_SERVER['SERVER_PORT'])
                    && $_SERVER['SERVER_PORT'] == '443' ? 's': '',
                $_SERVER['SERVER_NAME'],
                $redir
            );
        }
        return $redir;
    }

    public function getHost()
    {
        return $_SERVER['SERVER_NAME'];
    }

    public function getMethod()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    public function expire($seconds = 0)
    {
        if ($seconds > 0) {
            $time = time() + $seconds;
            $expiredate = gmdate("D, d M Y H:i:s", $time);
            $modifieddate = gmdate("D, d M Y H:i:s");
        } else {
            $expiredate = $modifieddate = gmdate("D, d M Y H:i:s");
        }
        try {
            header("Expires: $expiredate GMT");
            header("Last-Modified: $modifieddate GMT");
            if (!$seconds) {
                header("Cache-Control: no-cache, must-revalidate");
            }
        } catch (ErrorException $e) {
            /**
             * This usually happens when output has already started. We probably
             * don't really want to complain about that.
             */
        }
    }

    private function recursiveUnset($array, $matches)
    {
        foreach ($array as $key => &$value) {
            if (in_array($key, $matches)) {
                unset($array[$key]);
                continue;
            }
            if (is_array($value)) {
                $value = $this->recursiveUnset($value, $matches);
            }
        }
        return $array;
    }

    public function isXMLHttpRequest()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
    }

    public function ip()
    {
        return $_SERVER['REMOTE_ADDR'];
    }

    public function userAgent()
    {
        return $_SERVER['HTTP_USER_AGENT'];
    }

    public function server()
    {
        return $_SERVER['SERVER_NAME'];
    }

    public function url($strip_query = false)
    {
        $url = $_SERVER['REQUEST_URI'];
        if ($strip_query) {
            $url = explode('?', $url)[0];
        }
        return $url;
    }

    public function query()
    {
        return $_SERVER['QUERY_STRING'];
    }
}

