<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.10.1
 *  LICENSE: FL1KU6QLIUJA - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: bizlisting.xyz
 *  FILE: UTIL.PHP
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

use Symfony\Component\Intl\Countries;
use Vectorface\Whip\Whip;
use Carbon\Carbon;

/**
 * @since 4.6.0
 */
class Util
{
    /**
     * @since 4.8.0
     * @var array
     */
    protected static $pages = [];

    /**
     * Redirect to the url
     *
     * @since 4.7.0 - Added the $http_response_code parameter
     *
     * @param string  $url                - url to redirect
     * @param boolean $exit               - is exit after redirect flag
     * @param integer $http_response_code - HTTP response code
     */
    public static function redirect($url, $exit = true, $http_response_code = 301)
    {
        global $rlHook;

        if (!$url) {
            return;
        }

        /**
         * @since 4.7.0 - Added the $exit and $http_response_code parameters
         * @since 4.6.0
         */
        $rlHook->load('utilsRedirectURL', $url, $exit, $http_response_code);

        header("Location: {$url}", true, $http_response_code);

        if ($exit) {
            exit;
        }
    }

    /**
     * Sort associative array by item
     *
     * @param array    - array to sort
     * @param string   - field name to sort by
     * @param constant - sorting type (array_multisort() function default params)
     *
     **/
    public static function arraySort(&$array, $field, $sort_type = SORT_ASC)
    {
        if (!$array || !$field) {
            return $array;
        }

        foreach ($array as &$value) {
            $sort[] = strtolower($value[$field]);
        }

        array_multisort($sort, $sort_type, $array);
        unset($sort);
    }

    /**
     * Get client IP address
     *
     * @since 4.9.1 - Content of function replaced by usage of the Whip library
     *
     * @return string - IP
     */
    public static function getClientIP(): ?string
    {
        static $clientIP = null;

        if (is_null($clientIP)) {
            $whip     = new Whip();
            $clientIP = $whip->getValidIpAddress();
        }

        return $clientIP;
    }

    /**
     * Get page key by url
     *
     * @param  string $url    - requested page url
     * @param  array  &$pages - pages mapping data (key => path)
     * @return string         - requested page key
     */
    public static function getPageKeyFromURL($url, &$pages)
    {
        if (!$url) {
            return false;
        }

        $path = false;
        $url = str_replace(RL_URL_HOME, '', $url);
        $pattern = $GLOBALS['config']['mod_rewrite'] ? '/^([^\/]+)/' : '/page\=([^\=\&]+)/';

        preg_match($pattern, $url, $matches);

        if ($matches[1]) {
            $path = $matches[1];

            if (is_array($pages) && $key = array_search($path, $pages)) {
                return $key;
            } else {
                return $GLOBALS['rlDb']->getOne('Key', "`Path` = '{$path}'", 'pages');
            }
        } else {
            return false;
        }
    }

    /**
     * Get content by URL
     *
     * @param string $url     - source url
     * @param int $time_limit - time limit
     *
     * @return string - content
     **/
    public static function getContent($url, $time_limit = 10)
    {
        $content = null;
        $user_agent = 'Flynax Bot';

        if (extension_loaded('curl')) {
            $ch = curl_init();

            // localhost usage mode
            if ($_SERVER['SERVER_ADDR'] == '127.0.0.1') {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            }

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $time_limit);
            curl_setopt($ch, CURLOPT_TIMEOUT, $time_limit);
            curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
            curl_setopt($ch, CURLOPT_REFERER, RL_URL_HOME);
            $content = curl_exec($ch);
            curl_close($ch);
        } elseif (ini_get('allow_url_fopen')) {
            $default = ini_set('default_socket_timeout', $time_limit);
            $stream = fopen($url, "r");
            ini_set('default_socket_timeout', $default);

            if ($stream) {
                while (!feof($stream)) {
                    $content .= fgets($stream, 4096);
                }
                fclose($stream);
            }
        } else {
            $GLOBALS['rlDebug']->logger("Unable to get content from: {$url}");
            return 'Unable to get content from: ' . $url;
        }

