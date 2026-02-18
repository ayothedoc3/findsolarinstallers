<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.10.1
 *  LICENSE: FL1KU6QLIUJA - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: bizlisting.xyz
 *  FILE: CATEGORY.PHP
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

/**
 * @since 4.6.0
 */
class Category
{
    /**
     * Get category data by ID/Path
     *
     * @param  int    $id
     * @param  string $path
     * @param  bool   $getLangKeys - Get system phrases of category data
     *                             - Set FALSE to increase loading and decrease memory usage, if phrases aren't needed
     * @return array
     */
    public static function getCategory($id = 0, $path = '', $getLangKeys = true)
    {
        global $listing_type, $rlHook, $rlDb, $config, $page_info;

        $id       = (int) $id;
        $category = [];

        $GLOBALS['rlValid']->sql($path);

        if (!$id && !$path) {
            return $category;
        }

        // @since 4.9.3 - Added 'Status' column
        $select  = ['ID', 'Parent_ID', 'Parent_IDs', 'Parent_Keys', 'Path', 'Key', 'Count', 'Lock', 'Type', 'Level', 'Add', 'Status'];
        $where   = [];
        $options = '';

        if (defined('REALM') && REALM == 'admin') {
            $options = "AND `Status` <> 'trash'";
        } else {
            $where = ['Status' => 'active'];
        }

        if ($id) {
            $where['ID'] = $id;
        } elseif ($path) {
            $where['Path'] = trim($path, '/');

            if (!$listing_type && $page_info && 'listing_type' === $page_info['Controller']) {
                $listingTypeKey = str_replace('lt_', '', $page_info['Key']);
                $listing_type   = $GLOBALS['rlListingTypes']->types[$listingTypeKey];
            }

            if ($listing_type['Key']) {
                $where['Type'] = $listing_type['Key'];
            }
        }

        if ($config['multilingual_paths'] && RL_LANG_CODE !== $config['lang']) {
            foreach ($GLOBALS['languages'] as $languageKey => $languageData) {
                if ($languageKey === $config['lang']) {
                    continue;
                }

                $select[] = 'Path_' . $languageKey;
            }

            if ($path) {
                unset($where['Path']);
                $options .= " AND (`Path` = '{$path}' OR `Path_" . RL_LANG_CODE . "` = '{$path}')";
            }
        }

        /**
         * @since 4.8.0
         */
        $rlHook->load('phpPreGetCategoryData', $id, $path, $select, $where, $options, $getLangKeys);

        $category = $rlDb->fetch($select, $where, $options, null, 'categories', 'row');

        /**
         * Re-assign path of page if selected another language and enabled the option "Multilingual paths"
         * to use correct path for all internal urls
         */
        if ($config['multilingual_paths'] && RL_LANG_CODE !== $config['lang'] && $category['Path_' . RL_LANG_CODE]) {
            $category["Path_{$config['lang']}"] = $category['Path'];
            $category['Path'] = $category['Path_' . RL_LANG_CODE] ?: $category['Path'];
        }

        if (empty($category)) {
            return (array) $category;
        }

        if ($getLangKeys) {
            $category = $GLOBALS['rlLang']->replaceLangKeys(
                $category,
                'categories',
                ['name', 'title', 'des', 'meta_description', 'h1']
            );
        }

        /**
         * @since 4.8.0
         */
        $rlHook->load('phpAfterGetCategoryData', $category, $id, $path, $select, $where, $options, $getLangKeys);

        return (array) $category;
    }

