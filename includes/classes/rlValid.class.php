<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.10.1
 *  LICENSE: FL1KU6QLIUJA - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: bizlisting.xyz
 *  FILE: RLVALID.CLASS.PHP
 *  
 *  The software is a commercial product delivered under single, non-exclusive,
 *  non-transferable license for one domain or IP address. Therefore distribution,
 *  sale or transfer of the file in whole or in part without permission of Flynax
 *  respective owners is considered to be illegal and breach of Flynax License End
 *  User Agreement.
 *  
 *  You are not allowed to remove this information from the file without permission
 *  of Flynax respective owners.
 *  
 *  Flynax Classifieds Software 2026 | All copyrights reserved.
 *  
 *  https://www.flynax.com
 ******************************************************************************/

use Flynax\Utils\Valid;

class rlValid
{
    /**
     * escape string by mysql injection (by reference)
     *
     * @param array $data - requested string
     *
     * @return mixed valid data
     **/
    public function sql(&$data)
    {
        if (is_array($data)) {
            foreach ($data as $string => $value) {
                $this->sql($data[$string]);
            }
        } else if (is_object($data)) {
            $vars = get_object_vars($data);
            if (count($vars)) {
                foreach ($vars as $key => $value) {
                    $this->sql($data->$key);
                }
            }
        } else {
            $this->escapeString($data);
        }
    }

    /**
     * escape string by mysql injection
     *
     * @param array $data - requested string
     *
     * @return mixed valid data
     **/
    public function xSql($data)
    {
        if (is_array($data)) {
            foreach ($data as $string => $value) {
                $data[$string] = $this->xSql($data[$string]);
            }
        } elseif (is_object($data)) {
            $vars = get_object_vars($data);
            if (count($vars)) {
                foreach ($vars as $key => $value) {
                    $data->$key = $this->xSql($value);
                }
            }
        } else {
            $this->escapeString($data);
        }

        return $data;
    }

