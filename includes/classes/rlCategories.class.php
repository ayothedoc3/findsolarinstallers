<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.10.1
 *  LICENSE: FL1KU6QLIUJA - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: bizlisting.xyz
 *  FILE: RLCATEGORIES.CLASS.PHP
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

use Flynax\Utils\Category;
use Flynax\Utils\Valid;

class rlCategories
{
    /**
     * @var category sections
     **/
    public $sections;

    /**
     * @var selected fields array
     **/
    public $fields;

    /**
     * Get data format
     *
     * @since 4.10.0 - Removed unused $order parameter
     *
     * @param string $key - Format key
     *
     * @return array - Data formats list
     */
    public function getDF($key = '')
    {
        global $rlCache, $config, $rlDb, $reefless, $data_formats;
        static $df = [];

        Valid::escape($key);

        $data = [];

        if (!$key) {
            return $data;
        } elseif (!is_null($df[$key])) {
            return $df[$key];
        }

        if ($config['cache']) {
            $data = $rlCache->get('cache_data_formats', $key);
        } elseif ($format_id = $rlDb->getOne('ID', "`Key` = '{$key}'", 'data_formats')) {
            $data = $rlDb->fetch(
                ['ID', 'Parent_ID', 'Key`, CONCAT("data_formats+name+", `Key`) AS `pName', 'Default', 'Position'],
                ['Status' => 'active', 'Parent_ID' => $format_id],
                'ORDER BY `ID`, `Key`', null, 'data_formats'
            );
        }

        if ($data) {
            $data = $GLOBALS['rlLang']->replaceLangKeys($data, 'data_formats', ['name']);

            if (!empty($data_formats[$key]['Order_type'])) {
                $order = $data_formats[$key]['Order_type'];
            } else {
                $order = $rlDb->getOne('Order_type', "`Key` = '{$key}'", 'data_formats');
            }

            $reefless->rlArraySort($data, $order == 'alphabetic' ? 'name' : 'Position');
        }

        /**
         * @since 4.8.1
         */
        $GLOBALS['rlHook']->load('phpCategoryGetDF', $data, $key, $order);

        $df[$key] = $data;

        return $data;
    }