    /**
     * Get categories by type and parent ID
     *
     * @since 4.10.1 - Added parameter $add_urls
     *
     * @param  bool $type                     - Listing type key
     * @param  int  $parent_id                - Parent category id to get it's child
     * @param  int  $include_sub_categories   - Include subcategories:
     *                                           0 - doesn't include
     *                                           1 - include flag only
     *                                           2 - include full data
     * @param  mixed $include_user_categories - Include user categories by user id or false
     * @param  bool  $from_db                 - Get data from DB to avoid empty categories caching
     * @param  bool  $add_urls                - Include URLs or not
     *
     * @return array - Categories data
     */
    public static function getCategories(
        $type = false,
        $parent_id = 0,
        $include_sub_categories = 0,
        $include_user_categories = false,
        $from_db = false,
        $add_urls = false
    ) {
        global $config, $reefless;

        // validate data
        Valid::escape($type, true);

        if (!$type) {
            $GLOBALS['rlDebug']->logger(__METHOD__ . '() failed, no "type" parameter passed');
            return false;
        }

        // fields to fetch
        $fields = array('ID', 'Path', 'Count', 'Key', 'Type', 'Lock', 'Add', 'Add_sub');

        if ($config['multilingual_paths'] && RL_LANG_CODE !== $config['lang']) {
            foreach ($GLOBALS['languages'] as $languageKey => $languageData) {
                if ($languageKey === $config['lang']) {
                    continue;
                }

                $fields[] = 'Path_' . $languageKey;
            }
        }

        $GLOBALS['rlHook']->load(
            'getCategoryLevelFields',
            $fields,
            $type,
            $parent_id,
            $include_sub_categories,
            $include_user_categories
        );

        // fetch type info
        $type_info = $GLOBALS['rlListingTypes']->types[$type];

        // disable user category submition
        if (!$type_info['Cat_custom_adding']) {
            unset($fields[array_search('Add', $fields)]);
        }

        // get from cache
        if ($config['cache'] && (!defined('REALM') || (defined('REALM') && REALM != 'admin')) && !$from_db) {
            $categories = array_values(
                (array) $GLOBALS['rlCache']->get('cache_categories_by_parent', $parent_id, $type_info)
            );

            // include sub-categories mode
            if ($include_sub_categories > 0) {
                $fields[] = 'sub_categories';
                $fields[] = 'sub_categories_calc';
            }

            // remove unnecessary data
            foreach ($categories as &$category) {
                foreach ($category as $key => $value) {
                    if (!in_array($key, $fields)) {
                        unset($category[$key]);
                    }
                }

                /**
                 * Re-assign path of page if selected another language and enabled the option "Multilingual paths"
                 * to use correct path for all internal urls
                 */
                if ($config['multilingual_paths']
                    && RL_LANG_CODE !== $config['lang']
                    && $category['Path_' . RL_LANG_CODE]
                ) {
                    $category["Path_{$config['lang']}"] = $category['Path'];
                    $category['Path'] = $category['Path_' . RL_LANG_CODE];
                }

                if ($include_sub_categories === 1) {
                    $category['sub_categories_calc'] = is_array($category['sub_categories']) ? count($category['sub_categories']) : 0;
                }
            }
        }
        // get from db
        else {
            $select = $fields;
            $select[] = "ID`, CONCAT('categories+name+', `Key`) AS `pName";
            $where = array(
                'Status'    => 'active',
                'Parent_ID' => $parent_id,
                'Type'      => $type,
            );
            $add_where = '';

            if (!$from_db && $type_info['Cat_hide_empty']) {
                $add_where = " AND `Count` > 0 ";
            }

            $categories = $GLOBALS['rlDb']->fetch(
                $select,
                $where,
                $add_where . "ORDER BY `Position`",
                null,
                'categories'
            );

            /**
             * Re-assign path of page if selected another language and enabled the option "Multilingual paths"
             * to use correct path for all internal urls
             */
            if ($config['multilingual_paths'] && RL_LANG_CODE !== $config['lang']) {
                foreach ($categories as &$category) {
                    if ($category['Path_' . RL_LANG_CODE]) {
                        $category["Path_{$config['lang']}"] = $category['Path'];
                        $category['Path'] = $category['Path_' . RL_LANG_CODE];
                    }
                }
            }

            // include sub-categories mode
            if ($include_sub_categories > 0) {
                foreach ($categories as &$category) {
                    $sub_where = $where;
                    $sub_where['Parent_ID'] = $category['ID'];
                    $sub_categories = $GLOBALS['rlDb']->fetch(
                        $select,
                        $sub_where,
                        $add_where . "ORDER BY `Position`",
                        null,
                        'categories'
                    );

                    $sub_categories = $GLOBALS['rlLang']->replaceLangKeys(
                        $sub_categories,
                        'categories',
                        array('name')
                    );

                    $category['sub_categories_calc'] = count($sub_categories);
                    $category['sub_categories'] = $include_sub_categories === 1
                    ? (bool) $category['sub_categories_calc']
                    : $sub_categories;
                }
            }
        }

        // add names
        $categories = $GLOBALS['rlLang']->replaceLangKeys(
            $categories,
            'categories',
            array('name')
        );

        // add user categories
        if ($include_user_categories !== false) {
            $include_user_categories = (int) $include_user_categories;

            $where = array(
                'Parent_ID'    => $parent_id,
                'Account_ID'   => $include_user_categories ?: '',
                'Session_hash' => $include_user_categories ? '' : md5(session_id()),
            );

            if ($user_categories =
                $GLOBALS['rlDb']->fetch(
                    array('ID', "Name` AS `name`, 1 AS `tmp`, CONCAT('user-category-', `ID`) AS `Path"),
                    $where,
                    "AND `Status` <> 'trash' ORDER BY `ID`",
                    null, 'tmp_categories')) {
                $categories = array_merge($categories, $user_categories);
            }
        }

        // sort by names
        if ($type_info['Cat_order_type'] == 'alphabetic') {
            $GLOBALS['reefless']->rlArraySort($categories, 'name');
        }

        // Add URLs
        if ($add_urls) {
            foreach ($categories as &$category) {
                $category['url'] = $reefless->getCategoryUrl($category);

                if ($category['sub_categories']) {
                    foreach ($category['sub_categories'] as &$subCategory) {
                        $subCategory['url'] = $reefless->getCategoryUrl($subCategory);
                    }
                }
            }
        }

        return $categories;
    }

