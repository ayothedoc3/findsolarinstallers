<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.10.1
 *  LICENSE: FL1KU6QLIUJA - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: bizlisting.xyz
 *  FILE: TRANSLATOR.PHP
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

namespace Flynax\Utils;

use Exception;

/**
 * Translator class give ability to translate phrases by Google Translation API Basic v2
 * @since 4.9.1
 */
class Translator
{
    /**
     * Translate only phrases that have a pattern in the key
     * Empty by default and all provided phrases will be translated
     * For example, add "pages+name+" pattern to translate pages names only
     * It helps reduce count of requests to API and check the credentials of the Google Translation API Key
     * Using for "translatePhrases" method only; variables in phrase will not be escaped
     */
    public const PHRASE_KEY_PATTERN = '';

    /**
     * Translate the phrases into the language you need
     *
     * @param array       $strings - List of phrases
     *                             - Can be used as simple list: ['phrase 1', 'phrase 2']
     *                             - Or you can use Key => Value format: ['key1' => 'phrase 1', 'key2' => 'phrase 2']
     * @param string      $target  - Target language code (for example "fr" for french)
     * @param string|null $source  - Source language code (for example "en" for english)
     *                             - Optional, by default it will be detected automatically by Google
     * @param string|null $error
     *
     * @return array
     */
    public static function translatePhrases(array $strings, string $target, ?string $source = '', ?string &$error = ''): array
    {
        if (!($translator = self::getTranslator()) || empty($strings) ) {
            return $strings;
        }

        try {
            if (self::PHRASE_KEY_PATTERN) {
                foreach ($strings as &$string) {
                    if (false !== strpos($string['Key'], self::PHRASE_KEY_PATTERN)) {
                        $string['Value']  = self::escapeVariableInString($string['Value']);
                        $translatedPhrase = $translator::translatePhrase($string['Value'], $target, $source);
                        $string['Value']  = trim(self::unescapeVariableInString($translatedPhrase));
                    }
                }

                return $strings;
            }

            $escapedStrings = array_map(static function ($item) {
                if (is_array($item) && isset($item['Key'], $item['Value'])) {
                    $item = $item['Value'];
                }

                return self::escapeVariableInString($item);
            }, $strings);

            $translatedStrings = $translator::translatePhrases($escapedStrings, $target, $source);

            $countTranslatedStrings = count($translatedStrings);
            for ($i = 0; $i < $countTranslatedStrings; $i++) {
                if (is_array($strings[$i]) && $strings[$i]['Key']) {
                    $strings[$i]['Value'] = self::unescapeVariableInString($translatedStrings[$i]['text']);
                } else {
                    $strings[$i] = self::unescapeVariableInString($translatedStrings[$i]['text']);
                }
            }

            return $strings;
        } catch (Exception $e) {
            $response = json_decode($e->getMessage());

            if (isset($response->error->message)) {
                $error = $response->error->message;
            } else if (is_string($e->getMessage())) {
                $error = trim($e->getMessage());
            }

            return $strings;
        }
    }

    /**
     * Translate the phrase into the language you need
     *
     * @param string      $string
     * @param string      $target - Target language code (for example "fr" for french)
     * @param string|null $source - Source language code (for example "en" for english)
     *                            - Optional, by default it will be detected automatically by Google
     * @param string|null $error
     *
     * @return string
     */
    public static function translatePhrase(string $string, string $target, ?string $source = '', ?string &$error = ''): string
    {
        if (!($translator = self::getTranslator()) || empty($string) ) {
            return $string;
        }

        try {
            $string = self::escapeVariableInString($string);
            return trim(self::unescapeVariableInString($translator::translatePhrase($string, $target, $source)));
        } catch (Exception $e) {
            $response = json_decode($e->getMessage());

            if (isset($response->error->message)) {
                $error = $response->error->message;
            } else if (is_string($e->getMessage())) {
                $error = trim($e->getMessage());
            }

            return $string;
        }
    }

