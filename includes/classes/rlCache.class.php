<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.10.1
 *  LICENSE: FL1KU6QLIUJA - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: bizlisting.xyz
 *  FILE: RLCACHE.CLASS.PHP
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

use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Flynax\Utils\Category;

/**
 * Cache class
 *
 * @since 4.10.1 - Removed "cache_categories_by_id" key
 * @since 4.9.3 - Added "cache_news_in_box" and "cache_news_categories" keys
 * @since 4.9.3 - Added the Symfony\Component\Cache\ component
 *
 * Available cache resources:
 * | cache_submit_forms                  - Submit forms
 * | cache_categories_by_type            - Categories by listing type, full list
 * | cache_categories_by_parent          - Categories by listing type, by parent includes subcategories
 * | cache_search_forms                  - Search forms by form key
 * | cache_search_fields                 - Search fields list by form key
 * | cache_featured_form_fields          - Featured form fields by category id
 * | cache_listing_titles_fields         - Listing titles form fields by category id
 * | cache_short_forms_fields            - Short form fields by category id
 * | cache_sorting_forms_fields          - Sorting form fields by category id
 * | cache_data_formats                  - Data formats by key
 * | cache_listing_statistics            - Listing statistics by listing type
 * | cache_categories_multilingual_paths - Paths of categories in another languages
 * | cache_news_categories               - Data of news categories
 */
class rlCache
{
    /**
     * @since 4.9.3
     * @var PhpFilesAdapter|MemcachedAdapter|ApcuAdapter
     */
    protected $cache;

    /**
     * Cache keys using for cache dividing
     * @since 4.9.3 - $divided_caches renamed to $dividedCaches
     * @var array
     */
    public $dividedCaches = [
        'cache_short_forms_fields',
        'cache_listing_titles_fields',
        'cache_data_formats',
        'cache_submit_forms',
        'cache_sorting_forms_fields',
    ];

    /**
     * @since 4.9.3 - List of allowed types of cache
     */
    public const ALLOWED_METHODS = ['file', 'memcached', 'apcu', 'redis'];

    /**
     * Cache class constructor
     */
    public function __construct()
    {
        global $reefless, $config, $rlConfig, $rlDb;

        $cacheMethod = $config['cache_method'] ?: $rlConfig->getConfig('cache_method');

        $reefless->loadClass('Categories');
        $reefless->loadClass('Common');

        try {
            switch ($cacheMethod) {
                case 'file':
                    $this->cache = new PhpFilesAdapter('', 0, RL_CACHE);
                    break;
                case 'memcached':
                    $this->cache = new MemcachedAdapter(
                        MemcachedAdapter::createConnection(
                            'memcached://' . RL_MEMCACHE_HOST . ':' . RL_MEMCACHE_PORT
                        )
                    );
                    break;
                case 'apcu':
                    $this->cache = new ApcuAdapter();
                    break;
                case 'redis':
                    if (RL_REDIS_USER && RL_REDIS_PASS) {
                        $redisAuthentication = 'redis:' . RL_REDIS_USER . ':' . RL_REDIS_PASS . '@?host[' . RL_REDIS_HOST . ':' . RL_REDIS_PORT . ']';
                    } else {
                        $redisAuthentication = 'redis://' . RL_REDIS_HOST . ':' . RL_REDIS_PORT;
                    }

                    $this->cache = new RedisAdapter(RedisAdapter::createConnection($redisAuthentication));
                    break;
            }

            /**
             * @since 4.9.3 - Added $cacheMethod parameter
             * @since 4.8.1
             */
            $GLOBALS['rlHook']->load('phpCacheConstruct', $this, $cacheMethod);
        } catch (Exception $e) {
            $GLOBALS['rlDebug']->logger('Cache error: ' . $e->getMessage());

            /**
             * Reset method to "file" and update cache
             * If previous method is not available anymore
             */
            if ($cacheMethod !== 'file') {
                $rlDb->updateOne(['fields' => ['Default' => 'file'], 'where'  => ['Key' => 'cache_method']], 'config');
                $this->cache = new PhpFilesAdapter('', 0, RL_CACHE);

                register_shutdown_function(function() {
                    $GLOBALS['rlCache']->update();
                });
            }
        }
    }