    /**
     * Prepare categories data (names and urls) to use in html templates
     *
     * @since 4.9.3 - The second parameter string $listingTypeKey replaced with boolean $hideEmpty
     * @since 4.9.2
     *
     * @param array &$categories - Categories to prepare
     * @param bool  $hideEmpty   - Hide empty categories
     */
    public static function prepareCategories(array &$categories, bool $hideEmpty = false): void
    {
        if (!$GLOBALS['rlListingTypes']->types) {
            return;
        }

        /**
         * @todo Remove this code once the `geo_filter_data['location_url_pages']` is available in `hookPhpUrlBottom` multiField plugin hook
         */
        if ($GLOBALS['plugins']['multiField']) {
            $GLOBALS['reefless']->loadClass('GeoFilter', null, 'multiField');

            if ($GLOBALS['rlGeoFilter']->geo_format && !$GLOBALS['rlGeoFilter']->geo_filter_data['location_url_pages']) {
                $GLOBALS['rlGeoFilter']->init();
            }
        }

        foreach ($categories as $key => &$category) {
            if ($hideEmpty && $category['Count'] <= 0) {
                unset($categories[$key]);
                continue;
            }

            $category['link'] = $GLOBALS['reefless']->getCategoryUrl($category);

            if (!$category['sub_categories']) {
                continue;
            }

            $reset_index = false;

            foreach ($category['sub_categories'] as $sub_key => &$sub_category) {
                if ($hideEmpty && $sub_category['Count'] <= 0) {
                    unset($categories[$key]['sub_categories'][$sub_key], $sub_category);
                    $reset_index = true;
                    continue;
                }

                $sub_category['Type'] = $category['Type'];
                $sub_category['name'] = $GLOBALS['rlLang']->getPhrase($sub_category['pName']);
                $sub_category['link'] = $GLOBALS['reefless']->url('category', $sub_category);
            }

            if ($reset_index) {
                $category['sub_categories'] = array_values($category['sub_categories']);
            }
        }
    }