    /**
     * Translate listing text fields
     *
     * @since 4.9.3
     *
     * @param  int         $id    - Listing ID
     * @param  string|null $error
     * @return array              - Translated fields data
     */
    public static function translateListingText(int $id, ?string &$error = ''): array
    {
        return self::translateItemText($id, 'listing', $error);
    }

    /**
     * Translate account text fields
     *
     * @since 4.9.3
     *
     * @param  int         $id    - Account ID
     * @param  string|null $error
     * @return array              - Translated fields data
     */
    public static function translateAccountText(int $id, ?string &$error = ''): array
    {
        return self::translateItemText($id, 'account', $error);
    }

    /**
     * Translate item (listing or account) text fields
     *
     * @since 4.9.3
     *
     * @param  int         $itemID - Item ID
     * @param  string      $item   - Item type, listing or account
     * @param  string|null $error
     * @return array               - Translated fields data
     */
    public static function translateItemText(int $itemID, string $item, ?string &$error = ''): array
    {
        global $rlDb, $reefless, $config, $languages, $rlLang;

        if (!self::getTranslator()) {
            return [];
        }

        if (!in_array($item, ['listing', 'account'])) {
            return [];
        }

        if (!$languages) {
            $languages  = $rlLang->getLanguagesList();
        }

        $source_table = $item == 'listing' ? 'listings' : 'accounts';
        $fields_table = $item == 'listing' ? 'listing_fields' : 'account_fields';

        $rlDb->outputRowsMap = [false, 'Key'];
        $fields = $rlDb->fetch(
            ['Key'],
            ['Opt1' => '1', 'Multilingual' => '1'],
            "AND `Type` IN ('text','textarea')",
            null, $fields_table
        );

        if (!$fields) {
            return [];
        }

        $data = $rlDb->fetch($fields, ['ID' => $itemID], null, 1, $source_table, 'row');

        foreach ($data as $field => &$value) {
            $value = $reefless->parseMultilingual($value);

            if (!array_filter($value)) {
                continue;
            }

            $source_text = $value[$config['lang']] ?: current($value);
            $source_code = $value[$config['lang']] ? $config['lang'] : key($value);

            if (!$source_text) {
                continue;
            }

            $new_data = '';
            foreach ($languages as $code => $language) {
                if (!$value[$code] || ($value[$code] === $source_text && $code !== $source_code)) {
                    $value[$code] = self::translatePhrase($source_text, $code, $source_code, $error);
                }

                $new_data .= "{|{$code}|}" . $value[$code] . "{|/{$code}|}";
            }

            if ($new_data) {
                $update = [
                    'fields' => [$field => $new_data],
                    'where'  => ['ID' => $itemID]
                ];
                $rlDb->updateOne($update, $source_table, [$field]);
            }
        }

        return $data;
    }

    /**
     * Escape variables in text like {category} to prevent the translation of it
     *
     * @param string $string
     *
     * @return string
     */
    public static function escapeVariableInString(string $string): string
    {
        return $string ? preg_replace('/({[^}]+})/m', '<span translate="no">${1}</span>', $string) : '<span translate="no">{empty}</span>';
    }

    /**
     * Remove escaping of the variables in string
     *
     * @param string $string
     *
     * @return string
     */
    public static function unescapeVariableInString(string $string): string
    {
        return $string === '<span translate="no">{empty}</span>' ? '' : preg_replace('/<span translate="no">(\{[^}]+})<\/span>/im', '${1}', $string);
    }

    /**
     * Get the class of the active translator
     *
     * @since 4.9.3
     *
     * @return object|null
     */
    public static function getTranslator()
    {
        static $class = null;

        if (!is_null($class)) {
            return $class;
        }

        $provider = ucfirst($GLOBALS['config']['translation_api']);
        $class = "\\Flynax\\Utils\\TranslationProviders\\{$provider}";

        if (class_exists($class)) {
            $class = new $class();
            return $class::isConfigured() ? $class : null;
        }

        return null;
    }
}