    /**
     * Set data to cache
     *
     * @since 4.9.3 - Added $ttl parameter
     * @since 4.5.0
     *
     * @param string $key          - Cache item key
     * @param array  $data         - Data array
     * @param string $listing_type - Used when need to update only specific listing type in bunch of listing types
     * @param int    $ttl          - Set lifetime of cache item in seconds (set 0 for permanently stored)
     */
    public function set($key, $data, $listing_type = null, $ttl = 0): bool
    {
        global $config, $rlDebug, $rlConfig, $reefless;

        if (!$key) {
            return true;
        }

        /**
         * @since 4.9.3 - Added $ttl parameter
         * @since 4.8.1
         */
        $GLOBALS['rlHook']->load('phpCacheSet', $this, $key, $data, $listing_type, $ttl);

        if (empty($config[$key])) {
            $hash = $reefless->generateHash();
            if (!$hash) {
                $rlDebug->logger("Cache cannot generate key, generateHash() doesn't generate anything.");
            } else {
                $cacheKey = $key . '_' . $hash;

                // Save cache item name to mapping
                $rlConfig->setConfig($key, $cacheKey);
                $config[$key] = $cacheKey;
            }
        }

        // Save only one and don't affect others
        if ($listing_type) {
            if ($listing_type && $GLOBALS['rlListingTypes']->types[$listing_type]) {
                $tmp = $this->get($key);
                $tmp[$listing_type] = $data[$listing_type];
                $data = $tmp;
            }
        }

        try {
            if ($config['cache_divided'] && in_array($key, $this->dividedCaches)) {
                $data      = (array) $data;
                $result    = false;
                $chunkKeys = [];

                foreach ($data as $itemID => $item) {
                    $chunkKey = $config[$key] . '_' . $itemID;

                    if ($this->saveDataInCache($chunkKey, $item, $ttl)) {
                        $result = true;
                        $chunkKeys[] = $chunkKey;
                    }
                }

                if ($result && $chunkKeys) {
                    return $this->saveDataInCache($config[$key], $chunkKeys, $ttl);
                }

                return $result;
            } else {
                return $this->saveDataInCache($config[$key], $data, $ttl);
            }
        } catch (InvalidArgumentException $e) {
            $rlDebug->logger('Cache cannot save data, key: "' . $key . '", error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Save data into cache storage
     *
     * @since 4.9.3
     *
     * @param  string  $key
     * @param  mixed   $data
     * @param  integer $ttl
     * @return boolean
     */
    protected function saveDataInCache($key, $data, $ttl = 0): bool
    {
        global $config, $rlDebug;

        try {
            $cacheItem = $this->cache->getItem($key);

            if ($ttl) {
                $cacheItem->expiresAfter($ttl);
            }

            $cacheItem->set($data);

            if ($this->cache->save($cacheItem)) {
                return true;
            } else {
                $rlDebug->logger('Cache cannot save data, key: "' . $key . '" with method ' . $config['cache_method']);
                return false;
            }
        } catch (\RuntimeException $e) {
            $rlDebug->logger('Cache cannot save data, key: "' . $key . '", error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get cache item
     *
     * @since 4.7.1 - $parent_ids parameter added
     *
     * @param string       $key        - Cache item key
     * @param integer      $id         - Cache item id
     * @param array        $type       - Listing type data
     * @param array|string $parent_ids - Parent ids as array or string of comma separated ids: 12,51,61
     *
     * @return array|bool              - Cache data
     */
    public function get($key = false, $id = false, $type = [], $parent_ids = null)
    {
        global $config;

        if ($parent_ids && is_string($parent_ids)) {
            $parent_ids = explode(',', $parent_ids);
            rsort($parent_ids);
        }

        $cacheKey = $this->getCacheKey($key, $id, $type, $parent_ids);

        if (!$key || !$cacheKey) {
            return false;
        }

        $out = null;

        /**
         * @since 4.8.1
         */
        $GLOBALS['rlHook']->load('phpCacheGetBeforeFetch', $out, $key, $id, $type, $parent_ids);

        if ($out) {
            return $out;
        }

        $content = null;

        try {
            if ($config['cache_divided'] && in_array($key, $this->dividedCaches)) {
                if ($id && $this->isCacheItemExists($cacheKey)) {
                    $cacheItem = $this->cache->getItem($cacheKey);
                    $content = $cacheItem->get();
                } else {
                    $chunksCacheItem = $this->cache->getItem($cacheKey);

                    foreach ($chunksCacheItem->get() as $chunkCacheKey) {
                        $cacheItem = $this->cache->getItem($chunkCacheKey);
                        $content[str_replace($cacheKey . '_', '', $chunkCacheKey)] = $cacheItem->get();
                    }
                }
            } else {
                $cacheItem = $this->cache->getItem($cacheKey);
                $content = $cacheItem->get();
            }
        } catch (InvalidArgumentException $e) {
            $GLOBALS['rlDebug']->logger('Cache cannot get data: ' . $e->getMessage());
            return false;
        }

        if ($id === false) {
            $out = $content;
        } elseif ($config['cache_divided'] && in_array($key, $this->dividedCaches) && $content) {
            $out = $content;
        } else {
            $out = $content[$type['Key']] ? $content[$type['Key']][$id] : $content[$id];

            if ($type
                && !$out
                && in_array(
                    $key,
                    array(
                        'cache_featured_form_fields',
                        'cache_listing_titles_fields',
                        'cache_short_forms_fields',
                        'cache_submit_forms',
                        'cache_sorting_forms_fields',
                    )
                )
            ) {
                if ($type['Cat_general_only']) {
                    $out = $content[$type['Cat_general_cat']];
                } elseif (isset($parent_ids)) {
                    foreach ($parent_ids as $parent_id) {
                        if ($out = $content[$parent_id]) {
                            break;
                        }
                    }
                } else {
                    $main_content = $content;
                    $categories_by_type = $this->get('cache_categories_by_type', false, $type);
                    $categories_by_type = $categories_by_type[$type['Key']];
                    $out = $this->matchParent($id, 'Parent_ID', $categories_by_type, $main_content);
                    $content = $main_content;
                    unset($main_content, $categories_by_type);
                }

                if (!$out) {
                    $out = $content[$type['Cat_general_cat']];
                }
            }
        }

        /**
         * @since 4.8.1
         */
        $GLOBALS['rlHook']->load('phpCacheGetAfterFetch', $out, $content, $key, $id, $type, $parent_ids);

        return $out;
    }

    /**
     * Remove cached data from storage
     *
     * @since 4.9.3
     *
     * @param string $key - Cache item key
     */
    public function delete(string $key = ''): bool
    {
        global $config;

        try {
            if ($key) {
                if ($cacheKey = $config[$key]) {
                    return $this->cache->delete($cacheKey);
                }

                return false;
            }

            return $this->cache->clear();
        } catch (InvalidArgumentException $e) {
            $GLOBALS['rlDebug']->logger('Cache cannot remove data: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Checks the availability of selected type of cache
     *
     * @since 4.9.3
     *
     * @return bool
     */
    public function isMethodAvailable(string $type): bool
    {
        if (!$type || !in_array($type, self::ALLOWED_METHODS)) {
            throw new InvalidArgumentException("Error: Invalid provided the type of cache.");
        }

        try {
            if ($type !== 'file' && !extension_loaded($type)) {
                return false;
            }

            switch ($type) {
                case 'file':
                    $cache = new PhpFilesAdapter('', 0, RL_CACHE);
                    break;
                case 'memcached':
                    $cache = new MemcachedAdapter(
                        MemcachedAdapter::createConnection('memcached://' . RL_MEMCACHE_HOST . ':' . RL_MEMCACHE_PORT)
                    );
                    break;
                case 'apcu':
                    $cache = new ApcuAdapter();
                    break;
                case 'redis':
                    if (RL_REDIS_USER && RL_REDIS_PASS) {
                        $redisAuthentication = 'redis:' . RL_REDIS_USER . ':' . RL_REDIS_PASS . '@?host[' . RL_REDIS_HOST . ':' . RL_REDIS_PORT . ']';
                    } else {
                        $redisAuthentication = 'redis://' . RL_REDIS_HOST . ':' . RL_REDIS_PORT;
                    }

                    $cache = new RedisAdapter(RedisAdapter::createConnection($redisAuthentication));
                    break;
            }

            $testItem = $cache->getItem('test');
            $testItem->set('test-value');
            $cache->save($testItem);
            $testResult = $cache->getItem('test');

            if ($testResult->get() === 'test-value') {
                $cache->deleteItem('test');
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get Cache Key
     * Defines the cache key to get data from; depending on listing type settings and cache availability
     *
     * @since 4.6.0
     *
     * @param  string $key       - Cache key
     * @param  int    $id        - Category ID
     * @param  array  $type      - Listing type data
     * @param  array  $parentIDs - Parent ids
     * @return string            - Cache key
     */
    public function getCacheKey($key, $id, $type = [], $parentIDs = [])
    {
        global $config;

        $cache_key = $config[$key];

        if ($id
            && $config['cache_divided']
            && in_array($key, $this->dividedCaches)
        ) {
            if ($type['Cat_general_only']) {
                $cache_key .= '_' . $type['Cat_general_cat'];
            } else {
                if (is_numeric($id)) {
                    if ($this->isCacheItemExists($cache_key, $id)) {
                        $cache_key .= '_' . $id;
                    } else {
                        $parentCacheKey = '';
                        if ($parentIDs) {
                            foreach ($parentIDs as $parent_id) {
                                if ($this->isCacheItemExists($cache_key, $parent_id)) {
                                    $parentCacheKey = $cache_key . '_' . $parent_id;
                                    break;
                                }
                            }
                        }

                        $cache_key = $parentCacheKey ?: $cache_key . '_' . $type['Cat_general_cat'];
                    }
                } else {
                    $cache_key .= '_' . $id;
                }
            }
        }

        return $cache_key;
    }

    /**
     * Check is the cache item exists
     *
     * @since 4.9.3
     *
     * @param  string $key - Cache key
     * @param  int    $id  - Chunk cache item ID
     * @return bool        - Exists status
     */
    protected function isCacheItemExists($key, $id = null): bool
    {
        $cacheItem = $this->cache->getItem($id ? ($key . '_' . $id) : $key);
        return $cacheItem->isHit();
    }

    /**
     * Match parent
     *
     * @param string $id      - Cache source
     * @param string $field   - Parent field name
     * @param array  $search  - Search resource
     * @param array  $content - Main content from cache
     */
    public function matchParent($id, $field = false, &$search = [], &$content = [])
    {
        if (!$id || !$field || !$search || !$content) {
            return false;
        }

        if ($search[$id][$field]) {
            if (!empty($content[$search[$id][$field]])) {
                return $content[$search[$id][$field]];
            }

            return $this->matchParent($search[$id][$field], $field, $search, $content);
        }

        return false;
    }

    /**
     * Update submit forms (cache_submit_forms)
     */
    public function updateSubmitForms(): bool
    {
        global $config, $rlDb;

        if (!$config['cache']) {
            return false;
        }

        $this->delete('cache_submit_forms');

        $sql = "SELECT `T1`.`Group_ID`, `T1`.`ID`, `T2`.`ID` AS `Category_ID`, `T3`.`Key` AS `Key`, `T3`.`Display` AS `Display`, ";
        $sql .= "`T3`.`Columns` AS `Columns`, `T3`.`Header` AS `Header`, ";
        $sql .= "`T1`.`Fields`, CONCAT('listing_groups+name+', `T3`.`Key`) AS `pName`, `T2`.`Type` AS `Listing_type` ";
        $sql .= "FROM `{db_prefix}listing_relations` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_groups` AS `T3` ON `T1`.`Group_ID` = `T3`.`ID` ";
        $sql .= "WHERE `T1`.`Group_ID` = '' OR `T3`.`Status` = 'active' ";
        $sql .= "ORDER BY `T1`.`Position`";

        $rows = $rlDb->getAll($sql);

        if (!$rows) {
            return false;
        }

        $form = [];
        foreach ($rows as $key => $value) {
            if (!empty($value['Fields'])) {
                $sql = "SELECT *, FIND_IN_SET(`ID`, '{$value['Fields']}') AS `Order`, ";
                $sql .= "CONCAT('listing_fields+name+', `Key`) AS `pName`, CONCAT('listing_fields+description+', `Key`) AS `pDescription`, ";
                $sql .= "CONCAT('listing_fields+default+', `Key`) AS `pDefault`, `Multilingual` ";
                $sql .= "FROM `{db_prefix}listing_fields` ";
                $sql .= "WHERE FIND_IN_SET(`ID`, '{$value['Fields']}' ) > 0 AND `Status` = 'active' ";
                $sql .= "ORDER BY `Order`";
                $fields = $rlDb->getAll($sql, 'Key');

                if (empty($fields)) {
                    unset($rows[$key]);
                } else {
                    $rows[$key]['Fields'] = $GLOBALS['rlCommon']->fieldValuesAdaptation($fields, 'listing_fields', $value['Listing_type']);
                }
            } else {
                $rows[$key]['Fields'] = false;
            }

            unset($field_ids, $fields, $field_info);

            // Reassign to form, collect by category ID
            $set = $form[$value['Category_ID']] ? count($form[$value['Category_ID']]) + 1 : 1;
            $index = $value['Key'] ?: 'nogroup_' . $set;
            $form[$value['Category_ID']][$index] = $rows[$key];
        }

        unset($rows);

        $this->set('cache_submit_forms', $form);

        return true;
    }

    /**
     * Update categories by listing type (cache_categories_by_type)
     * @return bool
     */
    public function updateCategoriesByType(): bool
    {
        global $config, $rlListingTypes, $rlDb;

        if (!$config['cache']) {
            return false;
        }

        $out = [];
        foreach ($rlListingTypes->types as $type) {
            $sql = "SELECT `" . implode("`, `", Category::getColumns()) . "` FROM `{db_prefix}categories` ";
            $sql .= "WHERE `Type` = '{$type['Key']}' AND `Status` = 'active' ";
            if ($type['Cat_hide_empty']) {
                $sql .= "AND `Count` > 0 ";
            }
            $categories = $rlDb->getAll($sql, 'ID');

            foreach ($categories as &$category) {
                $category['pName']  = "categories+name+{$category['Key']}";
                $category['pTitle'] = "categories+title+{$category['Key']}";
            }

            $out[$type['Key']] = $categories;
        }

        return $this->set('cache_categories_by_type', $out);
    }

    /**
     * Update categories by listing type, organized by parent (cache_categories_by_parent)
     * @return bool
     */
    public function updateCategoriesByParent(): bool
    {
        global $config, $rlListingTypes;

        if (!$config['cache']) {
            return false;
        }

        $out  = [];
        foreach ($rlListingTypes->types as $type) {
            $out[$type['Key']] = $this->getChildCat($type);
        }
        return $this->set('cache_categories_by_parent', $out);
    }

    /**
     * Update multilingual paths of categories
     *
     * @since  4.8.0
     *
     * @return bool
     */
    public function updateCategoriesMultiLingualPaths(): bool
    {
        global $config;

        if (!$config['cache'] || !$config['multilingual_paths']) {
            return false;
        }

        $sql = "SELECT `T1`.`ID`";

        foreach ($GLOBALS['languages'] as $langKey => $langData) {
            if ($langKey === $config['lang']) {
                continue;
            }

            $sql .= ", `T1`.`Path_{$langKey}`";
        }

        $sql .= " FROM `{db_prefix}categories` AS `T1` ";
        $sql .= "WHERE `T1`.`Status` = 'active'";
        $categories = $GLOBALS['rlDb']->getAll($sql, 'ID');

        return $this->set('cache_categories_multilingual_paths', $categories);
    }

    /**
     * Call all related methods for updating categories data
     */
    public function updateCategories(): void
    {
        $this->updateCategoriesByType();
        $this->updateCategoriesByParent();
        $this->updateCategoriesMultiLingualPaths();
    }

    /**
     * Update search forms by form key (cache_search_forms)
     * @return bool
     */
    public function updateSearchForms(): bool
    {
        global $config, $reefless, $rlDb;

        if (!$config['cache']) {
            return false;
        }

        $sql = "SELECT `T1`.`Category_ID`, `T1`.`Group_ID`, `T1`.`Fields`, ";
        $sql .= "`T2`.`Key` AS `Group_key`, `T2`.`Display`, ";
        $sql .= "`T3`.`Type` AS `Listing_type`, `T3`.`Key` AS `Form_key`, `T3`.`With_picture` ";
        $sql .= "FROM `{db_prefix}search_forms_relations` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_groups` AS `T2` ON `T1`.`Group_ID` = `T2`.`ID` AND `T2`.`Status` = 'active' ";
        $sql .= "LEFT JOIN `{db_prefix}search_forms` AS `T3` ON `T1`.`Category_ID` = `T3`.`ID` ";
        $sql .= "WHERE `T3`.`Status` = 'active' ";
        $sql .= "ORDER BY `Position` ";

        $GLOBALS['rlHook']->load('phpCacheUpdateSearchFormsGetRelations', $sql); // >= v4.3

        $relations = $rlDb->getAll($sql);

        if (!$relations) {
            $out = array(1);
        }

        $reefless->loadClass('Categories');

        // Populate field information
        foreach ($relations as $key => $value) {
            if (!$value) {
                continue;
            }

            $sql = "SELECT `ID`, `Key`, `Type`, `Default`, `Values`, `Condition`, `Autocomplete`, ";
            $sql .= "CONCAT('listing_fields+name+', `Key`) AS `pName`, ";
            $sql .= "`Multilingual`, `Opt1`, `Opt2`, FIND_IN_SET(`ID`, '{$value['Fields']}') AS `Order` ";
            $sql .= "FROM `{db_prefix}listing_fields` ";
            $sql .= "WHERE FIND_IN_SET(`ID`, '{$value['Fields']}' ) > 0 AND `Status` = 'active' ";
            $sql .= "ORDER BY `Order`";
            $fields = $rlDb->getAll($sql);

            if ($value['Group_key']) {
                $relations[$key]['pName'] = 'listing_groups+name+' . $value['Group_key'];
            }
            $relations[$key]['Fields'] = empty($fields) ? false : $GLOBALS['rlCommon']->fieldValuesAdaptation($fields, 'listing_fields', $value['Listing_type']);

            $out[$value['Form_key']][] = $relations[$key];
        }

        $GLOBALS['rlHook']->load('phpCacheUpdateSearchFormsBeforeSave', $out, $relations); // >= v4.3

        unset($relations);

        $this->set('cache_search_forms', $out);

        return true;
    }

    /**
     * Update search fields list by form key (cache_search_fields)
     * @return bool
     */
    public function updateSearchFields(): bool
    {
        global $config, $rlDb;

        if (!$config['cache']) {
            return false;
        }

        $sql = "SELECT `T1`.`Category_ID`, `T1`.`ID`, `T1`.`Fields`, `T2`.`Key` AS `Form_key` ";
        $sql .= "FROM `{db_prefix}search_forms_relations` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}search_forms` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
        $sql .= "WHERE `T2`.`Status` = 'active' ";
        $sql .= "ORDER BY `Position` ";
        $relations = $rlDb->getAll($sql);

        if (!$relations) {
            return false;
        }

        $out = [];
        foreach ($relations as $value) {
            $sql = "SELECT `ID`, `Key`, `Type`, `Default`, `Values`, `Condition`, `Details_page`, `Opt1`, `Opt2`, ";
            $sql .= "`Multilingual`, FIND_IN_SET(`ID`, '{$value['Fields']}') AS `Order` ";
            $sql .= "FROM `{db_prefix}listing_fields` ";
            $sql .= "WHERE FIND_IN_SET(`ID`, '{$value['Fields']}' ) > 0 AND `Status` = 'active' ";
            $sql .= "ORDER BY `Order`";
            $fields = $rlDb->getAll($sql, 'Key');

            $out[$value['Form_key']] = array_merge($out[$value['Form_key']] ?: array(), $fields);
            unset($fields);
        }
        unset($relations);

        $this->set('cache_search_fields', $out);

        return true;
    }

    /**
     * Update featured form fields by category id (cache_featured_form_fields)
     * @return bool
     */
    public function updateFeaturedFormFields(): bool
    {
        return $GLOBALS['config']['cache']
            ? $this->set('cache_featured_form_fields', self::getFieldsInForm('featured'))
            : false;
    }

    /**
     * Update listing title form fields by category id (cache_listing_titles_fields)
     * @return bool
     */
    public function updateTitlesFormFields(): bool
    {
        return $GLOBALS['config']['cache']
            ? $this->set('cache_listing_titles_fields', self::getFieldsInForm('titles'))
            : false;
    }

    /**
     * Update listing title form fields by category id (cache_short_forms_fields)
     * @return bool
     */
    public function updateShortFormFields(): bool
    {
        return $GLOBALS['config']['cache']
            ? $this->set('cache_short_forms_fields', self::getFieldsInForm('short'))
            : false;
    }

    /**
     * Update listing sorting form fields by category id (cache_sorting_forms_fields)
     * @since  4.5.2
     * @return bool
     */
    public function updateSortingFormFields(): bool
    {
        return $GLOBALS['config']['cache']
            ? $this->set('cache_sorting_forms_fields', self::getFieldsInForm('sorting'))
            : false;
    }

    /**
     * Call all methods related to forms
     */
    public function updateForms()
    {
        $this->updateSubmitForms();
        $this->updateSearchForms();
        $this->updateSearchFields();
        $this->updateFeaturedFormFields();
        $this->updateTitlesFormFields();
        $this->updateShortFormFields();
        $this->updateSortingFormFields();
    }

    /**
     * Update data formats by key (cache_data_formats)
     */
    public function updateDataFormats()
    {
        global $config, $rlDb;

        if (!$config['cache']) {
            return false;
        }

        $this->delete('cache_data_formats');

        $rlDb->setTable('data_formats');

        /* DO NOT SET ANOTHER FIELD FOR ORDER, ID ONLY */
        $data = $rlDb->fetch(
            ['ID', 'Parent_ID', 'Key`, CONCAT("data_formats+name+", `Key`) AS `pName', 'Position', 'Default'],
            ['Status' => 'active', 'Plugin' => ''],
            'ORDER BY `ID`, `Key`'
        );

        /**
         * @since 4.8.1
         */
        $GLOBALS['rlHook']->load('phpCacheUpdateDataFormats', $this, $data);

        $out = [];
        foreach ($data as $key => $value) {
            if (!$value['Key']) {
                continue;
            }

            if (!array_key_exists($data[$key]['Key'], $out) && empty($data[$key]['Parent_ID'])) {
                $out[$data[$key]['Key']] = array();
                $df_info[$data[$key]['ID']] = $data[$key]['Key'];
            } else {
                if (!$df_info[$data[$key]['Parent_ID']]) {
                    continue;
                }

                $out[$df_info[$data[$key]['Parent_ID']]][] = $data[$key];
            }
        }

        unset($data, $df_info);
        $this->set('cache_data_formats', $out);
    }

    /**
     * Update statistics box data (cache_listing_statistics)
     *
     * @since 4.8.2
     *
     * @param  string $listingType - Listing type key
     * @return bool
     */
    public function updateStatistics($listingType = false): bool
    {
        if (!$GLOBALS['config']['cache']) {
            return false;
        }

        $out = $GLOBALS['rlListingTypes']->statisticsBlock(true, $listingType);

        return $this->set('cache_listing_statistics', $out, $listingType);
    }

    /**
     * Update news box data (cache_news_in_box)
     *
     * @since 4.9.3
     *
     * @return bool
     */
    public function updateNewsInBox(): bool
    {
        if (!$GLOBALS['config']['cache']) {
            return false;
        }

        $GLOBALS['reefless']->loadClass('News');
        return $this->set('cache_news_in_box', $GLOBALS['rlNews']->get(null, null, null, false, false));
    }

    /**
     * Update news categories (cache_news_categories)
     *
     * @since 4.9.3
     *
     * @return bool
     */
    public function updateNewsCategories(): bool
    {
        if (!$GLOBALS['config']['cache']) {
            return false;
        }

        $GLOBALS['reefless']->loadClass('News');
        return $this->set('cache_news_categories', $GLOBALS['rlNews']->getCategories(0, false, false));
    }

    /**
     * Update frontend phrases in cache (cache_phrases_frontend)
     *
     * @since 4.10.0
     *
     * @return bool
     */
    public function updateFrontendPhrases(): bool
    {
        return $GLOBALS['config']['cache_phrases'] ? $this->_updatePhrases('frontend') : false;
    }

    /**
     * Update admin phrases in cache (cache_phrases_admin)
     *
     * @since 4.10.0
     *
     * @return bool
     */
    public function updateAdminPhrases(): bool
    {
        return $GLOBALS['config']['cache_phrases'] ? $this->_updatePhrases('admin') : false;
    }

    /**
     * Update category phrases in cache (cache_phrases_category)
     *
     * @since 4.10.0
     *
     * @return bool
     */
    public function updateCategoryPhrases(): bool
    {
        return $GLOBALS['config']['cache_phrases'] ? $this->_updatePhrases('category') : false;
    }

    /**
     * Update all phrases in cache
     *
     * @since 4.10.0
     *
     * @return bool
     */
    public function updatePhrases(): bool
    {
        return $GLOBALS['config']['cache_phrases'] ? $this->_updatePhrases('all') : false;
    }

    /**
     * Update phrases in cache by scope
     * Cache keys: cache_phrases_frontend, cache_phrases_admin, cache_phrases_category
     *
     * @since  4.10.0
     *
     * @param  string $scope - Possible scope: all, frontend, admin, category
     * @return bool
     */
    protected function _updatePhrases(string $scope): bool
    {
        global $rlLang;

        if (!$scope || !in_array($scope, ['all', 'frontend', 'admin', 'category'])) {
            throw new InvalidArgumentException("Error: Invalid provided the scope of phrases cache.");
        }

        $languages = $rlLang->getLanguagesList('active', false);
        $jsKeys    = [];

        $sql         = "WHERE `Status` = 'active'";
        $frontendSQL = $sql . " AND `Module` IN ('frontEnd', 'common', 'box')";
        $adminSQL    = $sql . " AND `Module` IN ('admin','common')";
        $categorySQL = $sql . " AND `Module` = 'category'";

        foreach ($languages as $languageCode => $language) {
            if ($scope === 'all' || $scope === 'frontend') {
                $phrases = $rlLang->preparePhrases("{$frontendSQL} AND `Code` = '{$languageCode}'", $jsKeys, false, false);
                $this->set("cache_phrases_frontend_{$languageCode}", $phrases);
            }

            if ($scope === 'all' || $scope === 'admin') {
                $phrases = $rlLang->preparePhrases("{$adminSQL} AND `Code` = '{$languageCode}'", $jsKeys, false, false);
                $this->set("cache_phrases_admin_{$languageCode}", $phrases);
            }

            if ($scope === 'all' || $scope === 'category') {
                $phrases = $rlLang->preparePhrases("{$categorySQL} AND `Code` = '{$languageCode}'", $jsKeys, false, false);
                $this->set("cache_phrases_category_{$languageCode}", $phrases);
            }
        }

        return true;
    }

    /**
     * Update phrases by automatically detected scopes
     *
     * @since  4.10.0
     *
     * @param  array $phrases          - List of phrases
     * @param  bool  $updateCacheLater - Update cache later once or immediately
     * @return bool
     */
    public function updatePhrasesByScopes(array $phrases, bool $updateCacheLater = true): bool
    {
        if (!$GLOBALS['config']['cache_phrases'] || !$phrases) {
            return false;
        }

        static $updateFrontendPhrases, $updateAdminPhrases, $updateCategoryPhrases, $updateAllPhrases;

        $frontendPhrases = (bool) array_filter($phrases, function($phrase) {
            return $phrase['Module'] === 'frontEnd';
        });
        $commonPhrases = (bool) array_filter($phrases, function($phrase) {
            return $phrase['Module'] === 'common';
        });
        $adminPhrases = (bool) array_filter($phrases, function($phrase) {
            return $phrase['Module'] === 'admin';
        });
        $categoryPhrases = (bool) array_filter($phrases, function($phrase) {
            return $phrase['Module'] === 'category';
        });
        if ($frontendPhrases || $commonPhrases) {
            if ($updateCacheLater) {
                if (is_null($updateFrontendPhrases)) {
                    $updateFrontendPhrases = true;

                    register_shutdown_function(function() {
                        $this->updateFrontendPhrases();
                    });
                }
            } else {
                $this->updateFrontendPhrases();
            }
        }
        if ($adminPhrases || $commonPhrases) {
            if ($updateCacheLater) {
                if (is_null($updateAdminPhrases)) {
                    $updateAdminPhrases = true;

                    register_shutdown_function(function() {
                        $this->updateAdminPhrases();
                    });
                }
            } else {
                $this->updateAdminPhrases();
            }
        }
        if ($categoryPhrases) {
            if ($updateCacheLater) {
                if (is_null($updateCategoryPhrases)) {
                    $updateCategoryPhrases = true;

                    register_shutdown_function(function() {
                        $this->updateCategoryPhrases();
                    });
                }
            } else {
                $this->updateCategoryPhrases();
            }
        }
        if (!$frontendPhrases && !$commonPhrases && !$categoryPhrases) {
            if ($updateCacheLater) {
                if (is_null($updateAllPhrases)) {
                    $updateAllPhrases = true;

                    register_shutdown_function(function() {
                        $this->updatePhrases();
                    });
                }
            } else {
                $this->updatePhrases();
            }
        }

        return true;
    }

    /**
     * Update all system cache
     */
    public function update(): void
    {
        $this->delete();

        $this->updateDataFormats();
        $this->updateSubmitForms();

        $this->updateCategoriesByType();
        $this->updateCategoriesByParent();
        $this->updateCategoriesMultiLingualPaths();

        $this->updateSearchForms();
        $this->updateSearchFields();

        $this->updateFeaturedFormFields();
        $this->updateTitlesFormFields();
        $this->updateShortFormFields();
        $this->updateSortingFormFields();

        $this->updateStatistics();
        $this->updateNewsInBox();
        $this->updateNewsCategories();

        $this->updatePhrases();

        /**
         * @since 4.8.1
         */
        $GLOBALS['rlHook']->load('phpCacheUpdate', $this);
    }

    /**
     * Get children categories by parent
     *
     * @since 4.9.3 - Removed $parent, $data parameters
     *
     * @param  array $type - Listing type info
     * @return array
     */
    public function getChildCat(array $type): array
    {
        global $rlDb;

        $result   = [];
        $maxLevel = 0;

        $sql = "SELECT `" . implode("`, `", Category::getColumns()) . "` FROM `{db_prefix}categories` ";
        $sql .= "WHERE `Type` = '{$type['Key']}' AND `Status` = 'active' ";
        if ($type['Cat_hide_empty']) {
            $sql .= "AND `Count` > 0 ";
        }
        $sql .= "ORDER BY `Position`";
        $categories = $rlDb->getAll($sql, 'ID');

        // Adapt results of categories
        foreach ($categories as &$category) {
            // Save max level of categories
            if ((int) $category['Level'] > $maxLevel) {
                $maxLevel = (int) $category['Level'];
            }

            $category['pName']  = "categories+name+{$category['Key']}";
            $category['pTitle'] = "categories+title+{$category['Key']}";
        }
        unset($category);

        // Add first level of categories with parentID = 0
        $result[0] = Category::getSubCategoriesByParentID(0, $categories, $maxLevel, $type);

        // Add all another categories by ID as parent ID
        foreach ($categories as $category) {
            $categoryID = (int) $category['ID'];

            if ($subCategories = Category::getSubCategoriesByParentID($categoryID, $categories, $maxLevel, $type)) {
                $result[$categoryID] = $subCategories;
            }
        }

        return $result;
    }

    /**
     * Get list of configured forms
     *
     * @since 4.9.3
     *
     * @param  string $form - Key of form (possible value: featured|short|titles|sorting)
     * @return array
     */
    public static function getFieldsInForm(string $form): array
    {
        global $rlDb, $rlHook, $reefless, $rlCategories;

        if (!$form || !in_array($form, ['featured', 'short', 'titles', 'sorting'])) {
            throw new InvalidArgumentException("Error: Invalid provided key of the form.");
        }

        $excludeColumns = ['ID', 'Values', 'Add_page', 'Required', 'Map', 'Autocomplete', 'Status', 'Readonly'];

        static $columns = null;

        if (is_null($columns)) {
            $rlHook->load('phpPreGetFieldsInForm', $excludeColumns);

            $where = '';
            if ($excludeColumns) {
                $where .= "WHERE `Field` NOT IN ('" . implode("', '", $excludeColumns) . "') ";
            }

            // Get necessary list of fields from listing fields table
            $columns = $rlDb->getAll("SHOW COLUMNS FROM `{db_prefix}listing_fields` {$where}", [null, 'Field']);
        }

        $table = '{db_prefix}';
        switch ($form) {
            case 'titles':
                $table .= 'listing_titles';
                break;
            case 'short':
                $table .= 'short_forms';
                break;
            case 'sorting':
                $table .= 'sorting_forms';
                break;
            default:
                $table .= "{$form}_form";
                break;
        }

        // Get list of categories which have configured forms
        $categories = $rlDb->getAll("SELECT `Category_ID` AS `ID` FROM `{$table}` GROUP BY `Category_ID`");

        $reefless->loadClass('Categories');
        $priceOptions = $rlCategories->getDF('price_options');

        $out = [];
        foreach ($categories as $category) {
            // Get list of fields in form
            $sql = "SELECT `T2`.`" . implode("`, `T2`.`", $columns) . "` FROM `{$table}` AS `T1` ";
            $sql .= "LEFT JOIN `{db_prefix}listing_fields` AS `T2` ON `T1`.`Field_ID` = `T2`.`ID` ";
            $sql .= "WHERE `T1`.`Category_ID` = {$category['ID']} ORDER BY `T1`.`Position`";

            if ($fields = $rlDb->getAll($sql, 'Key')) {
                foreach ($fields as &$field) {
                    if ($field['Type'] == 'price' && $field['Opt1']) {
                        $contactFormOption = reset(array_filter($priceOptions, function ($option) {
                            return $option['Default'];
                        }));

                        $field['Contact_form_option'] = $contactFormOption['Key'] ?? false;
                    }
                }

                $out[$category['ID']] = $fields;
            }
        }

        return $out;
    }

    /**
     * Get a relevant key of cache with phrases by SQL
     *
     * @since 4.10.0
     *
     * @param  string $sql - SQL code with where criteria
     * @return string
     */
    public static function getPhrasesCacheKey(string $sql): string
    {
        $cacheKey = '';
        if (false !== strpos($sql, "(`Module` = 'frontEnd' OR `Module` = 'common')")) {
            $cacheKey = 'cache_phrases_frontend';
        } elseif (false !== strpos($sql, "`Module` IN ('admin','common')")) {
            $cacheKey = 'cache_phrases_admin';
        } elseif (false !== strpos($sql, "AND `Module` = 'category'")) {
            $cacheKey = 'cache_phrases_category';
        }

        preg_match("/AND `Code` = '([a-z]{2})'/", $sql, $matches);
        $cacheKey .= '_' . (!empty($matches[1]) ? $matches[1] : RL_LANG_CODE);

        return $cacheKey;
    }

    /*** DEPRECATE METHODS ***/

    /**
     * Remove cache files by cache item key
     *
     * @deprecated 4.9.3
     * @since      4.7.2 - Default value added to $key parameter
     * @since      4.7.1
     *
     * @param  string $key - Cache item key
     */
    public function removeFiles($key = null)
    {}

    /**
     * Check is the cache file exists
     *
     * @deprecated 4.9.3
     * @since      4.7.1
     *
     * @param  string  $key - Cache key
     * @param  integer $id  - Cache item ID
     * @return boolean      - Exists status
     */
    private function isCacheFileExists($key, $id)
    {}

    /**
     * Connect to memcache server
     *
     * @deprecated 4.9.3
     * @since 4.5.0
     *
     * @param string $host
     * @param int    $port
     *
     * @return bool
     */
    public function memcacheConnect($host = RL_MEMCACHE_HOST, $port = RL_MEMCACHE_PORT)
    {}

    /**
     * Memcache object
     * @deprecated 4.9.3
     * @var object
     */
    public $memcache_obj;

    /**
     * System cache keys
     * @var array
     * @deprecated 4.9.3
     * @since      4.8.0 - Added "cache_categories_multilingual_paths" resource
     * @since      4.7.2
     */
    public $cacheKeys = [];

    /**
     * Update categories by id, full list (cache_categories_by_id)
     * @deprecated 4.10.1
     * @return bool
     */
    public function updateCategoriesByID()
    {}
}