    /**
     * Build submit listing form by requested category ID
     *
     * @since 4.7.1 - $parent_ids parameter added
     *
     * @param  int|array - requested category ID as integer or category data as array
     *                 (ID and Parent_IDs) indexes are required
     * @param  array     - category related listing type data
     * @param  array     - fields array to append fields data in
     * @return array     - form data
     */
    public static function buildForm($category, &$listing_type, &$fields_list = array())
    {
        global $rlCache, $config, $rlHook, $lang, $rlDb, $languages;

        // ignored field keys
        $ignore_fields = array();
        if (!$config['address_on_map']) {
            array_push($ignore_fields, 'account_address_on_map');
        }

        $fields_list = array();
        $category_id = (int) (isset($category['ID']) ? $category['ID'] : $category);
        $parent_ids  = isset($category['Parent_IDs']) ? $category['Parent_IDs'] : null;

        // get from cache
        if ($config['cache']) {
            $form = $rlCache->get('cache_submit_forms', $category_id, $listing_type, $parent_ids);

            foreach ($form as $group_key => &$group) {
                foreach ($group['Fields'] as $field_key => &$field) {
                    // assign name
                    $field['name'] = $lang['listing_fields+name+' . $field['Key']];

                    // assign fields data to external object variable
                    $fields_list[] = $field;

                    // remove 'address on map' field if related option disabled
                    if ($field['Key'] == 'account_address_on_map' && !$config['address_on_map']) {
                        unset($form[$group_key]['Fields'][$field_key]);
                    }

                    // sort items
                    if ($field['Condition'] != 'years' && is_array($field['Values']) && RL_LANG_CODE != $config['lang'] && $field['Condition']) {
                        $order = $GLOBALS['data_formats'][$field['Condition']]['Order_type']
                        ? $GLOBALS['data_formats'][$field['Condition']]['Order_type']
                        : $rlDb->getOne('Order_type', "`Key` = '{$field['Condition']}'", "data_formats");

                        if ($order == 'alphabetic') {
                            foreach ($field['Values'] as $field_item_key => &$field_item) {
                                $field_item['name'] = $lang['data_formats+name+' . $field_item['Key']];
                            }

                            Util::arraySort($field['Values'], 'name');
                        }
                    }
                }
            }
        }
        // get from db
        else {
            $form = self::getFormRelations($category_id, $listing_type);

            foreach ($form as $key => &$value) {
                if ($value['Fields']) {
                    $sql = "SELECT *, FIND_IN_SET(`ID`, '{$value['Fields']}') AS `Order`, ";
                    $sql .= "CONCAT('listing_fields+name+', `Key`) AS `pName`, CONCAT('listing_fields+description+', `Key`) AS `pDescription`, ";
                    $sql .= "CONCAT('listing_fields+default+', `Key`) AS `pDefault` ";
                    $sql .= "FROM `{db_prefix}listing_fields` ";
                    $sql .= "WHERE FIND_IN_SET(`ID`, '{$value['Fields']}' ) > 0 AND `Status` = 'active' ";
                    $sql .= $ignore_fields
                    ? "AND `Key` NOT IN ('" . implode("','", $ignore_fields) . "') "
                    : '';
                    $sql .= "ORDER BY `Order`";

                    $rlHook->load('buildListingFormSql', $sql, $value);

                    $fields = $rlDb->getAll($sql, 'Key');

                    if (empty($fields)) {
                        unset($form[$key]);
                    } else {
                        $value['Fields'] = $GLOBALS['rlCommon']->fieldValuesAdaptation($fields, 'listing_fields', $listing_type);

                        // assign name
                        foreach ($value['Fields'] as &$field) {
                            $field['name'] = $lang['listing_fields+name+' . $field['Key']];
                        }
                    }

                    // assign fields data to external object variable
                    if (is_array($fields_list) && is_array($value['Fields'])) {
                        $fields_list = array_merge($fields_list, $value['Fields']);
                    }

                    unset($fields);
                } else {
                    $form[$key]['Fields'] = false;
                }
            }
        }

        // Adapt values of fields in form
        foreach ($form as $group_key => &$group) {
            foreach ($group['Fields'] as $field_key => &$field) {
                /**
                 * @since 4.8.2
                 */
                $rlHook->load('phpBuildListingFormField', $form, $group_key, $group, $field_key, $field);

                // Add default values for text fields with Multilingual mode
                if ($field['Type'] == 'text' && $field['Multilingual'] && $field['Default'] && count($languages) > 1) {
                    foreach ($languages as $language) {
                        $field['pMultiDefault'][$language['Code']] = $rlDb->getOne(
                            'Value',
                            "`Key` = '{$field['pDefault']}' AND `Code` = '{$language['Code']}'",
                            'lang_keys'
                        );
                    }
                }
            }
        }

        /**
         * @since 4.8.2
         */
        $rlHook->load('phpBuildListingFormBottom', $form, $category, $listing_type, $fields_list);

        return $form;
    }