        return $content;
    }

    /**
     * Prepare error response and write it to logs
     *
     * @since 4.6.1
     *
     * @param  string $msg - error message
     * @param  bool   $log - write the message to the logs
     * @return array       - error response
     */
    public static function errorResponse($msg, $log = true)
    {
        if ($log && $msg && is_object($GLOBALS['rlDebug'])) {
            $GLOBALS['rlDebug']->logger($msg);
        }

        $msg = $msg ?: 'No error response message specified';

        return array(
            'status'  => 'ERROR',
            'message' => $msg,
        );
    }

    /**
     * Convert string size to bytes, etc: 2M to 2097152
     *
     * @since 4.6.1
     *
     * @param  string $size - Size string
     * @return integer      - Converted size
     */
    public static function stringToBytes($size)
    {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
        $size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.

        if ($unit) {
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        } else {
            return round($size);
        }
    }

    /**
     * Get upload max file size depending of the server limits
     *
     * @since 4.6.1
     *
     * @return integer - Max upload size bytes
     */
    public static function getMaxFileUploadSize()
    {
        return min(
            self::stringToBytes(ini_get('post_max_size')),
            self::stringToBytes(ini_get('upload_max_filesize'))
        );
    }

    /**
     * Generate random number with selected length
     *
     * @since 4.7.0
     *
     * @param  int $length        - Length of needed number
     * @param  int $excluded_rand - Excluded number from result
     * @return int                - Random number
     */
    public static function getRandomNumber($length = 3, $excluded_rand = 0)
    {
        $rand = '';

        for ($i = 1; $i <= $length; $i++) {
            $rand .= mt_rand($i > 1 ? 0 : 1, 9);
        }

        $rand          = (int) $rand;
        $excluded_rand = (int) $excluded_rand;

        if ($excluded_rand && $excluded_rand === $rand) {
            return self::getRandomNumber($length, $excluded_rand);
        }

        return $rand;
    }

    /**
     * Get list of pages
     *
     * @since 4.7.2 - Added $customLang, $force parameter
     * @since 4.7.1
     *
     * @param string $select        - List of necessary data
     * @param array  $where         - Condition of selection pages from database
     * @param string $options       - Additional SQL condition, like "ORDER BY" and etc.
     * @param array  $outputRowsMap - Mapping in output ['Key' => 'Value']
     * @param string $customLang    - Force to get multilingual paths by necessary language
     * @param bool   $force         - Force to get multilingual paths from database
     *
     * @return array
     */
    public static function getPages(
        $select        = '*',
        $where         = [],
        $options       = '',
        $outputRowsMap = [],
        $customLang    = '',
        $force         = false
    ) {
        global $rlDb, $config;

        $customLang = $customLang ?: (RL_LANG_CODE ?: $config['lang']);
        $condition  = array_merge(
            (array) $select,
            (array) $where,
            (array) $options,
            (array) $outputRowsMap,
            ['lang' => $customLang]
        );
        $condition = json_encode($condition);

        if (self::$pages[$condition] && !$force) {
            return self::$pages[$condition];
        }

        $additionalSelect = '';

        if ($config['multilingual_paths']
            && $customLang !== $config['lang']
            && $pathSelectIndex = array_search('Path', $select)
        ) {
            unset($select[$pathSelectIndex]);
            $additionalSelect = ", IF(`Path_{$customLang}` <> '', `Path_{$customLang}`, `Path`) AS `Path`";
        }

        /**
         * @since 4.8.1 - Added $additionalSelect parameter
         */
        $GLOBALS['rlHook']->load('phpGetPages', $select, $where, $options, $outputRowsMap, $additionalSelect);

        $sql = 'SELECT `' . implode('`, `', $select) . "`{$additionalSelect} FROM `{db_prefix}pages` ";

        $sql .= 'WHERE ';
        foreach ($where as $field => $value) {
            Valid::escape($value);
            $sql .= " (`{$field}` = '{$value}') AND";
        }
        $sql = substr($sql, 0, -3);

        if ($options !== null) {
            $sql .= ' ' . $options . ' ';
        }

        $pages = $rlDb->getAll($sql, $outputRowsMap);

        return self::$pages[$condition] = $pages;
    }

    /**
     * Get pages with "Active" status only
     *
     * @since 4.7.1
     *
     * @return array
     */
    public static function getActivePages()
    {
        return self::getPages('*', array('Status' => 'active'));
    }

    /**
     * Get pages with "Active" status and low priority in system.
     * They are not required the login of users and from list excluded major system pages.
     *
     * @since 4.7.1
     *
     * @return array
     */
    public static function getMinorPages()
    {
        $select       = array('ID', 'Key');
        $where        = array('Status' => 'active', 'Login'  => '0');
        $excludedKeys = array(
            'add_listing',
            'edit_listing',
            'remind',
            'confirm',
            'payment',
            'upgrade_listing',
            'listing_remove',
            'payment_history',
            '404',
            'view_details',
            'my_favorites',
            'print',
            'rss_feed',
        );

        $GLOBALS['rlHook']->load('phpGetMinorPages', $select, $where, $excludedKeys);

        return self::getPages($select, $where, "AND `Key` NOT IN ('" . implode("', '", $excludedKeys) . "')");
    }

    /**
     * Get location data by location query
     *
     * @since 4.8.0
     *
     * @param  mixed  $query          - Location search as a string, ex: "San Francisco, FL", or as an array with possible keys:
     *                                  REVERSE LOOKUP
     *                                  * latlng     - latitude and longitude as string '30.3390,10.9870' or as an array [30.3390,10.9870]
     *
     *                                  FILTRATION
     *                                  * country    - filter results by country or country code
     *                                  * state      - filter results by state
     *                                  * county     - filter results by county
     *                                  * city       - filter results by city
     *                                  * street     - filter results by street
     *                                  * postalcode - filter results by postal code
     *                                  * query      - additional filtration by query but nominatim recommend against combine it with other filters
     *
     * @param  bool   $addressDetails - Include address details data
     * @param  string $lang           - Response data language, ex: "en" or "en_GB"
     * @param  string $provider       - Force service provider, available: 'nominatim', 'google' and 'googlePlaces', googlePlaces case uses for js place autocomplete only
     * @return array                  - Location data (single or multiple levels), [0] index contains the most accurate data
     */
    public static function geocoding($query = null, $addressDetails = false, $lang = null, $provider = null)
    {
        global $config, $rlConfig;

        if (!$query) {
            return [];
        }

        if ($config['geocoding_restrict_by_country']) {
            if (is_string($query)) {
                $query = [
                    'query'   => $query,
                    'country' => $config['geocoding_restrict_by_country'],
                ];
            } elseif (is_array($query) && !$query['country']) {
                $query['country'] = $config['geocoding_restrict_by_country'];
            }
        }

        // Nominatim reverse fields mapping (differ from Nominatim search fields)
        $nominatim_reverse_mapping = [
            'road' => 'road',
            'suburb' => 'suburb',
            'city' => 'city',
            'county' => 'county',
            'state' => 'state',
            'country' => 'country',
            'postcode' => 'postalcode'
        ];

        // Google => Nominatim location keys mapping
        $google_mapping = array(
            'route'                       => 'road',
            'neighborhood'                => 'suburb',
            'locality'                    => 'city',
            'administrative_area_level_2' => 'county',
            'administrative_area_level_1' => 'state',
            'country'                     => 'country',
            'postal_code'                 => 'postalcode',
        );

        // ArcGis => Nominatim location keys mapping
        $arcgis_mapping = array(
            'StAddr'                      => 'road',
            'District'                    => 'suburb',
            'City'                        => 'city',
            'Subregion'                   => 'county',
            'Region'                      => 'state',
            'Country'                     => 'country',
            'CntryName'                   => 'country', // reverseGeocode mode
            'Postal'                      => 'postalcode',
        );

        // Yandex => Nominatim location keys mapping
        $yandex_mapping = array(
            'house'                       => 'house_number',
            'street'                      => 'road',
            'district'                    => 'suburb',
            'locality'                    => 'city',
            'area'                        => 'county',
            'province'                    => 'state',
            'country'                     => 'country',
            'postal_code'                 => 'postalcode',
        );

        $lang    = $lang ?: $config['lang'];
        $service = in_array($provider, array('arcgis', 'nominatim', 'google', 'googlePlaces'))
        ? $provider
        : $config['geocoding_provider'];

        switch ($service) {
            case 'arcgis':
            default:
                $host = 'https://geocode.arcgis.com/arcgis/rest/services/World/GeocodeServer/';
                $mode = 'findAddressCandidates';

                $params = array(
                    'f' => 'json',
                    'outFields'    => $addressDetails ? 'Addr_type,LongLabel,PlaceName,Type,Country,District,City,Postal' : 'LongLabel',
                    'langCode'     => $lang,
                    'maxLocations' => 5,
                );

                if (isset($query['latlng'])) {
                    $mode   = 'reverseGeocode';
                    $latlng = strpos($query['latlng'], ',') ? explode(',', $query['latlng']) : $query['latlng'];
                    $params['location'] = implode(',', array_reverse($latlng));

                    unset($params['outFields']);
                } elseif (is_array($query)) {
                    if ($query['query']) {
                        $params['singleLine'] = $query['query'];
                        unset($query['query']);
                    }
                    if ($query['country']) {
                        $params['countryCode'] = $query['country'];
                        unset($query['country']);
                    }

                    foreach ($query as $key => $value) {
                        $index = array_search($key, $arcgis_mapping) ?: $key;
                        $params[$index] = $value;
                    }
                } else {
                    $params['singleLine'] = $query;
                }

                $host .= $mode;
                break;

            case 'nominatim':
                $host   = 'https://nominatim.openstreetmap.org/';
                $mode   = 'search';
                $params = array(
                    'format'          => 'json',
                    'addressdetails'  => $addressDetails ? 1 : 0,
                    'accept-language' => $lang,
                );

                if (isset($query['latlng'])) {
                    $mode   = 'reverse';
                    $params['addressdetails'] = 1;
                    $latlng = strpos($query['latlng'], ',') ? explode(',', $query['latlng']) : $query['latlng'];

                    list($params['lat'], $params['lon']) = array_map('trim', array_map('trim', $latlng));
                } elseif (is_array($query)) {
                    if ($query['country']) {
                        $params['countrycodes'] = $query['country'];
                        unset($query['country']);
                    }

                    if (count($query) > 1 && $query['query']) {
                        unset($query['query']); // Combining with q parameter is not allowed
                    } else {
                       $params['q'] = $query['query'];
                       unset($query['query']);
                    }

                    $params = array_merge($params, $query);
                } else {
                    $params['q'] = $query;
                }

                $host .= $mode;
                break;

            case 'google':
                $host   = 'https://maps.googleapis.com/maps/api/geocode/json';
                $params = array(
                    'key'      => $config['google_server_map_key'],
                    'language' => $lang
                );

                if (isset($query['latlng'])) {
                    $latlng = is_array($query['latlng']) ? implode(',', $query['latlng']) : $query['latlng'];
                    $params['latlng'] = $latlng;
                } elseif (is_array($query)) {
                    if ($query['query']) {
                        $params['address'] = $query['query'];
                        unset($query['query']);
                    }

                    $filters = [];
                    foreach ($query as $key => $value) {
                        $index = array_search($key, $google_mapping) ?: $key;
                        $filters[$index] = $value;
                    }

                    $params['components'] = str_replace('=', ':', http_build_query($filters, '', '|'));
                } else {
                    $params['address'] = $query;
                }
                break;

            case 'googlePlaces':
                $host = 'https://maps.googleapis.com/maps/api/place/autocomplete/json';
                $params = array(
                    'key'      => $config['google_server_map_key'],
                    'language' => $lang,
                );

                if (is_array($query)) {
                    if ($query['query']) {
                        $params['input'] = $query['query'];
                        unset($query['query']);
                    }

                    $filters = [];
                    foreach ($query as $key => $value) {
                        $index = array_search($key, $google_mapping) ?: $key;
                        $filters[$index] = $value;
                    }

                    if ($filters) {
                        $params['components'] = str_replace('=', ':', http_build_query($filters, '', '|'));
                    }
                } else {
                    $params['input'] = $query;
                }
                break;

            case 'yandex':
                $host = 'https://geocode-maps.yandex.ru/1.x/';
                $mode = 'search';
                $params = array(
                    'apikey' => $config['yandex_geocoder_api_key'],
                    'lang'   => $lang,
                    'format' => 'json',
                );

                if (isset($query['latlng'])) {
                    $mode   = 'reverse';
                    $latlng = strpos($query['latlng'], ',') ? explode(',', $query['latlng']) : $query['latlng'];

                    $params['geocode'] = implode(',', array_map('trim', array_reverse($latlng)));
                } elseif (is_array($query)) {
                    if ($query['query']) {
                        $params['geocode'] = $query['query'];
                        unset($query['query']);
                    }
                } else {
                    $params['geocode'] = $query;
                }
                break;
        }

        $request  = $host . '?' . http_build_query($params);

        if ($service == 'nominatim') {
            $url_chars = array('%23' => '#');
            $request = strtr($request, $url_chars);
        }

        $response = self::getContent($request);

        if (!$response) {
            return [];
        }

        $data = json_decode($response);

        if (!$data) {
            return [];
        }

        $out = [];

        // Prepare data
        switch ($service) {
            case 'arcgis':
            default:
                if ($mode == 'reverseGeocode') {
                    // Apply main address item
                    $out[] = array(
                        'place_id' => '',
                        'lat'      => $data->location->y,
                        'lng'      => $data->location->x,
                        'type'     => 'address',
                        'location' => $data->address->Address,
                        'address'  => $data->address->LongLabel
                    );

                    foreach ($data->address as $key => $value) {
                        if (($type = $arcgis_mapping[$key]) && $value) {
                            $out[] = array(
                                'place_id' => '',
                                'lat'      => $data->location->y,
                                'lng'      => $data->location->x,
                                'type'     => $type,
                                'location' => $value,
                                'address'  => $data->address->ShortLabel
                            );
                        }
                    }
                } else {
                    foreach ($data->candidates as $location) {
                        $attr = $location->attributes;
                        $address = null;

                        if ($addressDetails) {
                            foreach ($attr as $key => $item) {
                                if (!$item) {
                                    continue;
                                }

                                $index = $arcgis_mapping[$key] ?: $key;
                                $address[$index] = $item;

                                if ($key == 'Country') {
                                    $code = strtolower($item);
                                }
                            }

                            if ($code) {
                                $address['country_code'] = $code;
                            }
                        }

                        $out[] = array(
                            'place_id' => '',
                            'lat'      => $location->location->y,
                            'lng'      => $location->location->x,
                            'type'     => $attr->Addr_type,
                            'location' => $attr->LongLabel ?: $location->address,
                            'address'  => $address
                        );

                        unset($address, $code);
                    }
                }

                break;

            case 'nominatim':
                if ($mode == 'reverse') {
                    $out[] = array(
                        'place_id' => $data->place_id,
                        'lat'      => $data->lat,
                        'lng'      => $data->lon,
                        'type'     => 'address',
                        'location' => current($data->address),
                        'address'  => $data->display_name
                    );

                    foreach ($data->address as $key => $value) {
                        if (($type = $nominatim_reverse_mapping[$key]) && $value) {
                            $out[] = array(
                                'place_id' => $data->place_id,
                                'lat'      => $data->lat,
                                'lng'      => $data->lon,
                                'type'     => $type,
                                'location' => $value,
                                'address'  => $data->display_name
                            );
                        }
                    }
                } else {
                    foreach ($data as $location) {
                        $out[] = array(
                            'place_id' => $location->place_id,
                            'lat'      => $location->lat,
                            'lng'      => $location->lon,
                            'type'     => $location->type,
                            'location' => $location->display_name,
                            'address'  => $addressDetails && is_object($location->address) ? get_object_vars($location->address) : ''
                        );
                    }
                }
                break;

            case 'google':
                if (strtolower($data->status) == 'ok') {
                    if ($config['geocode_request_limit_reached']) {
                        $rlConfig->setConfig('geocode_request_limit_reached', '');
                    }

                    $found_items = [];
                    $address = [];

                    foreach (array_reverse($data->results) as $location) {
                        if ($addressDetails) {
                            foreach ($location->address_components as $item) {
                                $index = $google_mapping[$item->types[0]] ?: $item->types[0];
                                $address[$index] = $item->long_name;

                                if ($item->types[0] == 'country') {
                                    $code = strtolower($item->short_name);
                                }
                            }

                            if ($code) {
                                $address['country_code'] = $code;
                            }
                        }

                        foreach (array_reverse($location->address_components) as $item) {
                            if (in_array($item->types[0], $found_items)) {
                                continue;
                            }

                            $item_type = $item->types[0];
                            $item_long_name = $item->long_name;

                            $found_items[] = $item_type;
                            break;
                        }

                        $out[] = array(
                            'place_id' => $location->place_id,
                            'lat'      => $location->geometry->location->lat,
                            'lng'      => $location->geometry->location->lng,
                            'type'     => $google_mapping[$item_type] ?: $item_type,
                            'location' => $item_long_name,
                            'address'  => $address ?: $location->formatted_address
                        );

                        unset($address, $code);
                    }

                    $out = array_reverse($out);
                } else {
                    $save_log = false;

                    if ($data->status == 'OVER_QUERY_LIMIT') {
                        $rlConfig->setConfig('geocode_request_limit_reached', '1');

                        if (!$config['geocode_request_limit_reached']) {
                            $config['geocode_request_limit_reached'] = 1;
                            $save_log = true;
                        }
                    } else {
                        $save_log = true;
                    }

                    if ($save_log) {
                        $error_message = 'Google Geocoding API request failed with status: "' . $data->status . '", ';
                        $error_message .= 'message: "' . $data->error_message . '"';
                        $GLOBALS['rlDebug']->logger($error_message);
                    }
                }
                break;

            case 'googlePlaces':
                if (strtolower($data->status) == 'ok') {
                    foreach ($data->predictions as $location) {
                        $out[] = array(
                            'place_id' => $location->place_id,
                            'lat'      => '',
                            'lng'      => '',
                            'type'     => $google_mapping[$location->types[0]] ?: $location->types[0],
                            'location' => $location->description,
                            'address'  => ''
                        );
                    }
                }
                break;

            case 'yandex':
                if ($data->error) {
                    $save_log = false;

                    if ($data->status == 'OVER_QUERY_LIMIT') {
                        $rlConfig->setConfig('geocode_request_limit_reached', '1');

                        if (!$config['geocode_request_limit_reached']) {
                            $config['geocode_request_limit_reached'] = 1;
                            $save_log = true;
                        }
                    } else {
                        $save_log = true;
                    }

                    if ($save_log) {
                        $error_message = 'Yandex Geocoding API request failed with status: "' . $data->error . '", ';
                        $error_message .= 'message: "' . $data->message . '"';
                        $GLOBALS['rlDebug']->logger($error_message);
                    }
                } else {
                    if ($config['geocode_request_limit_reached']) {
                        $rlConfig->setConfig('geocode_request_limit_reached', '');
                    }

                    if ($mode == 'reverse') {
                        $postalcode_added = false;
                        $country_added = false;

                        foreach ($data->response->GeoObjectCollection->featureMember as $item) {
                            $meta_data = $item->GeoObject->metaDataProperty->GeocoderMetaData;
                            $address_data = $meta_data->Address;
                            end($address_data->Components);
                            $last = current($address_data->Components);
                            $type = $yandex_mapping[$last->kind];

                            if (!$type
                                || $type == 'other'
                                || !$yandex_mapping[$meta_data->kind]
                                || !$last->name
                                || ($meta_data->kind == 'province'
                                    && $address_data->country_code == 'RU'
                                    && !$meta_data->AddressDetails->Country->AdministrativeArea
                                )
                            ) {
                                continue;
                            }

                            if ($addressDetails) {
                                foreach ($address_data->Components as $component) {
                                    $index = $yandex_mapping[$component->kind] && $yandex_mapping[$meta_data->kind]
                                    ? $yandex_mapping[$component->kind]
                                    : $component->kind;
                                    $address[$index] = $component->name;

                                    if ($meta_data->kind == 'country') {
                                        $code = strtolower($address_data->country_code);
                                    }
                                }

                                if ($code) {
                                    $address['country_code'] = $code;
                                }
                            }

                            $latlng = explode(' ', $item->GeoObject->Point->pos);

                            $out[$type] = array(
                                'place_id' => '',
                                'lat'      => $latlng[1],
                                'lng'      => $latlng[0],
                                'type'     => $type,
                                'location' => $last->name,
                                'address'  => $address ?: $meta_data->text
                            );

                            if ($type == 'country') {
                                $country_added = true;
                            }

                            // Assign postal code data if exists
                            if (isset($address_data->postal_code) && !$postalcode_added) {
                                $out['postalcode'] = array(
                                    'place_id' => '',
                                    'lat'      => $latlng[1],
                                    'lng'      => $latlng[0],
                                    'type'     => 'postalcode',
                                    'location' => $address_data->postal_code,
                                    'address'  => $meta_data->text
                                );
                                $postalcode_added = true;
                            }

                            unset($address, $code);
                        }

                        $out = array_values($out);

                        // Assign country data if missing
                        if (!$country_added) {
                            $location = $data->response->GeoObjectCollection->metaDataProperty->GeocoderResponseMetaData->Point->pos;
                            $country_data = $data->response->GeoObjectCollection->featureMember[0];
                            $address_data = $country_data->GeoObject->metaDataProperty->GeocoderMetaData->Address->Components[0];

                            if ($location) {
                                $latlng = explode(' ', $location);
                            }

                            $out[] = array(
                                'place_id' => '',
                                'lat'      => $latlng[1],
                                'lng'      => $latlng[0],
                                'type'     => 'country',
                                'location' => $address_data->name,
                                'address'  => $address_data->name
                            );
                        }
                    } else {
                        if ($data->response && $data->response->GeoObjectCollection) {
                            foreach ($data->response->GeoObjectCollection->featureMember as $location) {
                                $latlng = explode(' ', $location->GeoObject->Point->pos);
                                $meta_data = $location->GeoObject->metaDataProperty->GeocoderMetaData;
                                $location = array_reverse(array_map('trim', explode(',', $meta_data->text)));

                                $out[] = array(
                                    'place_id' => '',
                                    'lat'      => $latlng[1],
                                    'lng'      => $latlng[0],
                                    'type'     => $yandex_mapping[$meta_data->kind] ?: $meta_data->kind,
                                    'location' => implode(', ', $location),
                                    'address'  => $location->GeoObject->metaDataProperty->description
                                );

                                unset($latlng, $meta_data, $location);
                            }
                        }
                    }
                }
                break;
        }

        return $out;
    }

    /**
     * parse_url() function for multi-bytes character encodings
     *
     * @since 4.9.2 - $idnConversion parameter added
     * @since 4.8.1
     *
     * @param  string $url       - Url to parse
     * @param  int    $component - Components to retrieve
     * @return array             - Parsed url data
     */
    public static function parseURL($url, $component = -1, $idnConversion = false)
    {
        $encodedUrl = preg_replace_callback('%[^:/@?&=#]+%usD', function($matches) {
            return urlencode($matches[0]);
        }, $url);

        $parts = parse_url($encodedUrl, $component);

        if (is_array($parts) && count($parts) > 0) {
            foreach ($parts as $name => $value) {
                $value = urldecode($value);
                $value = $idnConversion ? self::idnToUtf8($value) : $value;
                $parts[$name] = $value;
            }
        }

        return $parts;
    }

    /**
     * Convert idn host to utf8 format
     *
     * @since 4.8.1
     *
     * @param  string $host - Host to convert
     * @return string       - Converted host
     */
    public static function idnToUtf8($host = '')
    {
        if (!$host = (string) $host) {
            return $host;
        }

        if (defined('INTL_IDNA_VARIANT_UTS46')) {
            $host = idn_to_utf8($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
        } else {
            $host = idn_to_utf8($host);
        }

        return $host;
    }

    /**
     * Get countries list with ISO codes
     *
     * @since 4.8.2
     *
     * @param  string $lang
     * @return array
     */
    public static function getCountries($lang = 'en')
    {
        return Countries::getNames($lang);
    }

    /**
     * Indicator of the request from the Google Page Speed Insights / Lighthouse
     *
     * @since 4.9.0
     *
     * @return bool
     */
    public static function isLighthouseRequest()
    {
        $googleUserAgents = ['Chrome-Lighthouse', 'Speed Insights'];

        foreach ($googleUserAgents as $googleUserAgent) {
            if (false !== strpos($_SERVER['HTTP_USER_AGENT'], $googleUserAgent)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Convert time to pretty human readable format
     *
     * @since 4.10.0 - $short and $parts parameters added
     * @since 4.9.3
     *
     * @param  string $date  - Date timestamp or mysql date string
     * @param  bool   $short - Short labels mode
     * @param  int    $parts - Number of parts of date (1: "1 month ago", 2: "1 month, 2 days ago")
     * @return string        - Human readable date, ex: "1 hour ago", "Yesterday" and so on
     */
    public static function toPrettyDateTime(?string $date = null, ?bool $short = false, ?int $parts = 1): string
    {
        if (!$date) {
            return '';
        }

        return Carbon::parse($date)->locale(RL_LANG_CODE)->diffForHumans(null, null, $short, $parts);
    }
}
