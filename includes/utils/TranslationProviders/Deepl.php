<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.10.1
 *  LICENSE: FL1KU6QLIUJA - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: bizlisting.xyz
 *  FILE: DEEPL.PHP
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

namespace Flynax\Utils\TranslationProviders;

use Flynax\Interfaces\TranslationProviderInterface;
use DeepL\Translator;

/**
 * Translator class give ability to translate phrases by the Deepl API
 * @since 4.9.3
 */
class Deepl implements TranslationProviderInterface
{
    /**
     * List of special language codes
     * https://developers.deepl.com/docs/resources/supported-languages
     *
     * @since 4.10.0
     * @var array
     */
    public static $langCodes = [
        'en' => 'en-us',
        'pt' => 'pt-br',
    ];

    /**
     * Translate the phrases into the language you need
     *
     * @param array       $strings - List of phrases
     *                             - Can be used as simple list: ['phrase 1', 'phrase 2']
     *                             - Or you can use Key => Value format: ['key1' => 'phrase 1', 'key2' => 'phrase 2']
     * @param string      $target  - Target language code (for example "fr" for french)
     * @param string|null $source  - Source language code (for example "en" for english)
     *                             - Optional, by default it will be detected automatically by Google
     *
     * @return array
     */
    public static function translatePhrases(array $strings, string $target, ?string $source = ''): array
    {
        static $translator = null;

        if (is_null($translator)) {
            $translator = new Translator($GLOBALS['config']['deepl_translation_api_key']);
        }

        $options     = ['tag_handling' => 'html', 'ignore_tags' => 'span translate="no"'];
        $target      = self::$langCodes[$target] ?: $target;
        $translation = $translator->translateText($strings, $source, $target, $options);

        foreach ($translation as &$translatedObject) {
            $translatedObject = (array) $translatedObject;
        }

        return $translation;
    }

    /**
     * Translate the phrase into the language you need
     *
     * @param string      $string
     * @param string      $target - Target language code (for example "fr" for french)
     * @param string|null $source - Source language code (for example "en" for english)
     *                            - Optional, by default it will be detected automatically by API
     *
     * @return string
     */
    public static function translatePhrase(string $string, string $target, ?string $source = ''): string
    {
        $translation = self::translatePhrases([$string], $target, $source);
        return is_array($translation[0]) && $translation[0]['text'] ? (string) $translation[0]['text'] : $string;
    }

    /**
     * @return bool
     */
    public static function isConfigured(): bool
    {
        return $GLOBALS['config']['translation_api'] === 'deepl' && $GLOBALS['config']['deepl_translation_api_key'];
    }
}