    /**
     * Get Form Relations from db by requested category ID
     *
     * @since 4.7.1 - $no_recursive (second param) removed
     *              - $listing_type added
     *
     * @param  int $category_id    - requested category ID
     * @param  array $listing_type - category related listing type data
     * @return array               - form data
     */
    public static function getFormRelations($category_id = 0, $listing_type = [])
    {
        global $rlDb;

        $category_id = (int) $category_id;

        if (!$category_id || !$listing_type) {
            return [];
        }

        if ($listing_type['Cat_general_only'] && $category_id != $listing_type['Cat_general_cat']) {
            return self::getFormRelations($listing_type['Cat_general_cat'], $listing_type);
        }

        $sql = "SELECT `T1`.`Group_ID`, `T1`.`ID`, `T1`.`Category_ID`, `T2`.`Key`, `T1`.`Fields`, `T2`.`Display`, ";
        $sql .= "`T2`.`Columns`, `T2`.`Header`, ";
        $sql .= "CONCAT('listing_groups+name+', `T2`.`Key`) AS `pName`, `T2`.`ID` AS `Group` ";
        $sql .= "FROM `{db_prefix}listing_relations` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_groups` AS `T2` ON `T1`.`Group_ID` = `T2`.`ID` ";
        $sql .= "WHERE `T1`.`Category_ID` = '{$category_id}' AND (`T1`.`Group_ID` = '' OR `T2`.`Status` = 'active') ";
        $sql .= "ORDER BY `T1`.`Position`";

        $form = $rlDb->getAll($sql);

        // prepare form
        if ($form) {
            $count = 1;
            if ($form) {
                foreach ($form as &$item) {
                    $index = $item['Key'] ? $item['Key'] : 'nogroup_' . $count;
                    $tmp_form[$index] = $item;
                    $count++;
                }
                $form = $tmp_form;
                unset($tmp_form);

                return $form;
            }
        }
        // check in parent
        else {
            if ($parent = $rlDb->getOne('Parent_ID', "`ID` = '{$category_id}'", 'categories')) {
                $form = self::getFormRelations($parent, $listing_type);
            }

            if (!$form && $listing_type['Cat_general_cat']) {
                if ($listing_type['Cat_general_cat'] == $category_id) {
                    return [];
                } else {
                    $form = self::getFormRelations($listing_type['Cat_general_cat'], $listing_type);
                }
            }

            return $form;
        }
    }

    /**
     * Reset general category data in listing type and database
     *
     * @since 4.9.0
     *
     * @param integer|string $categoryID  - Category ID
     * @param string         $listingType - Listing type key
     */
    public static function resetGeneralCategory($categoryID = null, $listingType = null)
    {
        if (!$categoryID || !$listingType) {
            return;
        }

        if ($GLOBALS['rlListingTypes']->types[$listingType]['Cat_general_cat'] == $categoryID) {
            $update = [
                'fields' => [
                    'Cat_general_cat' =>  0,
                    'Cat_general_only' =>  '0',
                ],
                'where' => ['Cat_general_cat' => $categoryID]
            ];
            $GLOBALS['rlDb']->updateOne($update, 'listing_types');

            $GLOBALS['rlListingTypes']->types[$listingType]['Cat_general_cat'] = 0;
            $GLOBALS['rlListingTypes']->types[$listingType]['Cat_general_only'] = 0;
        }
    }

    /**
     * Add user category
     *
     * @param integer - parent category id
     * @param string  - user category name
     * @param integer - account id to assign new category to
     * @param array   - errors
     */
    public static function addUserCategory($parent_id = 0, $name = '', $account_id = 0, &$errors = [])
    {
        global $rlDb;

        // validate data
        $parent_id = (int) $parent_id;
        $name = trim($name, ' "\'');
        Valid::escape($name, true);

        if (!$parent_id || !$name) {
            $GLOBALS['rlDebug']->logger(__METHOD__ . '() failed, no "parent_id" or "name" parameter passed');
            return false;
        }

        // TODO brute force protection required

        // check for common category existence
        $sql = "SELECT `T1`.`ID` FROM `{db_prefix}categories` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T2` ON CONCAT('categories+name+', `T1`.`Key`) = `T2`.`key` ";
        $sql .= "WHERE LCASE(`T2`.`Value`) = '" . strtolower($name) . "' AND `Parent_ID` = '{$parent_id}' LIMIT 1";
        $category_exist = $rlDb->getRow($sql);

        // check for user category existence
        $user_category_exist = $rlDb->getOne('ID', "LCASE(`Name`) = '" . strtolower($name) . "'", 'tmp_categories');

        if ($category_exist || $user_category_exist) {
            $errors[] = str_replace('{category}', $name, $GLOBALS['rlLang']->getPhrase('tmp_category_exists', null, false, true));
        }

        if (!$errors) {
            $GLOBALS['reefless']->loadClass('Actions');
            $GLOBALS['reefless']->loadClass('Mail');

            // save category
            $insert = array(
                'Name'         => $name,
                'Parent_ID'    => $parent_id,
                'Account_ID'   => $account_id,
                'Session_hash' => $account_id ? '' : md5(session_id()),
                'Date'         => 'NOW()',
            );

            $GLOBALS['rlActions']->insertOne($insert, 'tmp_categories');
            $user_category_id = $GLOBALS['rlDb']->insertID();

            // inform category owner
            if ($account_id) {
                $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate('custom_category_added_user');
                $mail_tpl['body'] = str_replace('{category_name}', $name, $mail_tpl['body']);
                $account_email = $rlDb->getOne('Mail', "`ID` = {$account_id}", 'accounts');

                $GLOBALS['rlMail']->send($mail_tpl, $account_email);
            }

            // inform administrator
            $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate('custom_category_added_admin');
            $mail_tpl['body'] = str_replace('{category_name}', $name, $mail_tpl['body']);

            $GLOBALS['rlMail']->send($mail_tpl, $GLOBALS['config']['notifications_email']);

            return $user_category_id;
        }

        return false;
    }