    /**
     * Escape string by mysql injection
     * Return string by reference
     *
     * @param string $string - requested string
     */
    public function escapeString(&$string)
    {
        if ($GLOBALS['rlDb'] && $GLOBALS['rlDb']->realEscapeString($string)) {
            $string = $GLOBALS['rlDb']->realEscapeString($string);
        } else {
            $string = trim($string);
            if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
                $string = stripslashes($string);
            }
            $string = str_replace("\'", "'", $string);
            $string = addslashes($string);
        }
    }

    /**
     * html tags conversion (by reference)
     *
     * @param array $data - requested string
     *
     * @return mixed valid data
     **/
    public function html(&$data)
    {
        if (is_array($data)) {
            foreach ($data as $string => $value) {
                //$data[$string] = strip_tags( $data[$string] );
                $data[$string] = htmlspecialchars($data[$string]);
            }
        } else {
            //$data = strip_tags( $data );
            $data = htmlspecialchars($data);
        }
    }

    /**
     * html tags conversion
     *
     * @param array $data - requested string
     *
     * @return mixed valid data
     **/
    public function xHtml($data)
    {
        if (is_array($data)) {
            foreach ($data as $string => $value) {
                if (!is_array($data[$string])) {
                    //$data[$string] = strip_tags( $data[$string] );
                    $data[$string] = htmlspecialchars($data[$string]);
                }
            }
        } else {
            //$data = strip_tags( $data );
            $data = htmlspecialchars($data);
        }

        return $data;
    }

    /**
     * strip javascript tags
     *
     * @param array $data - requested string
     *
     * @return mixed valid data
     **/
    public function stripJS($data)
    {
        if (is_array($data)) {
            foreach ($data as $string => $value) {
                if (!is_array($data[$string])) {
                    $data[$string] = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $data[$string]);
                    $data[$string] = preg_replace('/[\r\n\t]/is', '', $data[$string]);
                }
            }
        } else {
            $data = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $data);
            $data = preg_replace('/[\r\n\t]/is', '', $data);
        }

        return $data;
    }

    /**
     * validate e-mail
     *
     * @deprecated 4.10.1 - Use Flynax\Utils\Valid::isEmail() instead
     *
     * @param string $mail - e-mail address
     * @return bool
     **/
    public function isEmail($mail)
    {
        return Valid::isEmail($mail);
    }

    /**
     * validate URL address
     *
     * @deprecated 4.10.1 - Use Flynax\Utils\Valid::isURL() instead
     *
     * @param string $url - url address
     * @return bool
     **/
    public function isUrl($url)
    {
        return Valid::isURL($url);
    }

    /**
     * validate domain name
     *
     * @deprecated 4.10.1 - Use Flynax\Utils\Valid::isDomain() instead
     *
     * @param string $domain - domain name
     * @return bool
     **/
    public function isDomain($domain)
    {
        return Valid::isDomain($domain);
    }

    /**
     * check image extension
     *
     * @param string $extension - file extension
     *
     * @return bool
     **/
    public function isImage($extension)
    {
        // available image extensions
        $available_ext = array(1 => 'jpg', 2 => 'jpeg', 3 => 'gif', 4 => 'png');

        if (!array_search(strtolower($extension), $available_ext)) {
            return false;
        }
        return true;
    }

    /**
     * check file extension
     *
     * @param string $type      - file type
     * @param string $extension - file extension
     *
     * @return bool
     **/
    public function isFile($type, $extension)
    {
        include_once RL_LIBS . 'system.lib.php';

        global $l_file_types;

        // available image extensions
        $available_ext = $l_file_types[$type]['ext'];

        if (false === strpos($available_ext, strtolower($extension))) {
            return false;
        }
        return true;
    }

    /**
     * get domain name from url
     *
     * @param string $url - url
     * @param bool $mode - allow local domain names, like: localhost
     *
     * @return string - domain name
     **/
    public function getDomain($url = null)
    {
        return parse_url($url, PHP_URL_HOST);
    }

    /**
     * convert string to key
     *
     * @param string $key - key
     * @param string $replace - replae simbol
     *
     * @return string - valid key
     **/
    public function str2key($key, $replace = '_')
    {
        $key = preg_replace('/[^a-zA-Z0-9\+]+/i', $replace, $key);
        $key = strtolower($key);
        $key = trim($key, $replace);

        return empty($key) ? false : $key;
    }

    /**
     * Convert string to path
     *
     * @param  string $str
     * @param  bool   $keepSlashes - Save '/' symbol in path
     * @return string
     */
    public function str2path($str, $keepSlashes = false)
    {
        global $config;

        $rx = $keepSlashes ? '\/' : '';
        loadUTF8functions('ascii', 'utf8_to_ascii', 'utf8_is_ascii');

        if (!utf8_is_ascii($str)) {
            if ($config['url_transliteration']) {
                $str = utf8_to_ascii($str);
                $str = preg_replace("/[^a-z0-9{$rx}]+/i", '-', $str);
            } else {
                $str = preg_replace("/[\s#%@;=\?\^\!\&\-\№\%\:\,\-\(\)\_|~«»\"\"'']+/u", '-', $str);
            }
        } else {
            $str = preg_replace("/[^a-z0-9{$rx}]+/i", '-', $str);
        }

        $str = preg_replace("/\.+/i", '-', $str);
        $str = preg_replace("/\-+/", '-', $str);
        $str = $config['url_transliteration'] ? strtolower($str) : mb_strtolower($str, 'UTF-8');
        $str = trim($str, '-');
        $str = trim($str, '/');
        $str = trim($str);

        return empty($str) ? '' : $str;
    }

    /**
     * Convert string to path in multilingual mode
     *
     * @since 4.8.0
     *
     * @param  string      $string
     * @return string|bool
     */
    public function str2multiPath($string)
    {
        $string = (string) $string;

        if (!$string) {
            return false;
        }

        $string = preg_replace("/[\s#%@;=\?\^\!\&\-\№\%\:\,\-\(\)\_|~]+/u", '-', $string);
        $string = preg_replace("/\.+/i", '-', $string);
        $string = preg_replace('/\-+/', '-', $string);
        $string = mb_strtolower($string, 'UTF-8');
        $string = trim($string, '-');
        $string = trim($string, '/');
        $string = trim($string);

        return empty($string) ? false : $string;
    }

    /**
     * Convert string with price to money format
     *
     * @since 4.7.1 - Added $forceShowCents parameter
     *
     * @param  array  $aParams        - String with numbers
     * @param  bool   $forceShowCents - Show/hide of cents forcibly
     * @return string                 - Formatted string
     */
    public function str2money($aParams, $forceShowCents = null)
    {
        global $config;

        $string              = (float) preg_replace('/[^0-9.]/', '', (is_array($aParams) ? $aParams['string'] : $aParams));
        $decimal_separator   = (string) $config['price_separator'];
        $thousands_separator = (string) $config['price_delimiter'];
        $show_cents          = isset($forceShowCents) ? (bool) $forceShowCents : (bool) $config['show_cents'];

        return number_format($string, $show_cents ? 2 : 0, $decimal_separator, $thousands_separator);
    }

    /**
     * make key unique
     *
     * @param string $dir - directory to create
     *
     * @return unique key
     *
     **/
    public function uniqueKey($key = false, $table = false, $keyField = 'Key')
    {
        global $rlDb;

        if (!$key || !$table) {
            return 'key_' . mt_rand();
        }

        if ($rlDb->getOne($keyField, "`{$keyField}` = '{$key}'", $table)) {
            $key .= rand(1, 9);
            return $this->uniqueKey($key, $table, $keyField);
        } else {
            return $key;
        }
    }

    /**
     * @since 4.5.0
     *
     * htmlspecialchars quotes in the string
     *
     * @param array $data - requested string
     *
     * @return mixed valid data
     **/
    public function quotes(&$data)
    {
        if (!$data) {
            return;
        }

        if (is_array($data)) {
            foreach ($data as $string => $value) {
                $this->quotes($data[$string]);
            }
        } elseif (is_object($data)) {
            $vars = get_object_vars($data);
            if (count($vars)) {
                foreach ($vars as $key => $value) {
                    $this->quotes($data->$key);
                }
            }
        } else {
            $data = str_replace('"', "&quot;", $data);
        }
    }
}