    /**
     * delete listing field's group
     *
     * @package ajax
     *
     * @param string $key - group key
     *
     **/
    public function ajaxDeleteFGroup($key = false)
    {
        global $_response, $lang;

        if (!$key) {
            return $_response;
        }

        $GLOBALS['rlValid']->sql($key);
        $lang_keys[] = array(
            'Key' => 'listing_groups+name+' . $key,
        );

        // delete group field relations
        if (!$GLOBALS['config']['trash']) {
            $this->deleteGroupRelations($key);
        }

        $GLOBALS['rlActions']->delete(array('Key' => $key), array('listing_groups', 'lang_keys'), null, 1, $key, $lang_keys);
        $del_mode = $GLOBALS['rlActions']->action;

        $_response->script("
            listingGroupsGrid.reload();
            printMessage('notice', '{$lang['group_' . $del_mode]}');
        ");

        return $_response;
    }

    /**
     * delete group relations
     *
     * @param string $key - group key
     *
     **/
    public function deleteGroupRelations($key = false)
    {
        global $rlDb;

        if (!$key) {
            return false;
        }

        $GLOBALS['rlValid']->sql($key);

        // get category id
        $group_id = $rlDb->getOne('ID', "`Key` = '{$key}'", 'listing_groups');

        if ($group_id) {
            // delete field relations from main form
            $sql = "DELETE FROM `{db_prefix}listing_relations` WHERE `Group_ID` = '{$group_id}'";
            $rlDb->query($sql);

            // delete field relations from search form
            $sql = "DELETE FROM `{db_prefix}search_forms_relations` WHERE `Group_ID` = '{$group_id}'";
            $rlDb->query($sql);
        }
    }

    /**
     * get all categories
     *
     * @param int $id - category ID
     * @param mixed $type - listing type key
     * @param bool $include_sections - include sections
     *
     * @return array - listing types list
     **/
    public function getCategories($parent = 0, $type = false, $include_sections = false, $include_subcats = false)
    {
        global $select, $where, $rlListingTypes, $config, $rlCache, $reefless, $rlDb;

        $parent = (int) $parent;
        $types = $type ? array($rlListingTypes->types[$type]) : $rlListingTypes->types;

        /* get categories from cache */
        if ($config['cache'] && ((defined('REALM') && REALM != 'admin') || !defined('REALM'))) {
            foreach ($types as $type) {
                $categories = $rlCache->get('cache_categories_by_parent', $parent, $type);

                if ($config['multilingual_paths'] && $config['lang'] !== RL_LANG_CODE) {
                    $multilingualPaths = $rlCache->get('cache_categories_multilingual_paths');

                    foreach ($categories as &$category) {
                        $path             = $multilingualPaths[$category['ID']]['Path_' . RL_LANG_CODE];
                        $category['Path'] = $path ?: $category['Path'];

                        foreach ($category['sub_categories'] as &$subcategory) {
                            $path                = $multilingualPaths[$subcategory['ID']]['Path_' . RL_LANG_CODE];
                            $subcategory['Path'] = $path ?: $subcategory['Path'];
                        }
                    }

                    unset($category, $subcategory, $path);
                }

                $categories = $GLOBALS['rlLang']->replaceLangKeys($categories, 'categories', array('name'));

                $GLOBALS['rlHook']->load('phpCategoriesGetCategoriesCache', $categories);

                if ($type['Cat_order_type'] == 'alphabetic') {
                    $reefless->rlArraySort($categories, 'name');
                }

                if ($include_subcats) {
                    foreach ($categories as $key => &$value) {
                        if ($value['sub_categories']) {
                            $value['sub_categories'] = $GLOBALS['rlLang']->replaceLangKeys($value['sub_categories'], 'categories', array('name'));

                            if ($type['Cat_order_type'] == 'alphabetic') {
                                $reefless->rlArraySort($value['sub_categories'], 'name');
                                $categories[$key]['sub_categories'] = $value['sub_categories'];
                            }
                        }
                    }
                }

                if ($include_sections) {
                    $sections[$type['Key']] = array(
                        'ID'         => $type['ID'],
                        'name'       => $type['name'],
                        'Key'        => $type['Key'],
                        'Categories' => $categories,
                    );
                }
            }

            if ($include_sections) {
                $categories = $sections;
            }

            return $categories;
        }

        $sections = array();
        foreach ($types as $type) {
            $where = array(
                'Status'    => 'active',
                'Parent_ID' => $parent,
            );
            if ($type) {
                $where['Type'] = $type['Key'];
            }
            if ($type['Cat_hide_empty'] && ((defined('REALM') && REALM != 'admin') || !defined('REALM'))) {
                $addwhere = "AND `Count` > 0";
            }

            $select = array('ID', 'Path', 'Count', "Key`, CONCAT('categories+name+', `Key`) AS `pName`, CONCAT('categories+title+', `Key`) AS `pTitle", 'Type');

            if ($config['multilingual_paths'] && RL_LANG_CODE !== $config['lang']) {
                foreach ($GLOBALS['languages'] as $languageKey => $languageData) {
                    if ($languageKey === $config['lang']) {
                        continue;
                    }

                    $select[] = 'Path_' . $languageKey;
                }
            }

            /* load hook for front-end only */
            if (!defined('REALM')) {
                $GLOBALS['rlHook']->load('getCategoriesModifySelect');
            }

            $categories = $rlDb->fetch($select, $where, "{$addwhere} ORDER BY `Position`", null, 'categories');
            $categories = $GLOBALS['rlLang']->replaceLangKeys($categories, 'categories', array('name'));

            if ($config['multilingual_paths'] && $config['lang'] !== RL_LANG_CODE) {
                foreach ($categories as &$category) {
                    if ($category['Path_' . RL_LANG_CODE]) {
                        $category["Path_{$config['lang']}"] = $category['Path'];
                        $category['Path'] = $category['Path_' . RL_LANG_CODE];
                    }
                }
            }

            $GLOBALS['rlHook']->load('phpCategoriesGetCategories', $categories);

            if ($type['Cat_order_type'] == 'alphabetic') {
                $reefless->rlArraySort($categories, 'name');
            }

            /* get subcategories */
            if ($include_subcats) {
                foreach ($categories as $key => $value) {
                    if ($type['Cat_hide_empty'] && ((defined('REALM') && REALM != 'admin') || !defined('REALM'))) {
                        $addwhere = "AND `Count` > 0";
                    }
                    $rlDb->calcRows = true;
                    $subCategories = $rlDb->fetch(
                        $select,
                        ['Status' => 'active', 'Parent_ID' => $categories[$key]['ID']],
                        $addwhere . " ORDER BY `Position`",
                        null,
                        'categories'
                    );
                    $rlDb->calcRows = false;
                    $subCategories = $GLOBALS['rlLang']->replaceLangKeys($subCategories, 'categories', array('name'));

                    /**
                     * Re-assign path of page if selected another language and enabled the option "Multilingual paths"
                     * to use correct path for all internal urls
                     */
                    if ($config['multilingual_paths'] && RL_LANG_CODE !== $config['lang']) {
                        foreach ($subCategories as &$subCategory) {
                            if ($subCategory['Path_' . RL_LANG_CODE]) {
                                $subCategory["Path_{$config['lang']}"] = $subCategory['Path'];
                                $subCategory['Path'] = $subCategory['Path_' . RL_LANG_CODE];
                            }
                        }
                    }

                    if ($type['Cat_order_type'] == 'alphabetic') {
                        $reefless->rlArraySort($subCategories, 'name');
                    }

                    if (!empty($subCategories)) {
                        $categories[$key]['sub_categories'] = $subCategories;
                        $categories[$key]['sub_categories_calc'] = $rlDb->calcRows;
                    }

                    unset($subCategories);
                }
            }

            if ($include_sections) {
                if (!empty($categories)) {
                    $sections[$type['Key']] = array(
                        'ID'         => $type['ID'],
                        'name'       => $type['name'],
                        'Key'        => $type['Key'],
                        'Categories' => $categories,
                    );
                }
            }
        }

        if ($include_sections) {
            $categories = $sections;
        }

        /* "with sections" mode */
        if ($sections && $parent == 0) {
            if (!$this->sections) {
                return $categories;
            }

            $cat_sections = $this->sections;

            foreach ($categories as $cVal) {
                $cat_sections[$cVal['Type']]['Categories'][] = $cVal;
            }
            unset($categories);

            $categories = $cat_sections;
        }

        return $categories;
    }

    /**
     * Get categories tree
     *
     * @since 4.8.2 - Added $excludeAdminOnly parameter
     *
     * @param int          $parent_id         - Category parent_id
     * @param string|array $type              - Listing type
     * @param bool         $group_by_sections - Group categories by sections mode
     * @param bool         $active_types      - Filter categories by active listing types only
     * @param bool         $excludeAdminOnly  - Exclude listing types from result which opened for admins only
     *
     * @return array - Array with categories
     */
    public function getCatTree(
        $parent_id         = 0,
        $type              = false,
        $group_by_sections = false,
        $active_types      = false,
        $excludeAdminOnly  = false
    ) {
        global $sql, $rlListingTypes, $account_info, $rlDb, $reefless;

        $parent_id = (int) $parent_id;
        $GLOBALS['rlValid']->sql($type);
        $sql = "SELECT `T1`.`ID`, `T1`.`Path`, `T1`.`Level`, `T1`.`Type`, `T1`.`Key`, `T1`.`Lock`, `T1`.`Add`, `T1`.`Count`, ";

        $GLOBALS['rlHook']->load('getCatTreeFields', $sql); // param1 added from > 4.3.0

        $sql .= "IF(`T2`.`ID` AND `T2`.`Status` = 'active', `T2`.`ID`, IF( `T3`.`ID`, 1, 0 )) `Sub_cat`";
        $sql .= " FROM `{db_prefix}categories` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`ID` = `T2`.`Parent_ID` AND `T2`.`Status` = 'active' ";
        $sql .= "LEFT JOIN `{db_prefix}tmp_categories` AS `T3` ON `T1`.`ID` = `T3`.`Parent_ID` AND `T3`.`Account_ID` = '{$account_info['ID']}' AND `T3`.`Status` <> 'trash' ";
        $sql .= "WHERE `T1`.`Status` = 'active' ";

        if ($type && $parent_id == 0) {
            $type = is_array($type) ? $type : explode(',', $type);

            if ($excludeAdminOnly) {
                foreach ($type as $tk => $tp) {
                    if ($rlListingTypes->types[$tp]['Admin_only']) {
                        unset($type[$tk]);
                    }
                }
            }

            if ($type) {
                $sql .= "AND (`T1`.`Type` = '" . implode("' OR `T1`.`Type` = '", $type) . "') ";
            }
        }

        $sql .= "AND `T1`.`Parent_ID` = '{$parent_id}' ";
        $sql .= "GROUP BY `T1`.`Key` ";
        $sql .= "ORDER BY `T1`.`Position` ";

        $categories = $rlDb->getAll($sql);
        $categories = $GLOBALS['rlLang']->replaceLangKeys($categories, 'categories', array('name'));

        /* group by sections mode */
        if ($group_by_sections && $parent_id == 0) {
            $categories_grouped = $rlListingTypes->types;

            // remove inactive listing types
            if ($active_types) {
                foreach ($categories_grouped as $type_key => $l_type) {
                    if ($l_type['Status'] != 'active') {
                        unset($categories_grouped[$type_key]);
                    }
                }
            }

            if ($type) {
                foreach ($categories_grouped as $key => $value) {
                    if (!in_array($value['Key'], $type)) {
                        unset($categories_grouped[$key]);
                    }
                }
            }

            foreach ($categories as $key => $value) {
                if ($categories_grouped[$value['Type']]) {
                    $categories_grouped[$value['Type']]['Categories'][] = $value;
                }
            }

            /* ordering */
            foreach ($categories_grouped as $key => $value) {
                if ($value['Cat_order_type'] == 'alphabetic') {
                    $reefless->rlArraySort($value['Categories'], 'name');
                    $categories_grouped[$key]['Categories'] = $value['Categories'];
                }
            }

            $categories = $categories_grouped;
            unset($categories_grouped);
        } else {
            if ((!$type || $type == "false") && $parent_id) {
                $type = $rlDb->getOne("Type", "`ID` = {$parent_id}", "categories");
            } elseif (is_array($type) && $type[0]) {
                $type = $type[0];
            }
            if ($type) {
                if ($rlListingTypes->types[$type]['Cat_order_type'] == 'alphabetic') {
                    $reefless->rlArraySort($categories, 'name');
                }
            }
        }

        return $categories;
    }

    /**
     * get category level
     *
     * @deprecated 4.6.0 - Use Flynax\Utils\Category::getCategories
     *
     * @package xajax
     *
     * @param int $category_id - category ID
     * @param string $type - listing type
     * @param string $tpl - tpl postfix name, example: category_level_{$tpl}.tpl
     * @param string $function - js callback method
     * @param string $namespace - container namespace
     * @param string $section_key - section/selector class name
     *
     * @return array - sub categories array
     **/
    public function ajaxGetCatLevel($category_id, $type = false, $tpl = false, $function = false, $namespace = false, $section_key = false, $mode = false)
    {
        global $_response, $rlSmarty, $rlListingTypes, $account_info, $reefless, $rlDb;

        $reefless->loadClass('Categories');

        // Get category information
        $category_id = (int) $category_id;
        $category = $this->getCategory($category_id);
        $rlSmarty->assign_by_ref('category', $category);

        /* get child categories */
        $categories = $this->getCatTree($category_id, $type);

        // assign namespace mode
        $rlSmarty->assign_by_ref('mode', $mode);

        /* custom category for current user detecting */
        if ($rlListingTypes->types[$category['Type']]['Cat_custom_adding']) {
            $custom_cat_in = $rlDb->fetch(array('ID', 'Name'), array('Account_ID' => $account_info['ID'], 'Parent_ID' => $category_id), "AND `Status` <> 'trash' ORDER BY `Date`", null, 'tmp_categories');
            if (!empty($custom_cat_in)) {
                foreach ($custom_cat_in as $key => $value) {
                    $categories[] = array(
                        'ID'   => $custom_cat_in[$key]['ID'],
                        'name' => $custom_cat_in[$key]['Name'],
                        'Tmp'  => true,
                    );
                }
            }
        }

        if ($categories || ($rlListingTypes->types[$category['Type']]['Cat_custom_adding'] && $category['Add'])) {
            $rlSmarty->assign_by_ref('categories', $categories);

            $_response->script("$('#tree_area_{$category['Parent_ID']}').parent().nextAll().remove();");

            $tpl_postfix = $tpl ? '_' . $tpl : '';
            $file = 'blocks' . RL_DS . 'category_level' . $tpl_postfix . '.tpl';

            if (in_array($tpl, array('crossed', 'checkbox'))) {
                $target = 'tree_cat_' . $category_id;
                $_response->script("xajaxFix = $('#tree_cat_{$category_id}').find('input').attr('checked');");
            } else {
                $target = 'type_section_' . $section_key;
                if ($namespace) {
                    $target .= '_' . $namespace;
                }
            }

            $_response->append($target, 'innerHTML', $rlSmarty->fetch($file, null, null, false));

            if (in_array($tpl, array('crossed', 'checkbox'))) {
                $_response->script("
                    $('#tree_cat_{$category_id} > ul').fadeIn('normal');
                    $('#tree_cat_{$category_id} > img').addClass('opened');
                    $('#tree_cat_{$category_id} > span.tree_loader').fadeOut();

                    if (xajaxFix == 'checked') {
                        $('#tree_cat_{$category_id} input:first').prop('checked', true);
                    }
                ");
            }

            $_response->script("flynax.treeLoadLevel('{$tpl}', '{$function}', '{$section_key}', '{$namespace}', '{$mode}');");

            if ($function) {
                $_response->call($function);
            }
        }

        return $_response;
    }

    /**
     * detect parent item with enabled including mode | recursive method
     *
     * @param int $id - category id
     *
     * @return bool
     **/
    public function detectParentIncludes($id)
    {
        global $rlDb;

        $id = (int) $id;
        if ($id == 0) {
            return false;
        }

        /* get parent */
        $parent = $rlDb->fetch(array('Parent_ID', 'Add_sub', 'Add'), array('ID' => $id), null, 1, 'categories', 'row');

        if (!empty($parent)) {
            /* check relations */
            if ($parent['Add_sub'] == '1') {
                return $parent['Add'];
            }
            return $this->detectParentIncludes($parent['Parent_ID']);
        } else {
            return false;
        }
    }

    /**
     * get parent points
     *
     * @param array $ids - categories ids
     * @param string $assign - assign vaiable name in SMARTY
     *
     * @assign array - parent points IDs
     **/
    public function parentPoints($ids = false, $assign = 'parentPoints')
    {
        global $rlListingTypes, $rlSmarty, $config, $rlDb;

        $GLOBALS['rlValid']->sql($ids);
        if (!is_array($ids)) {
            $ids = array($ids);
        }

        if (empty($ids) || empty($ids[0])) {
            return false;
        }

        $sql = "SELECT `ID`, `Parent_ID`, `Type` FROM `{db_prefix}categories` ";
        $sql .= "WHERE (`ID` = " . implode(" OR `ID` = ", $ids) . ")";
        $parents = $rlDb->getAll($sql);

        $checked             = array();
        $config['tmp_cache'] = $config['cache'];
        $config['cache']     = 0;

        $out = [];
        foreach ($parents as $cat) {
            if (!$cat['Parent_ID'] || in_array($cat['Parent_ID'], $checked)) {
                continue;
            }

            $bc        = $this->getBreadCrumbs($cat['Parent_ID'], false, $rlListingTypes->types[$cat['Type']]);
            $checked[] = $cat['Parent_ID'];

            $bc = array_reverse($bc);
            foreach ($bc as $bc_item) {
                if (!is_numeric(array_search($bc_item['ID'], $out))) {
                    $out[] = $bc_item['ID'];
                }
            }
        }

        $config['cache'] = $config['tmp_cache'];
        unset($config['tmp_cache']);

        $rlSmarty->assign_by_ref($assign, $out);

        return $out;
    }

    /**
     * Get category bread crumbs | recursive method
     *
     * @since 4.9.2 - Removed "bool" type from return values, now it will return "array" only
     *
     * @param  int   $parentID
     * @param  array $path      - Path array
     * @param  array $type      - Listing type info
     * @return array
     */
    public function getBreadCrumbs($parentID = 0, $path = [], $type = []): array
    {
        global $rlCache, $config;

        $parentID = (int) $parentID;

        if (!$parentID) {
            return [];
        }

        if ($config['cache']) {
            $categoryInfo = $rlCache->get('cache_categories_by_type', $parentID, $type);
        } else {
            $select = ['ID', 'Key', 'Parent_ID', 'Path', 'Position', 'Type'];

            if ($config['multilingual_paths']) {
                foreach ($GLOBALS['languages'] as $languageKey => $languageData) {
                    if ($languageKey === $config['lang']) {
                        continue;
                    }

                    $select[] = 'Path_' . $languageKey;
                }
            }

            $categoryInfo = $GLOBALS['rlDb']->fetch(
                $select,
                ['ID' => $parentID, 'Type' => $type['Key']],
                null, null, 'categories', 'row'
            );
        }

        if (!empty($categoryInfo)) {
            $categoryInfo = $GLOBALS['rlLang']->replaceLangKeys($categoryInfo, 'categories', ['name']);

            if ($config['multilingual_paths'] && $config['lang'] !== RL_LANG_CODE) {
                $categoryInfo['Path'] = $categoryInfo['Path_' . RL_LANG_CODE] ?: $categoryInfo['Path'];
            }

            $path[] = $categoryInfo;
        } else {
            $path = [];
        }

        if (!empty($categoryInfo['Parent_ID'])) {
            return $this->getBreadCrumbs($categoryInfo['Parent_ID'], $path, $type);
        }

        return $path;
    }

    /**
     * @since 4.5.1
     *
     * build category bread crumbs
     *
     * @param array $bread_crumbs - system bread crumbs array
     * @param array $category - category info array
     * @param array $listing_type - listing type info array
     *
     * @return array - path array
     **/
    public function buildCategoryBreadCrumbs(&$bread_crumbs, &$category_id, &$listing_type)
    {
        global $page_info, $lang, $config, $rlListingTypes;

        $cat_bread_crumbs = $this->getBreadCrumbs($category_id, null, $listing_type);
        $cat_bread_crumbs = $cat_bread_crumbs ? array_reverse($cat_bread_crumbs) : [];

        if (!empty($cat_bread_crumbs)) {
            foreach ($cat_bread_crumbs as $bKey => $bVal) {
                if ($config['mod_rewrite']) {
                    if ($rlListingTypes->types[$bVal['Type']]['Links_type'] === 'short') {
                        $path = $bVal['Path'];
                    } else {
                        $path = $page_info['Path'] . '/' . $bVal['Path'];
                    }
                } else {
                    $path = $page_info['Path'] . '&category=' . $bVal['ID'];
                }

                $cat_bread_crumbs[$bKey]['path'] = $path;
                $cat_bread_crumbs[$bKey]['title'] = $lang[$bVal['pTitle']] ?: $lang[$bVal['pName']];
                $cat_bread_crumbs[$bKey]['category'] = true;
                $bread_crumbs[] = $cat_bread_crumbs[$bKey];
            }
        }

        unset($cat_bread_crumbs);
    }

    /**
     * Category walker check/update/delete categories or listings inside subcategories | recursive method
     *
     * @since 4.9.3 - Parameter $category_id renamed to $category
     *
     * @param int|array $category         - Start category (array or ID)
     * @param string    $mode             - Action mode: detect|delete|trash|restore|replace
     * @param array     $data             - Recursive variable
     * @param int       $new_id           - New category ID | in replace mode only
     * @param int       $initial_category - Initial category ID
     *
     * @return array - mixed data
     */
    public function categoryWalker($category = false, $mode = false, $data = array(), $new_id = false, $initial_category = false)
    {
        global $rlDb, $reefless, $rlListingTypes;

        if (!$mode) {
            trigger_error('categoryWalker() error, no mode selected', E_USER_WARNING);
            $GLOBALS['rlDebug']->logger("categoryWalker() error, no mode selected");

            return false;
        }

        $category_id = is_array($category) && $category['ID'] ? (int) $category['ID'] : (int) $category;
        if (!$category_id) {
            return false;
        }

        // Detects last level of subcategories to prevent unnecessary requests in DB
        if (is_array($category) && $category['Type'] && !isset($rlListingTypes->types[$category['Type']]['Max_level'])) {
            $rlListingTypes->types[$category['Type']]['Max_level'] = (int) $rlDb->getRow(
                "SELECT MAX(`Level`) AS `Max_level` FROM `{db_prefix}categories` WHERE `Type` = '{$category['Type']}'"
            )['Max_level'];
        }

        $rlDb->setTable('categories');

        switch ($mode) {
            case 'detect':
                /* get child categories */
                $child = $rlDb->fetch(array('ID', 'Parent_ID'), array('Parent_ID' => $category_id), "AND `Status` <> 'trash'");
                $listings = $rlDb->getRow("SELECT COUNT(`ID`) AS `count` FROM `{db_prefix}listings` WHERE (`Category_ID` = '{$category_id}' OR FIND_IN_SET('{$category_id}', `Crossed`) > 0) AND `Status` <> 'trash'");

                if ($listings['count']) {
                    $data['listings'] += $listings['count'];
                }

                if (!empty($child)) {
                    foreach ($child as $key => $value) {
                        $data['categories']++;

                        $data = $this->categoryWalker($child[$key]['ID'], 'detect', $data);
                    }
                }

                return $data;
                break;

            case 'delete':
                if ((is_array($category)
                        && isset($category['Level'])
                        && $category['Type']
                        && isset($rlListingTypes->types[$category['Type']]['Max_level'])
                        && ((int) $category['Level'] <= $rlListingTypes->types[$category['Type']]['Max_level'])
                    ) || !is_array($category)
                ) {
                    $subCategories = $rlDb->fetch(['ID', 'Key', 'Count', 'Type', 'Level'], ['Parent_ID' => $category_id]);
                }

                if ((is_array($category) && $category['Count']) || !is_array($category)) {
                    $listings = $rlDb->fetch(array('ID'), array('Category_ID' => $category_id), null, null, 'listings');
                }

                // Delete listings
                if (!empty($listings)) {
                    $reefless->loadClass('Listings');
                    foreach ($listings as $listing) {
                        $GLOBALS['rlListings']->deleteListingData($listing['ID']);
                    }

                    $rlDb->query("DELETE FROM `{db_prefix}listings` WHERE `Category_ID` = {$category_id}");
                }

                // Delete subcategories
                if (!empty($subCategories)) {
                    foreach ($subCategories as $subCategory) {
                        Category::deleteCategoryWithRelatedPhrases($subCategory['Key']);
                        $this->categoryWalker($subCategory, 'delete');
                        $this->deleteCatRelations($subCategory['ID']);
                        Category::resetGeneralCategory($subCategory['ID'], $subCategory['Type']);
                    }
                }
                break;

            case 'trash':
                // Get child categories
                if ((is_array($category)
                        && isset($category['Level'])
                        && $category['Type']
                        && isset($rlListingTypes->types[$category['Type']]['Max_level'])
                        && ((int) $category['Level'] <= $rlListingTypes->types[$category['Type']]['Max_level'])
                    ) || !is_array($category)
                ) {
                    $subCategories = $rlDb->fetch(['ID', 'Key', 'Count', 'Type', 'Level'], ['Parent_ID' => $category_id]);
                }

                if ((is_array($category) && $category['Count']) || !is_array($category)) {
                    $listings = $rlDb->fetch(
                        ['ID`, UNIX_TIMESTAMP(`Pay_date`) AS `Pay_date', 'Category_ID', 'Account_ID'],
                        ['Category_ID' => $category_id],
                        null, null, 'listings'
                    );
                }

                // Trash all listings
                if (!empty($listings)) {
                    foreach ($listings as $listing) {
                        if ($listing['Pay_date']) {
                            $this->listingsDecrease($listing['Category_ID'], null, false);
                            $this->accountListingsDecrease($listing['Account_ID']);
                        }
                    }

                    $rlDb->query("UPDATE `{db_prefix}listings` SET `Status` = 'trash' WHERE `Category_ID` = '{$category_id}'");
                }

                if (!empty($subCategories)) {
                    foreach ($subCategories as $subCategory) {
                        $rlDb->query("UPDATE `{db_prefix}categories` SET `Status` = 'trash' WHERE `ID` = '{$subCategory['ID']}' LIMIT 1");
                        $this->categoryWalker($subCategory, 'trash');
                    }
                }
                break;

            case 'restore':
                // Get child categories
                if ((is_array($category)
                        && isset($category['Level'])
                        && $category['Type']
                        && isset($rlListingTypes->types[$category['Type']]['Max_level'])
                        && ((int) $category['Level'] <= $rlListingTypes->types[$category['Type']]['Max_level'])
                    ) || !is_array($category)
                ) {
                    $subCategories = $rlDb->fetch(['ID', 'Key', 'Count', 'Type', 'Level'], ['Parent_ID' => $category_id]);
                }

                $listings = $rlDb->fetch(
                    ['ID`, UNIX_TIMESTAMP(`Pay_date`) AS `Pay_date', 'Category_ID', 'Account_ID'],
                    ['Category_ID' => $category_id],
                    null, null, 'listings'
                );

                // Restore all listings
                if (!empty($listings)) {
                    foreach ($listings as $listing) {
                        if ($listing['Pay_date']) {
                            $this->listingsIncrease($listing['Category_ID'], null, false);
                            $this->accountListingsIncrease($listing['Account_ID']);
                        }
                    }
                    $rlDb->query("UPDATE `{db_prefix}listings` SET `Status` = 'active' WHERE `Category_ID` = '{$category_id}'");
                }

                if (!empty($subCategories)) {
                    foreach ($subCategories as $subCategory) {
                        $rlDb->query("UPDATE `{db_prefix}categories` SET `Status` = 'active' WHERE `ID` = '{$subCategory['ID']}' LIMIT 1");
                        $this->categoryWalker($subCategory, 'restore');
                    }
                }
                break;

            case 'replace':
                $new_id = (int) $new_id;
                if ($new_id) {
                    $find_id = (int) ($initial_category ?: $category_id);
                    $initial_category_data = $rlDb->fetch(array('Path', 'Tree'), array('ID' => $find_id), null, 1, 'categories', 'row');
                    $replace_category_data = $rlDb->fetch(array('Path', 'Tree'), array('ID' => $new_id), null, 1, 'categories', 'row');

                    /* update sub-categories */
                    $rlDb->query("UPDATE `{db_prefix}categories` SET `Parent_ID` = '{$new_id}', `Path` = REPLACE(`Path`, '{$initial_category_data['Path']}', '{$replace_category_data['Path']}'), `Tree` = REPLACE(`Tree`, '{$initial_category_data['Tree']}', '{$replace_category_data['Tree']}') WHERE `Parent_ID` = '{$category_id}'");

                    /* update listings */
                    $rlDb->query("UPDATE `{db_prefix}listings` SET `Category_ID` = '{$new_id}' WHERE `Category_ID` = '{$category_id}'");
                }

                if ($child = $rlDb->fetch(array('ID', 'Parent_ID', 'Key'), array('Parent_ID' => $category_id))) {
                    foreach ($child as $child_category) {
                        $this->categoryWalker($child_category['ID'], 'replace', false, $new_id, $category_id);
                    }
                }

                break;
        }
    }

    /**
     * Get category related paths
     *
     * @param  int    $parentID - Category parent_id
     * @param  string $path
     * @param  string $langKey  - Provide language key if you want get a multilingual path
     * @return string
     */
    public function getCatPath($parentID = 0, $path = '', $langKey = '')
    {
        global $rlDb;

        if (!$parentID = (int) $parentID) {
            return $path;
        }

        if ($langKey && $GLOBALS['config']['multilingual_paths']) {
            $sql = "SELECT IF(`T1`.`Path_{$langKey}` <> '', `T1`.`Path_{$langKey}`, `T1`.`Path`) AS `Path` ";
            $sql .= 'FROM `{db_prefix}categories` AS `T1` ';
            $sql .= "WHERE `T1`.`Status` = 'active' AND `T1`.`ID` = {$parentID}";
            $categoryPath = $rlDb->getRow($sql, 'Path');
        } else {
            $categoryPath = $rlDb->getOne('Path', "`ID` = {$parentID}", 'categories');
        }

        $path = $categoryPath ? ($path ? $categoryPath . '/' . $path : $categoryPath) : $path;

        return $path;
    }

    /**
     * Get category data by ID/Path
     *
     * @deprecated 4.8.1 - Use \Flynax\Utils\Category::getCategory()
     *
     * @since 4.8.0 - Added $getLangKeys parameter
     *
     * @param  int    $id
     * @param  string $path
     * @param  bool   $getLangKeys - Get system phrases of category data
     *                             - Set FALSE to increase loading and decrease memory usage, if phrases aren't needed
     * @return array
     */
    public function getCategory($id = 0, $path = '', $getlangKeys = true)
    {
        return Category::getCategory($id, $path, $getlangKeys);
    }

    /**
     * get parent category form relations
     *
     * @param int $id - category id
     *
     * @return array - fields form
     **/
    public function getParentCatRelations($id = false, $noRecursive = false)
    {
        global $rlDb;

        $id = (int) $id;

        $sql = "SELECT `T1`.`Group_ID`, `T1`.`ID`, `T1`.`Category_ID`, `T2`.`Key`, `T1`.`Fields`, `T2`.`Display`, ";
        $sql .= "`T2`.`Columns`, `T2`.`Header`, CONCAT('listing_groups+name+', `T2`.`Key`) AS `pName`, `T2`.`ID` AS `Group` ";
        $sql .= "FROM `{db_prefix}listing_relations` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_groups` AS `T2` ON `T1`.`Group_ID` = `T2`.`ID` ";
        $sql .= "WHERE `T1`.`Category_ID` = '{$id}' AND (`T1`.`Group_ID` = '' OR `T2`.`Status` = 'active') ";
        $sql .= "ORDER BY `T1`.`Position`";
        $form = $rlDb->getAll($sql);

        $count = 1;
        if ($noRecursive || !empty($form)) {
            foreach ($form as $item) {
                $index = $item['Key'] ?: 'nogroup_' . $count;
                $tmp_form[$index] = $item;
                $count++;
            }
            $form = $tmp_form;
            unset($tmp_form);

            return $form;
        }

        if (empty($form)) {
            /* get parent */
            if ($parent = $rlDb->getOne('Parent_ID', "`ID` = '{$id}'", 'categories')) {
                /* check relations */
                return $this->getParentCatRelations($parent);
            }
        }
    }

    /**
     * build the listing form by listing category id
     *
     * @deprecated 4.7.1 - Use Utils\Category::buildForm()
     *
     * @param int $id - category id
     * @param array $listing_type - listing type details
     *
     * @return array - listing form
     **/
    public function buildListingForm($id = false, $listing_type = false)
    {
        global $rlDb;

        $category = array(
            'ID'         => $id,
            'Parent_IDs' => $rlDb->getOne('Parent_IDs', "`ID` = {$id}", 'categories')
        );

        return Category::buildForm($category, $listing_type, $this->fields);
    }

    /**
     * preparation category deleting
     *
     * @package ajax
     *
     * @param int $id - category id
     *
     **/
    public function ajaxPrepareDeleting($id = false)
    {
        global $_response, $rlSmarty, $lang, $config, $reefless;

        // check admin session expire
        if ($reefless->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        $id = (int) $id;
        $delete_info = $this->categoryWalker($id, 'detect');

        $category = $this->getCategory($id);
        $rlSmarty->assign_by_ref('category', $category);

        if ($delete_info) {
            /* get the first level categories */
            $sections = $this->getCatTree(0, false, true);
            $rlSmarty->assign_by_ref('sections', $sections);

            $rlSmarty->assign_by_ref('delete_info', $delete_info);
            $tpl = 'blocks' . RL_DS . 'delete_preparing_category.tpl';
            $_response->assign("delete_container", 'innerHTML', $GLOBALS['rlSmarty']->fetch($tpl, null, null, false));
            $_response->script("
                flynax.treeLoadLevel('', '', 'div#replace_content');
                flynax.slideTo('#bc_container');
                $('#delete_block').slideDown();

                category_key = '{$category['Key']}';
                category_name = '{$category['name']}';
            ");
        } else {
            $phrase = $config['trash'] ? $lang['trash_confirm'] : $lang['drop_confirm'];
            $_response->script("
                $('#delete_block').slideUp();
                rlConfirm('{$phrase}', 'xajax_deleteCategory', '{$category['Key']}');
            ");
        }

        return $_response;
    }

    /**
     * delete category
     *
     * @package ajax
     *
     * @param string $key - category key
     * @param int $replace - replace category id
     * @param bool $direct - direct method call
     *
     **/
    public function ajaxDeleteCategory($key = false, $replace = false, $direct = false)
    {
        global $_response, $rlCache, $config, $lang, $controller, $rlDb, $reefless;

        // check admin session expire
        if ($reefless->checkSessionExpire() === false && !$direct) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        $GLOBALS['rlValid']->sql($key);
        $category_info = $rlDb->fetch(['ID', 'Count', 'Type', 'Level'], ['Key' => $key], null, 1, 'categories', 'row');
        $id = (int) $category_info['ID'];

        if (!$id || !$key) {
            if (!$direct) {
                $_response->script("printMessage('error', 'Error detected, no category key or ID specified.');");
                return $_response;
            } else {
                return false;
            }
        }

        if ($replace && (int) $replace == (int) $id) {
            if (!$direct) {
                $message = str_replace('{category}', $lang['categories+name+' . $key], $lang['replace_category_duplicate']);
                $_response->script("printMessage('error', '{$message}');");
                return $_response;
            }
            exit;
        }

        if ($replace) {
            $replace = (int) $replace;
            $rlDb->query("UPDATE `{db_prefix}categories` SET `Count` = `Count` + {$category_info['Count']} WHERE `ID` = '{$replace}' LIMIT 1");
        }

        if ($replace) {
            $this->categoryWalker($id, 'replace', '', $replace);
        } else {
            $GLOBALS['listingFileFields'] = $rlDb->getAll(
                "SELECT `Key` FROM `{db_prefix}listing_fields` WHERE `Type` = 'image' OR `Type` = 'file'",
                [false, 'Key']
            );

            $this->categoryWalker($category_info, $config['trash'] ? 'trash' : 'delete');

            unset($GLOBALS['listingFileFields']);
        }

        if (!$config['trash']) {
            $this->deleteCatRelations($id);
        }

        Category::resetGeneralCategory($id, $category_info['Type']);
        Category::deleteCategoryWithRelatedPhrases($key);

        $del_mode = $GLOBALS['rlActions']->action;

        if (!$direct) {
            $rlCache->updateCategories();
            $rlCache->updateStatistics();
            $rlCache->updateForms();
        }

        $GLOBALS['rlHook']->load('apPhpAjaxDeleteCategory', $category_info, $replace, $direct); // > 4.3.0

        // Update single category flag
        if (!$direct) {
            $GLOBALS['rlListingTypes']->updateSingleID($category_info['Type']);
        }

        // return if direct mode
        if ($direct) {
            return true;
        }

        if ($controller == 'browse') {
            $_response->redirect(RL_URL_HOME . ADMIN . "/index.php?controller=browse" . ($replace ? "&id={$replace}" : ''));
        } else {
            $_response->script("
                categoriesGrid.reload();
                $('#replace_content').slideUp();
                $('#delete_block').fadeOut();
            ");
        }

        $_response->script("printMessage('notice', '{$lang['category_' . $del_mode]}')");

        return $_response;
    }

    /**
     * delete category field relations
     *
     * @param int $id - category ID
     *
     **/
    public function deleteCatRelations($id = false)
    {
        global $rlDb;

        $id = (int) $id;
        if (!$id) {
            return false;
        }

        $tables = array('short_forms', 'listing_titles', 'featured_form', 'listing_relations');
        foreach ($tables as $table) {
            $sql = "DELETE FROM `{db_prefix}{$table}` WHERE `Category_ID` = '{$id}'";
            $rlDb->query($sql);
        }

        // Delete from crossed
        $sql = "UPDATE `{db_prefix}listings` ";
        $sql .= "SET `Crossed` = TRIM(BOTH ',' FROM REPLACE(CONCAT(',',`Crossed`,','), ',{$id},', ',')) ";
        $sql .= "WHERE FIND_IN_SET({$id}, `Crossed`) > 0";
        $rlDb->query($sql);
    }

    /**
     * Increase category listings
     *
     * @since 4.9.3 - Added $updateCache parameter
     *
     * @param  int    $id          - ID of the Category
     * @param  string $type        - Key of the listing type
     * @param  bool   $updateCache - Update the cache of categories and listing type
     * @return bool
     */
    public function listingsIncrease($id, $type = '', bool $updateCache = true)
    {
        return $this->listingsCount($id, '+', $type, 1, $updateCache);
    }

    /**
     * Decrease category listings
     *
     * @since 4.9.3 - Added $updateCache parameter
     *
     * @param  int    $id          - ID of the Category
     * @param  string $type        - Key of the listing type
     * @param  bool   $updateCache - Update the cache of categories and listing type
     * @return bool
     */
    public function listingsDecrease($id, $type = '', bool $updateCache = true)
    {
        return $this->listingsCount($id, '-', $type, 1, $updateCache);
    }

    /**
     * Category listings count
     *
     * @since 4.9.3
     *
     * @param  int    $id          - ID of the Category
     * @param  string $sign        - '-' or '+' - decrease or increase
     * @param  string $type        - Key of the listing type
     * @param  int    $number      - Number of listings
     * @param  bool   $updateCache - Update the cache of categories and listing type
     * @return bool
     */
    public function listingsCount($id, $sign = '+', $type = '', $number = 1, bool $updateCache = true)
    {
        global $rlCache, $rlHook, $rlDb;

        $id     = (int) $id;
        $number = $number ?: 1;
        $sign   = $sign ?: '+';

        if (empty($id)) {
            return false;
        }

        $rlDb->query("UPDATE `{db_prefix}categories` SET `Count` = `Count` {$sign} {$number}, `Modified` = NOW() WHERE `ID` = {$id}");

        // Get parent of the category
        $parent = $rlDb->getOne('Parent_ID', "`ID` = {$id}", 'categories');

        if ($parent > 0) {
            $this->listingsCount($parent, $sign, $type, $number, $updateCache);
        } else {
            if (is_object($rlHook)) {
                /**
                 * @since 4.9.3 - Added $updateCache parameter
                 * @since 4.1.0
                 */
                if ($sign === '+') {
                    $rlHook->load('categoriesListingsIncrease', $id, $type, $updateCache);
                } elseif ($sign === '-') {
                    $rlHook->load('categoriesListingsDecrease', $id, $type, $updateCache);
                }
            }

            if ($updateCache) {
                $rlCache->updateCategories();
            }

            $type = $type ?: $rlDb->getOne('Type', "`ID` = {$id}", 'categories');
            $rlDb->query("UPDATE `{db_prefix}listing_types` SET `Count` = `Count` {$sign} {$number} WHERE `Key` = '{$type}'");

            if ($updateCache) {
                $rlCache->updateStatistics($type);
            }
        }

        return true;
    }

    /**
     * lock/unlock category
     *
     * @package xajax
     *
     * @param int $id - category id
     * @param string $mode - mode: lock | unclock
     **/
    public function ajaxLockCategory($id, $mode = false)
    {
        global $_response, $lang, $reefless, $rlDb;

        // check admin session expire
        if ($reefless->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        if (!$mode || !in_array($mode, array('lock', 'unlock'))) {
            return $_response;
        }

        $status = $mode == 'lock' ? 1 : 0;
        $id = (int) $id;

        /* update lock status */
        $update = array(
            'fields' => array(
                'Lock' => $status,
            ),
            'where'  => array(
                'ID' => $id,
            ),
        );
        $rlDb->updateOne($update, 'categories');

        $lang_key = $mode == 'lock' ? 'message_category_locked' : 'message_category_unlocked';
        $new_phrase = $mode == 'lock' ? 'unlock_category' : 'lock_category';
        $new_action = $mode == 'lock' ? 'unlock' : 'lock';

        $_response->script("
            $('#locked_button_phrase').html('{$GLOBALS['lang'][$new_phrase]}').attr('class', 'center_{$new_action}');
            $('#locked_button').attr('onClick', \"xajax_lockCategory('{$id}', '{$new_action}')\");
            printMessage('notice', '{$lang[$lang_key]}');
        ");

        return $_response;
    }

    /**
     * copy fields relations
     *
     * @param int $target_cat - target category id
     *
     * @param int $source_cat - source category id
     *
     * @param string $mode - edit or add
     **/
    public function copyFieldsRelations($target_cat, $source_cat, $mode = 'add')
    {
        global $reefless, $rlDb;

        $reefless->loadClass('Actions');

        $source_cat = (int) $source_cat;
        $target_cat = (int) $target_cat;

        // copy main form fields
        $main_relations = $rlDb->fetch('*', array('Category_ID' => $source_cat), null, null, 'listing_relations');
        if (!empty($main_relations)) {
            foreach ($main_relations as $key => $value) {
                unset($main_relations[$key]['ID']);
                $main_relations[$key]['Category_ID'] = $target_cat;
            }
            if ($mode == 'edit') {
                //delete existing relations
                $rlDb->query("DELETE FROM `{db_prefix}listing_relations` WHERE `Category_ID` = {$target_cat}");
            }
            $rlDb->insert($main_relations, 'listing_relations');
        }

        // copy listing title fields
        $title_relations = $rlDb->fetch('*', array('Category_ID' => $source_cat), null, null, 'listing_titles');
        if (!empty($title_relations)) {
            foreach ($title_relations as $key => $value) {
                unset($title_relations[$key]['ID']);
                $title_relations[$key]['Category_ID'] = $target_cat;
            }

            if ($mode == 'edit') {
                //delete existing relations
                $rlDb->query("DELETE FROM `{db_prefix}listing_titles` WHERE `Category_ID` = {$target_cat}");
            }
            $rlDb->insert($title_relations, 'listing_titles');
        }

        // copy short form fields
        $short_relations = $rlDb->fetch('*', array('Category_ID' => $source_cat), null, null, 'short_forms');
        if (!empty($short_relations)) {
            foreach ($short_relations as $key => $value) {
                unset($short_relations[$key]['ID']);
                $short_relations[$key]['Category_ID'] = $target_cat;
            }

            if ($mode == 'edit') {
                //delete existing relations
                $rlDb->query("DELETE FROM `{db_prefix}short_forms` WHERE `Category_ID` = {$target_cat}");
            }
            $rlDb->insert($short_relations, 'short_forms');
        }

        // copy featured form fields
        $featured_relations = $rlDb->fetch('*', array('Category_ID' => $source_cat), null, null, 'featured_form');
        if (!empty($featured_relations)) {
            foreach ($featured_relations as $key => $value) {
                unset($featured_relations[$key]['ID']);
                $featured_relations[$key]['Category_ID'] = $target_cat;
            }

            if ($mode == 'edit') {
                //delete existing relations
                $rlDb->query("DELETE FROM `{db_prefix}featured_form` WHERE `Category_ID` = {$target_cat}");
            }
            $rlDb->insert($featured_relations, 'featured_form');
        }
    }

    /**
     * load listing type categories to form on Add Category page
     *
     * @package xajax
     *
     * @param string $type - listing type
     *
     * @todo - add new category
     **/
    public function ajaxLoadType($type = false)
    {
        global $_response, $rlSmarty, $rlListingTypes, $pages, $languages, $config;

        $GLOBALS['rlValid']->sql($type);

        /* fetch type details */
        $rlSmarty->assign_by_ref('type', $type);

        /* fetch categories */
        $categories = $this->getCatTree(0, $type);
        $rlSmarty->assign_by_ref('categories', $categories);

        $tpl = 'blocks' . RL_DS . 'categories' . RL_DS . 'parent_cats_tree.tpl';
        $_response->assign("parent_categories", 'innerHTML', $GLOBALS['rlSmarty']->fetch($tpl, null, null, false));

        $postfix = $config['html_in_categories'] ? '.html' : '/';
        $links_type = $rlListingTypes->types[$type]['Links_type'];

        $_response->script("
            $('span#listing_type_loading').fadeOut();
            flynax.treeLoadLevel();
            $('#cat_postfix_el').html('{$postfix}');
        ");

        if ($categories) {
            $_response->script("$('div#parent_category').slideDown();");
        } else {
            $_response->script("$('div#parent_category').slideUp();");
        }

        $pagePath = $pages["lt_{$type}"];

        if ($config['multilingual_paths']) {
            foreach ($languages as $languageKey => $languageData) {
                if ($languageKey !== $config['lang']) {
                    $multilingualPath = $GLOBALS['rlDb']->getOne(
                        "Path_{$languageKey}",
                        "`Key` = 'lt_{$type}'",
                        'pages'
                    ) ?: $pagePath;
                } else {
                    $multilingualPath = $pagePath;
                }

                $urlHome = RL_URL_HOME;
                $urlHome .= $languageData['Code'] !== $config['lang'] ? $languageData['Code'] . '/' : '';

                if ($links_type == 'full') {
                    $_response->script("$('.ab.{$languageKey}').html('{$multilingualPath}/');");
                    $_response->script("$('.abase.{$languageKey}').html('{$urlHome}');");
                    $_response->script("$('.ap.{$languageKey}').html('');");
                } elseif ($links_type == 'subdomain') {
                    $abase = preg_replace('#http(s)?://(www.)?#', "http$1://{$multilingualPath}.", $urlHome);
                    $_response->script("$('.abase.{$languageKey}').html('{$abase}');");
                    $_response->script("$('.ab.{$languageKey}').html('');");
                } else {
                    $_response->script("$('.abase.{$languageKey}').html('{$urlHome}');");
                    $_response->script("$('.ab.{$languageKey}').html('');");
                }
            }
        } else {
            if ($links_type == 'full') {
                $_response->script("$('.ab').html('{$pagePath}/');");
                $_response->script("$('.abase').html('" . RL_URL_HOME . "');");
                $_response->script("$('.ap').html('');");
            } elseif ($links_type == 'subdomain') {
                $abase = preg_replace('#http(s)?://(www.)?#', "http$1://{$pagePath}.", RL_URL_HOME);
                $_response->script("$('.abase').html('{$abase}');");
            } else {
                $_response->script("$('.abase').html('" . RL_URL_HOME . "');");
            }
        }

        return $_response;
    }

    /**
     * get parent categories IDs | RECURSIVE method
     *
     * @param int $id - category ID
     * @param array $ids - found IDs
     *
     * @return array - parent categories IDs
     **/
    public function getParentIDs($id = false, $ids = false)
    {
        global $rlDb;

        $id = (int) $id;
        if (!$id) {
            return false;
        }

        $parent_id = $rlDb->getOne('Parent_ID', "`ID` = '{$id}'", 'categories');
        if ($parent_id) {
            $ids[] = $parent_id;
            return $this->getParentIDs($parent_id, $ids);
        } else {
            return $ids;
        }
    }

    /**
     * get categories Paths
     *
     * @param string $type - listing type
     *
     * @return array containing categories paths
     **/
    public function getCatPaths($type = false)
    {
        global $rlDb;

        $sql = "SELECT `ID`, `Path`, `Key`, CONCAT('categories+name+', `Key`) as `pName` FROM `{db_prefix}categories`";
        if ($type) {
            $sql .= "WHERE `Type` = '{$type}'";
        }

        return $rlDb->getAll($sql, "ID");
    }

    /**
     * build conversion rate
     *
     **/
    public function buildConversionRates()
    {
        global $rlDb;

        $sql = "SELECT * FROM `{db_prefix}data_formats` WHERE `Conversion` = '1' AND `Parent_ID` = 0";
        $conversion_dfs = $rlDb->getAll($sql);

        foreach ($conversion_dfs as $df_item) {
            $sql = "SELECT `Rate`, `Key` FROM `{db_prefix}data_formats` WHERE `Parent_ID` = " . $df_item['ID'];
            $GLOBALS['conversion_rates'][$df_item['Key']] = $rlDb->getAll($sql, array("Key", "Rate"));
        }
    }

    /**
     * @since 4.5.0
     *
     * increase account listings count
     *
     * @param int $id - account id
     * @param int $number - number of listings to be increased
     **/
    public function accountListingsIncrease($id = false, $number = false)
    {
        $this->accountListingsCount($id, '+', $number);
    }

    /**
     * @since 4.5.0
     *
     * decrease account listings count
     *
     * @param int $id - account id
     * @param int $number - number of listings to be increased
     **/
    public function accountListingsDecrease($id = false, $number = false)
    {
        $this->accountListingsCount($id, '-', $number);
    }

    /**
     * Account listings count
     *
     * @since 4.5.0
     *
     * @param int    $id     - Account id
     * @param string $sign   - '-' or '+' - decrease or increase
     * @param int    $number - Number of listings
     */
    public function accountListingsCount($id, $sign = '+', $number = 0)
    {
        global $rlDb;

        if (!$id) {
            return false;
        }

        $number = $number ?: 1;
        $sign = $sign ?: "+";

        $sql = "UPDATE `{db_prefix}accounts` SET `Listings_count` = `Listings_count` {$sign} {$number} WHERE `ID` = {$id}";
        $rlDb->query($sql);
    }

    /**
     * @since 4.5.0
     *
     * mass actions with listings
     *
     * @package xAjax
     *
     * @param string $ids     - listings ids
     * @param string $action  - mass action
     **/
    public function ajaxCategoryMassActions($ids = false, $action = false)
    {
        global $_response, $lang, $config, $rlCache, $rlDb, $rlListingTypes, $rlListings;

        if (!$ids || !$action) {
            return $_response;
        }

        $GLOBALS['rlHook']->load('apPhpCategoriesMassActions', $ids, $action); //> 4.5.0

        $ids = explode('|', $ids);
        $GLOBALS['reefless']->loadClass('Listings');

        /**
         * Prevent cache update of categories during the categories updating process
         * @since 4.9.3
         */
        $updateCategoriesCache = $config['cache'];
        if ($updateCategoriesCache) {
            $config['cache'] = false;
        }

        foreach ($ids as $id) {
            if (!($categoryInfo = Category::getCategory($id))) {
                continue;
            }

            if (in_array($action, ['activate', 'approve'])) {
                $status = $action === 'activate' ? 'active' : 'approval';

                if ($status === $categoryInfo['Status']) {
                    continue;
                }

                $sql = "UPDATE `{db_prefix}categories` SET `Status` = '{$status}' ";
                $sql .= "WHERE `ID` = '{$id}' ";
                $sql .= " OR FIND_IN_SET('{$id}', `Parent_IDs`) ";
                $rlDb->query($sql);

                if ($categoryInfo['Count']) {
                    $rlListings->listingStatusControl(['Category_ID' => $id], $status);
                }
            } elseif ($action === 'delete') {
                $this->categoryWalker($categoryInfo, $config['trash'] ? 'trash' : 'delete');
                if (!$config['trash']) {
                    $this->deleteCatRelations($id);
                }
                Category::resetGeneralCategory($id, $categoryInfo['Type']);
                Category::deleteCategoryWithRelatedPhrases($categoryInfo['Key']);
            }
        }

        if ($updateCategoriesCache) {
            $config['cache'] = '1';

            $rlListingTypes->updateCountListings();
            $rlCache->updateCategories();
            $rlCache->updateStatistics();

            if ($action === 'delete') {
                $rlCache->updateForms();
            }
        }

        $_response->script("printMessage('notice', '{$lang['mass_action_completed']}')");
        $_response->script("categoriesGrid.store.reload();");

        return $_response;
    }

    /**
     * Update count of listings in categories
     *
     * @since 4.7.0
     *
     * @return void
     */
    public function recountCategories()
    {
        global $rlDb;

        // Reset counter before recount
        $rlDb->query("UPDATE `{db_prefix}categories` SET `Count` = 0 WHERE 1");

        $counts = $rlDb->getAll(
            "SELECT COUNT(*) AS `Count`, `T1`.`Category_ID`, `T2`.`Parent_IDs`
             FROM `{db_prefix}listings` AS `T1`
             RIGHT JOIN `{db_prefix}categories` AS `T2` ON `T2`.`ID` = `T1`.`Category_ID`
             WHERE `T1`.`Status` = 'active'
             GROUP BY `T1`.`Category_ID`"
        );

        $categories = [];
        foreach ($counts as $category) {
            $categories[$category['Category_ID']] = $category['Count'];

            if ($category['Parent_IDs']) {
                foreach (explode(',', $category['Parent_IDs']) as $id) {
                    $categories[$id] += $category['Count'];
                }
            }
        }

        if ($rlDb->getOne('ID', "`Status` = 'active' AND `Crossed` <> ''", 'listings')) {
            $crossedCounts = $rlDb->getAll(
                "SELECT `Crossed` FROM `{db_prefix}listings`
                 WHERE `Status` = 'active' AND `Crossed` <> ''"
            );

            foreach ($crossedCounts as $crossedCategories) {
                foreach (explode(',', $crossedCategories['Crossed']) as $categoryID) {
                    if (isset($categories[$categoryID])) {
                        $categories[$categoryID]++;
                    } else {
                        $categories[$categoryID] = 1;
                    }
                }
            }
        }

        foreach ($categories as $categoryID => $count) {
            $rlDb->updateOne([
                'fields' => ['Count' => $count],
                'where'  => ['ID' => $categoryID]
            ], 'categories');
        }
    }
}