    /**
     * Get user category by id or by url path
     *
     * @param  int    $id     - user category ID
     * @param  string $path   - user category path
     * @param  string $prefix - user category path prefix
     * @return array          - user category data
     */
    public static function getUserCategory($id = null, $path = '', $prefix = '')
    {
        if (!($id || ($path && $prefix))) {
            return false;
        }

        $user_category_id = intval($id ?: str_replace($prefix, '', $path));

        $user_category = $GLOBALS['rlDb']->fetch(
            array('Name', 'Parent_ID'),
            array('ID' => $user_category_id),
            null,
            1,
            'tmp_categories',
            'row');

        $category = self::getCategory($user_category['Parent_ID']);
        $category['name'] = $user_category['Name'];
        $category['Level'] = $category['Level'] + 1;
        $category['Path'] = $prefix . $user_category_id;
        $category['Lock'] = 0;
        $category['user_category_id'] = $user_category_id;

        return $category;
    }


    /**
     * Update multilingual paths of child categories
     *
     * @since 4.8.0
     *
     * @param  int   $id
     * @param  array $multiPaths
     * @return bool
     */
    public static function updateChildMultilingualPaths($id, $multiPaths = [])
    {
        global $rlDb, $rlHook;

        $id = (int) $id;

        if (!$id || !is_array($multiPaths) || !$GLOBALS['config']['multilingual_paths']) {
            return false;
        }

        $select = ['ID', 'Path', 'Parent_ID'];
        $where  = "WHERE (FIND_IN_SET({$id}, `Parent_IDs`) > 0 OR `ID` = {$id}) AND `Status` = 'active'";

        foreach ($multiPaths as $langKey => $multiPath) {
            $select[] = "Path_{$langKey}";
        }

        unset($langKey);

        $rlHook->load('phpPreUpdateChildMultiPaths', $id, $multiPaths, $select, $where);

        $subCategories = $rlDb->fetch($select, null, $where, null, 'categories');

        if (!$subCategories) {
            return false;
        }

        $update = [];
        $index  = 0;

        foreach ($subCategories as &$category) {
            foreach ($multiPaths as $langKey => $multiPath) {
                if (!$multiPath || $category['ID'] == $id) {
                    continue;
                }

                $update[$index]  = ['where' => ['ID' => $category['ID']]];
                $parentID        = array_search($category['Parent_ID'], array_column($subCategories, 'ID'));
                $parent          = is_numeric($parentID) ? $subCategories[$parentID] : null;
                $subcategoryPath = $category["Path_{$langKey}"] ?: $category['Path'];

                if ($parent) {
                    if ($category["Path_{$langKey}"]) {
                        $subcategoryPath = $parent["Path_{$langKey}"] . '/' . end(explode('/', $subcategoryPath)) ;
                    } else {
                        $subcategoryPath = str_replace($parent['Path'], $parent["Path_{$langKey}"], $subcategoryPath);
                    }
                } else {
                    $subcategoryPath = $multiPath . '/' . end(explode('/', $subcategoryPath));
                }

                $update[$index]['fields']["Path_{$langKey}"] = $category["Path_{$langKey}"] = $subcategoryPath;
                $index++;
            }
        }

        $rlDb->update($update, 'categories');

        return true;
    }


    /**
     * Get path of category in selected language
     *
     * @since 4.8.0
     *
     * @param $id
     * @param $lang
     * @return bool|string
     */
    public static function getCategoryMultilingualPath($id, $lang)
    {
        $id   = (int) $id;
        $lang = Valid::escape($lang);

        global $config;

        if (!$id || !$lang || !$config['multilingual_paths']) {
            return false;
        }

        if ($config['cache']) {
            $multilingualPath = $GLOBALS['rlCache']->get('cache_categories_multilingual_paths', $id)["Path_{$lang}"];
        } else {
            $multilingualPath = $GLOBALS['rlDb']->getOne("Path_{$lang}", "`ID` = {$id}", 'categories');
        }

        return $multilingualPath;
    }

    /**
     * Get info about current category in system
     *
     * @since 4.8.1
     *
     * @return array
     */
    public static function getCurrentCategory()
    {
        global $page_info;

        if (!$page_info || ($page_info && $page_info['Controller'] !== 'listing_type')) {
            return [];
        }

        static $category = null;

        if (!is_null($category)) {
            return $category;
        }

        if ($GLOBALS['config']['mod_rewrite']) {
            $category = self::getCategory(false, $_GET['rlVareables']);
        } else {
            $category = self::getCategory($_GET['category']);
        }

        return $category;
    }

    /**
     * Delete category with all related phrases
     *
     * @since 4.9.3
     *
     * @param  string $key - Key of category
     * @return bool
     */
    public static function deleteCategoryWithRelatedPhrases(string $key = ''): bool
    {
        if (!$key) {
            return false;
        }

        $phraseKeys = [
            ['Key' => 'categories+name+' . $key],
            ['Key' => 'categories+title+' . $key],
            ['Key' => 'categories+h1+' . $key],
            ['Key' => 'categories+des+' . $key],
            ['Key' => 'categories+meta_description+' . $key],
            ['Key' => 'categories+listing_meta_title+' . $key],
            ['Key' => 'categories+listing_meta_description+' . $key],
        ];

        /**
         * @since 4.9.2 - Added $key as first parameter
         */
        $GLOBALS['rlHook']->load('phpPreDeleteRelatedCategoryPhrases', $key, $phraseKeys);

        $GLOBALS['reefless']->loadClass('Actions');
        $GLOBALS['rlActions']->delete(['Key' => $key], ['categories'], null, 1, $key, $phraseKeys);

        return true;
    }

    /**
     * Get category icon menu items
     *
     * @since 4.9.2
     *
     * @return array - Categories array
     */
    public static function getCategoryIconMenu(): array
    {
        global $pages, $rlListingTypes, $tpl_settings, $rlDb, $rlLang, $config;

        $category_menu = [];

        if ($tpl_settings['category_menu_listing_type']) {
            foreach ($rlListingTypes->types as $type) {
                if (!$type['Menu']) {
                    continue;
                }

                $category_menu[] = array(
                    'ID' => $type['ID'],
                    'Type' => $type['Key'],
                    'Name' => $type['name'],
                    'Menu_icon' => $type['Menu_icon'],
                    'href' => $GLOBALS['reefless']->getPageUrl($type['Page_key']),
                    'isListingType' => true
                );
            }
        }

        $category_icons = $rlDb->fetch(
            '*',
            array(
                'Menu' => 1,
                'Status' => 'active'
            ),
            "ORDER BY `Position`",
            null, 'categories'
        );

        if ($category_icons) {
            $category_ids = [];

            foreach ($category_icons as &$category_icon) {
                $category_icon['href'] = $GLOBALS['reefless']->getCategoryUrl($category_icon);
                $category_ids[] = $category_icon['ID'];
            }

            $category_menu = array_merge($category_menu, $category_icons);

            $GLOBALS['rlSmarty']->assign_by_ref('category_menu_parents', $category_ids);
        }

        foreach ($category_menu as &$item) {
            $href = SEO_BASE;
            $type = $rlListingTypes->types[$item['Type']];

            $item['icon'] = RL_LIBS . 'icons/svg-line-set/' . $item['Menu_icon'];
            if (!$item['Name']) {
                $item['Name'] = $rlLang->getPhrase('categories+name+' . $item['Key'], null, null, true);
            }
        }

        return $category_menu;
    }

    /**
     * Get list of subcategories (with internal subcategories)
     *
     * @since 4.9.3
     *
     * @param  int   $parentID   - ID of parent category
     * @param  array $categories - List of categories which must be filtered by parent category ID
     * @param  int   $maxLevel   - Maximum nesting level of provided categories
     * @param  array $type       - Data of related listing type
     * @return array             - List of subcategories
     */
    public static function getSubCategoriesByParentID(int $parentID, array $categories, int $maxLevel = 0, array $type = []): array
    {
        $result = [];
        if ($parentID && $maxLevel > 0 && (int) $categories[$parentID]['Level'] === $maxLevel) {
            return $result;
        }

        foreach ($categories as $category) {
            if ((int) $category['Parent_ID'] === $parentID) {
                $categoryID    = (int) $category['ID'];
                $categoryLevel = (int) $category['Level'];

                if ($type) {
                    if ($categoryLevel < $maxLevel
                        && $subCategories = self::getSubCategoriesByParentID($categoryID, $categories)
                    ) {
                        $category['sub_categories']      = $subCategories;
                        $category['sub_categories_calc'] = count($subCategories);
                    }

                    $result[$categoryID] = $category;
                } else {
                    $result[] = $category;
                }
            }
        }

        return $result;
    }

    /**
     * Get system list of columns from categories table
     *
     * @since 4.9.3
     *
     * @return array
     */
    public static function getColumns()
    {
        global $rlDb, $rlHook;

        static $columns = null;

        if (!is_null($columns)) {
            return $columns;
        }

        $excludeColumns = ['Status', 'Position', 'Tree'];

        $rlHook->load('phpPreGetCategoryColumns', $excludeColumns);

        $where = '';
        if ($excludeColumns) {
            $where .= "WHERE `Field` NOT IN ('" . implode("', '", $excludeColumns) . "') ";
        }

        $columns = $rlDb->getAll("SHOW COLUMNS FROM `{db_prefix}categories` {$where}", [null, 'Field']);

        $rlHook->load('phpAfterGetCategoryColumns', $columns);

        return $columns;
    }

    /**
     * Replace vars in category metadata string
     *
     * @since 4.10.0
     *
     * @param  string $string    - Metadata string
     * @param  array  $category  - Current category data array
     * @param  string $parentKey - Parent category key
     * @return string            - Metadata with replaced variables
     */
    public static function replaceVars(string $string, array $category, string $parentKey): string
    {
        if (!$string) {
            return '';
        }

        $no_parent_var = "{parent-exclude-{$parentKey}} ";
        $parent_name = $parentKey ? $GLOBALS['rlLang']->getPhrase('categories+name+' . $parentKey) : '';
        $vars = ['{category}', '{parent}'];
        $replace = [$category['name'], $parent_name];

        if (false !== strpos($string, $no_parent_var)) {
            $string = str_replace($no_parent_var, '', $string);
        } else {
            $string = preg_replace('/\{parent\-exclude\-([^\}]+)\}/', $parent_name, $string);
        }

        $GLOBALS['rlHook']->load('phpCategoryReplaceVars', $string, $category, $parentKey);

        return str_replace($vars, $replace, $string);
    }

    /**
     * Set category metadata by parent/general category metadata template
     *
     * @since 4.10.0
     *
     * @param array &$pageInfo - Page information array
     * @param array &$category - Current category data array
     */
    public static function setMetadata(array &$pageInfo, array &$category): void
    {
        global $lang, $_metadataFields;

        $search_parent = (!$category['h1'] || !$category['title'] || !$category['meta_description']);
        $meta_data = '';
        $parents = [];

        if ($search_parent) {
            if ($category['Parent_Keys']) {
                $parents = array_reverse(explode(',', $category['Parent_Keys']));
                foreach ($parents as $key) {
                    foreach ($_metadataFields as $meta_field) {
                        $info_index = str_replace('template_', '', $meta_field);
                        if (!$category[$info_index] && $meta_data = $lang["categories+{$meta_field}+{$key}"]) {
                            $category[$info_index] = self::replaceVars($meta_data, $category, current($parents));
                        }
                    }
                }
            }

            if (!$meta_data && $general_cat_id = $GLOBALS['rlListingTypes']->types[$category['Type']]['Cat_general_cat']) {
                $general_cat_key = $GLOBALS['rlDb']->getOne('Key', "`ID` = {$general_cat_id}", 'categories');
                $parent_key = $parents[0] ?: '';

                foreach ($_metadataFields as $meta_field) {
                    $info_index = str_replace('template_', '', $meta_field);
                    if (!$category[$info_index] && $meta_data = $lang["categories+{$meta_field}+{$general_cat_key}"]) {
                        $category[$info_index] = self::replaceVars($meta_data, $category, $parent_key);
                    }
                }
            }
        }

        $pageInfo['h1'] = $category['h1'] ?: $category['name'];
        $pageInfo['title'] = $category['title'] ?: $category['name'];
        $pageInfo['meta_description'] = $category['meta_description'] ?: '';
    }

    /**
     * Delete all related phrases with category
     *
     * @deprecated 4.9.3 - Use method deleteCategoryWithRelatedPhrases
     * @since 4.9.1
     *
     * @param  string $key - Key of category
     * @return bool
     */
    public static function deleteRelatedPhrases(string $key = ''): bool
    {
        return self::deleteCategoryWithRelatedPhrases($key);
    }
}
