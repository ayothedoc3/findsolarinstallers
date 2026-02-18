<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.10.1
 *  LICENSE: FL1KU6QLIUJA - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: bizlisting.xyz
 *  FILE: RLLISTINGSBOX.CLASS.PHP
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

class rlListingsBox
{
    /**
     * Rejected box positions
     *
     * @var array
     */
    public $rejectedBoxSides = array('header_banner', 'long_top', 'integrated_banner');

    /**
     * IDs of listings which already selected in current script session
     *
     * @since X.X.X
     * @var array
     */
    public $IDs = [];

    /**
     * Plugin installer
     **/
    public function install()
    {
        // create listing box table
        $raw_sql = "`ID` int(11) NOT NULL AUTO_INCREMENT,
            `Type` varchar(255) NOT NULL,
            `Use_category` enum('1','0') NOT NULL DEFAULT '0',
            `Category_IDs` varchar(255) NOT NULL,
            `Use_subcats` enum('1','0') NOT NULL DEFAULT '0',
            `Load_more` enum('1','0') NOT NULL DEFAULT '0',
            `Filters` text NOT NULL,
            `Box_type` enum('top_rating','popular','recently_added','random','featured') NOT NULL DEFAULT 'recently_added',
            `Count` varchar(10) NOT NULL,
            `Unique` enum('1','0') NOT NULL DEFAULT '0',
            `By_category` enum('1','0') NOT NULL DEFAULT '0',
            `Display_mode` enum('default','grid') NOT NULL DEFAULT 'default',
            PRIMARY KEY (`ID`)";

        $GLOBALS['rlDb']->createTable("listing_box", $raw_sql, RL_DBPREFIX, "ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci");

    }

    /**
     * Plugin un-installer
     **/
    public function uninstall()
    {
        // DROP TABLE
        $GLOBALS['rlDb']->dropTable('listing_box');
    }

    /**
     * Remove listing box
     *
     * @hook apAjaxRequest
     */
    public function hookApAjaxRequest(&$out, &$item)
    {
        global $rlDb, $rlLang, $rlSmarty, $reefless;

        $smarty_request_keys = ['listingBoxCatTree', 'listingBoxAddField'];

        if (!is_object($rlSmarty) && in_array($item, $smarty_request_keys)) {
            require_once RL_LIBS . 'smarty' . RL_DS . 'Smarty.class.php';
            $reefless->loadClass('Smarty');
        }

        switch ($item) {
            case 'deleteListingsBox':
                $id = (int) $_REQUEST['id'];
                $key = 'listing_box_' . $id;

                $rlDb->query("DELETE FROM `{db_prefix}listing_box` WHERE `ID` = {$id} LIMIT 1");
                $rlDb->query("DELETE FROM `{db_prefix}blocks` WHERE `Key` = '{$key}' LIMIT 1");

                if (method_exists($rlLang, 'deletePhrase')) {
                    $rlLang->deletePhrase(['Key' => "blocks+name+{$key}"]);
                } else {
                    $rlDb->query("DELETE FROM `{db_prefix}lang_keys` WHERE `Key` = 'blocks+name+{$key}'");
                }

                $this->removeIndexes();

                $out = [
                    'status' => 'OK',
                    'message' => $GLOBALS['lang']['block_deleted']
                ];
                break;

            case 'listingBoxCatTree':
                $out = $this->getCatTree();
                break;

            case 'listingBoxAddField':
                if ($key = Valid::escape($_REQUEST['key'])) {
                    $out = [
                        'status' => 'OK',
                        'results' => $this->buildFieldView($key)
                    ];
                } else {
                    $out = ['status' => 'ERROR'];
                }
                break;
        }
    }

    /**
     * Build filter field view
     *
     * @since 3.2.0
     *
     * @param  string $key - Field key
     * @return string      - Field view HTML
     */
    public function buildFieldView(string $key): string
    {
        global $rlSmarty, $rlDb, $rlLang;

        $rlSmarty->preAjaxSupport();

        $this->loadNumberConditions();

        $field_data = $rlDb->fetch('*', ['Key' => $key], null, 1, 'listing_fields', true);
        $field_data['name'] = $rlLang->getPhrase('listing_fields+name+' . $key);
        $field_data['type_name'] = $rlLang->getPhrase('type_' . $field_data['Type']);

        // Add field values
        $fields = $GLOBALS['rlCommon']->fieldValuesAdaptation([$field_data], 'listing_fields');
        $field_data = $fields[0];

        $rlSmarty->assign('field_data', $field_data);

        $tpl = RL_PLUGINS . 'listings_box/admin/field_filter.tpl';
        $content = $rlSmarty->fetch($tpl, null, null, false);
        return $content;
    }

    /**
     * Load number conditions data to SMARTY
     *
     * @since 3.2.0
     */
    public function loadNumberConditions(): void
    {
        global $lang, $rlSmarty;

        $number_cond = [
            'equals' => $lang['lb_equals'],
            'less' => $lang['lb_less'],
            'more' => $lang['lb_more'],
        ];
        $rlSmarty->assign_by_ref('number_cond', $number_cond);
    }
    
    /**
     * Get category tree
     *
     * @since 3.1.0
     *
     * @return array - categories
     **/
    public function getCatTree()
    {   
        global $rlCategories, $rlSmarty;
        $category_id = $_REQUEST['id'];
        $cats = $rlCategories->getCatTree($category_id);

        if ($cats) {
            $rlSmarty->assign('mode', $_REQUEST['input_mode']);
            $rlSmarty->assign('categories', $cats);
            $tpl = RL_PLUGINS . 'listings_box/admin/category_tree.tpl';
            $content = $rlSmarty->fetch($tpl, null, null, false);
            $out['data'] = $content;
        }
        $out['status'] = 'ok';
        return $out;
    }

    /**
     * Set content box
     *
     * @param array $info  - array info
     * @param array $field - fields for update in grid
     *
     * @return array - box information
     **/
    public function checkContentBlock($info = false, $field = false)
    {
        if (is_array($field)) {
            $data = $GLOBALS['rlDb']->fetch('*', ['ID' => $field[2]], null, 1, 'listing_box', 'row');

            if ($field[0] == 'Type') {
                $type = $field[1];
                $box_type = $data['Box_type'];
                $limit = $data['Count'];
            } elseif ($field[0] == 'Box_type') {
                $type = $data['Type'];
                $box_type = $field[1];
                $limit = $data['Count'];
            } elseif ($field[0] == 'Count') {
                $type = $data['Type'];
                $box_type = $data['Box_type'];
                $limit = $field[1];
            }
            $unique = $data['Unique'];
            $by_category = $data['By_category'];
            $categories_ids = $data['Use_category'] && $data['Category_IDs']  ? $data['Category_IDs'] : '';
            $use_subcats = $data['Use_subcats'];

            $box_option = [
                'display_mode' => $data['Display_mode'],
                'limit' => $limit,
                'load_more' => $data['Load_more'],
            ];
            $filters = $data['Filters'];
        } else {
            $type = $info['type'];
            $box_type = $info['box_type'];
            $limit = $info['count'];
            $unique = $info['unique'];
            $by_category = $info['by_category'];
            $box_option = [
                'display_mode' => $info['display_mode'],
                'limit' => $limit,
                'load_more' => $info['load_more'],
            ];

            $categories_ids = $info['use_category'] && $info['category_ids']  ? $info['category_ids'] : '';
            $use_subcats = $info['use_subcats'];
            $filters = $info['filters'];
        }

        $content = '
                global $rlSmarty;
                $GLOBALS["reefless"]->loadClass("ListingsBox", null, "listings_box");
                $listings_box = $GLOBALS["rlListingsBox"]->getListings( "' . $type . '", "' . $box_type . '", "' . $limit . '", "' . $unique . '", "' . $by_category . '", "' . $categories_ids . '", "' . $use_subcats . '", \'' . $filters . '\' );
                $rlSmarty->assign_by_ref("listings_box", $listings_box);
                $rlSmarty->assign("type", "' . $type . '");
                $rlSmarty->assign("lb_selected_ids", implode(",", $GLOBALS["rlListingsBox"]->IDs));';
        foreach ($box_option as $key => $val) {
            $content .= '$box_option["' . $key . '"] = "' . $val . '";';
        }
        $content .= '$rlSmarty->assign("box_option", $box_option);
                $rlSmarty->display(RL_PLUGINS . "listings_box" . RL_DS . "listings_box.block.tpl");
            ';
        return $content;
    }

    /**
     * Prepare system box options
     *
     * @since 3.2.0
     *
     * @param array $boxData  - The Box fields array to apply the system option to
     * @param array $postData - POST data array
     */
    public function prepareBoxOptions(array &$boxData, array &$postData): void
    {
        global $config, $rlDb;

        if (version_compare($config['rl_version'], '4.10.0', '<=')) {
            return;
        }

        $boxData['Options'] = '';

        $view_all_link = (bool) $postData['view_all_link'];
        $page_key = count($postData['type']) > 1 ? 'listings' : 'lt_' . current($postData['type']);

        if ($view_all_link && $rlDb->getOne('ID', "`Key` = '{$page_key}' AND `Status` = 'active'", 'pages')) {
            $box_options = [
                'header_link' => [
                    'type' => 'boolean',
                    'default' => true
                ],
                'header_link_page_key' => [
                    'type' => 'select',
                    'default' => $page_key,
                    'values' => []
                ]
            ];

            $boxData['Options'] = json_encode($box_options);
        }
    }

    /**
     * Get listings
     *
     * @since 3.2.0 - $filters parameter added
     *
     * @param string $type        - type
     * @param string $order       - field name for order
     * @param int    $limit       - listing number per request
     * @param int    $unique      - Unique listings in box
     * @param int    $by_category - Filter by category on page
     * @param int    $cat_ids     - Selected category in admin panel for one type
     * @param int    $useSubCats  - Use sub categories
     * @param string $filters     - Filters data in json format
     *
     * @return array - listings information
     **/
    public function getListings($type, $order, $limit = 0, $unique = 0, $by_category = 0, $cat_ids = false, $useSubCats = false, $filters = '')
    {
        global $sql, $config, $category, $rlDb, $rlHook, $rlListings, $plugins;

        $selectedIDs = [];
        if ($order === 'top_rating' && $plugins['rating']) {
            $preSQL = "
                SELECT `T1`.`ID`, (`T1`.`lr_rating` / `T1`.`lr_rating_votes`) AS `Middle_rating`
                FROM `{db_prefix}listings` AS `T1`
                LEFT JOIN `{db_prefix}categories` AS `T3` ON `T1`.`Category_ID` = `T3`.`ID`
                WHERE `T1`.`Status` = 'active'
            ";

            if ($type) {
                $GLOBALS['rlValid']->sql($type);

                if (false !== strpos($type, ',')) {
                    $preSQL .= "AND `T3`.`Type` IN('" . str_replace(",", "','", $type) . "') ";
                } else {
                    $preSQL .= "AND `T3`.`Type` = '{$type}' ";
                }
            }

            if ($unique && $rlListings->selectedIDs) {
                $preSQL .= "AND `T1`.`ID` NOT IN('" . implode("','", $rlListings->selectedIDs) . "') ";
            }

            $preSQL .= 'ORDER BY `Middle_rating` DESC ';
            $preSQL .= 'LIMIT ' . (int) $limit;
            $selectedIDs = $rlDb->getAll($preSQL, [null, 'ID']);
        }

        $sql = '';
        if ($order === 'random') {
            $sql .= "SELECT * FROM (";
        }

        $sql .= "SELECT ";

        $dbcount = false;
        /**
         * @since 3.0.3
         */
        $rlHook->load('listingsModifyPreSelect', $dbcount);

        $hook = '';
        $sql .= " {hook} ";
        $sql .= "`T1`.*, `T3`.`Path` AS `Path`, `T3`.`Key` AS `Key`, `T3`.`Type` AS `Listing_type`, `T3`.`Parent_keys`, ";

        // add multilingual
        if ($config['multilingual_paths']) {
            foreach ($GLOBALS['languages'] as $languageKey => $languageData) {
                if ($languageKey === $config['lang']) {
                    continue;
                }

                $sql .= "`T3`.`Path_{$languageKey}`, ";
            }
        }

        // Add option for rating plugin
        if ($order === 'top_rating' && $plugins['rating']) {
            $sql .= '(`T1`.`lr_rating` / `T1`.`lr_rating_votes`) AS `Middle_rating`, ';
        }

        // Add option by category
        if ($category['ID'] && $by_category) {
            $sql .= "IF(`T1`.`Category_ID` = {$category['ID']} OR FIND_IN_SET('{$category['ID']}', `T3`.`Parent_IDs`) , 1, 0) ";
            $sql .= "AS `Category_match`, ";
        }

        $rlHook->load('listingsModifyField');

        $sql = rtrim($sql, ', ');

        $sql .= " FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T3` ON `T1`.`Category_ID` = `T3`.`ID` ";

        $rlHook->load('listingsModifyJoin');

        $sql .= "WHERE `T1`.`Status` = 'active' ";

        if ($order === 'featured') {
            $sql .= "AND `T1`.`Featured_date` <> '0000-00-00 00:00:00' ";
        }

        // select by type or types
        if ($type) {
            $GLOBALS['rlValid']->sql($type);

            if (false !== strpos($type, ',')) {
                $sql .= "AND `T3`.`Type` IN('" . str_replace(",", "','", $type) . "') ";
            } else {
                $sql .= "AND `T3`.`Type` = '{$type}' ";
            }
        }

        // Choose by categories
        if (count(explode(',', $type)) == 1 && $cat_ids) {
            $catIDs = explode(',', $cat_ids);
            $catSql = '';
            foreach($catIDs as $catID) {
                $catSql .= $catSql ? "OR " : "";
                $catSql .= "(`T1`.`Category_ID` = '{$catID}' OR FIND_IN_SET('{$catID}', `T1`.`Crossed`) ";
                if ($useSubCats) {
                    $catSql .= "OR FIND_IN_SET('{$catID}', `T3`.`Parent_IDs`) ";
                }
                $catSql .= ") ";
            }
           $sql .= "AND (".$catSql.") ";
        }

        if ($selectedIDs) {
            $sql .= "AND `T1`.`ID` IN('" . implode("','", $selectedIDs) . "') ";
        } elseif ($unique && $rlListings->selectedIDs) {
            $sql .= "AND `T1`.`ID` NOT IN('" . implode("','", $rlListings->selectedIDs) . "') ";
        }

        // Apply filters
        if ($filters && $filter_fields = json_decode($filters, true)) {
            foreach ($filter_fields as $field_key => &$field_value) {
                // Checkbox
                if (is_array($field_value) && isset($field_value[0])) {
                    unset($field_value[0]); // Remove dummy item
                    $sql .= "AND (";
                    foreach ($field_value as $value) {
                        $value = str_replace($field_key . '_', '', $value);
                        $sql .= "FIND_IN_SET('{$value}', `T1`.`{$field_key}`) AND ";
                    }
                    $sql = substr($sql, 0, -5);
                    $sql .= ") ";
                }
                // Number
                elseif (is_array($field_value) && isset($field_value['cond']) && isset($field_value['number'])) {
                    $cond = '';
                    switch ($field_value['cond']) {
                        case 'equals':
                            $cond = '=';
                            break;
                        case 'less':
                            $cond = '<';
                            break;
                        case 'more':
                            $cond = '>';
                            break;
                    }
                    $sql .= "AND `T1`.`{$field_key}` {$cond} {$field_value['number']} ";
                }
                // Other types
                else {
                    $field_value_legacy = str_replace($field_key . '_', '', $field_value);
                    $sql .= "AND (`T1`.`{$field_key}` = '{$field_value}' OR `T1`.`{$field_key}` = '{$field_value_legacy}') ";
                }
            }
        }

        $plugin_name = 'listings_box';
        $rlHook->load('listingsModifyWhere', $sql, $plugin_name); // > 4.1.0
        $rlHook->load('listingsModifyGroup');

        $sql .= 'ORDER BY ';
        if ($category['ID'] && $by_category) {
            $sql .= "`Category_match` DESC, ";
        }
        switch ($order) {
            case 'popular':
                $sql .= "`T1`.`Shows` DESC ";
                break;
            case 'top_rating':
                $sql .= "`Middle_rating` DESC ";
                break;
            case 'featured':
                $sql .= "`T1`.`Last_show` ASC, RAND() ";
                break;
            case 'recently_added':
                $date_field = $config['recently_added_order_field'] ?: 'Date';
                $sql .= "`T1`.`{$date_field}` DESC ";
                break;
            default:
                $sql .= "`T1`.`ID` DESC ";
                break;
        }

        if ($order === 'random') {
            $sql .= "LIMIT 1000) AS `Sub` ORDER BY RAND() ";
        }

        $sql .= "LIMIT " . (int) $limit;

        $sql = str_replace('{hook}', $hook, $sql);

        $listings = $rlDb->getAll($sql);
        $listings = $GLOBALS['rlLang']->replaceLangKeys($listings, 'categories', 'name');

        if (empty($listings)) {
            return false;
        }

        /**
         * @since 3.0.3
         */
        $block_key = $GLOBALS['rlSmarty']->_tpl_vars['block']['Key'];

        $rlHook->load('listingsAfterSelectFeatured', $sql, $block_key, $listings);

        $this->IDs = [];
        foreach ($listings as $key => $value) {
            // add id in selected array
            $rlListings->selectedIDs[] = $value['ID'];
            $this->IDs[] = $value['ID'];

            // populate fields
            $fields = $rlListings->getFormFields($value['Category_ID'], 'featured_form', $value['Listing_type']);

            foreach ($fields as &$field) {
                $field['value'] = $GLOBALS['rlCommon']->adaptValue(
                    $field,
                    $value[$field['Key']],
                    'listing',
                    $value['ID'],
                    true,
                    false,
                    false,
                    false,
                    $value['Account_ID'],
                    'short_form',
                    $value['Listing_type']
                );
            }

            $listings[$key]['fields'] = $fields;
            $listings[$key]['listing_title'] = $rlListings->getListingTitle($value['Category_ID'], $value, $value['Listing_type']);
            $listings[$key]['url'] = $GLOBALS['reefless']->getListingUrl($listings[$key]);
        }

        // save show date
        if ($this->IDs && $order === 'featured') {
            $sql = "UPDATE `{db_prefix}listings` SET `Last_show` = NOW() ";
            $sql .= "WHERE `ID` = " . implode(" OR `ID` = ", $this->IDs);
            $rlDb->shutdownQuery($sql);
        }

        return $listings;
    }

    /**
     * Load more listing button handler
     *
     * @since 3.2.0
     * @param array  &$out          - Ajax response array
     * @param string &$request_mode - Ajax request more
     */
    public function hookAjaxRequest(&$out, &$request_mode)
    {
        if ($request_mode != 'lbLoadMoreListings') {
            return;
        }

        $box_key = Valid::escape($_REQUEST['key']);
        $ids = Valid::escape($_REQUEST['ids']);
        $side_bar_exists = Valid::escape($_REQUEST['sideBarExists']);
        $block_side = Valid::escape($_REQUEST['blockSide']);
        $page_key = Valid::escape($_REQUEST['pageKey']);

        $results = $this->getMoreListings($box_key, $ids, $page_key, $side_bar_exists, $block_side);

        $out = array(
            'status' => 'OK',
            'results' => $results
        );
    }

    /**
     * @hook tplHeader
     */
    public function hookTplHeader()
    {
        $include = false;

        foreach ($GLOBALS['blocks'] as $block) {
            if ($block['Plugin'] == 'listings_box') {
                $include = true;
                $GLOBALS['lang']['blocks+header_link+' . $block['Key']] = $GLOBALS['lang']['lb_view_all'];
            }
        }

        if ($include) {
            $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'listings_box' . RL_DS . 'header.tpl');
        }
    }

    /**
     *  Define plugin related boxes and remove not supported box positions
     *  in edit box mode
     *
     *  @hook apPhpBlocksPost
     */
    public function hookApPhpBlocksPost()
    {
        global $block_info;

        if ($block_info['Plugin'] != 'listings_box') {
            return;
        }

        $this->rejectBoxSides();
    }

    /**
     * Get more listings
     *
     * @since 3.2.0
     *
     * @param  string $boxKey      - Box key
     * @param  string $selectedIDs - Currently selected listing IDs
     * @return array               - Prepared results array
     */
    public function getMoreListings(string $boxKey, string $selectedIDs, string $pageKey, string $sideBarExists, string $blockSide): array
    {
        global $rlSmarty, $config, $reefless, $rlDb;

        require_once RL_ROOT . 'templates' . RL_DS . $config['template'] . RL_DS . 'settings.tpl.php';

        $results = array();
        $page_info = array(
            'Controller' => $pageKey,
            'Key' => $pageKey,
        );

        $rlSmarty->assign('side_bar_exists', $sideBarExists);
        $rlSmarty->assign('block', array('Side' => $blockSide));

        $reefless->loadClass('Listings');
        $GLOBALS['rlListings']->selectedIDs = explode(',', $selectedIDs);

        $box_id     = str_replace('listing_box_', '', $boxKey);
        $box_info   = $rlDb->fetch(
            '*',
            array('ID' => $box_id),
            null, 1, 'listing_box', 'row'
        );
        $limit      = $box_info['Count'];
        $next_limit = $limit < 10 ? $limit * 2 : $limit;
        $tpl        = RL_PLUGINS . 'listings_box' . RL_DS . 'listings_box.block.tpl';
        $listings   = $GLOBALS['rlListingsBox']->getListings(
            $box_info['Type'],
            $box_info['Box_type'],
            $next_limit,
            1,
            $box_info['By_category'],
            $box_info['Category_IDs'],
            $box_info['Use_subcats'],
            $box_info['Filters']
        );
        $count      = $listings ? count($listings) : 0;
        $next       = $count == $next_limit;

        $box_option = array(
            'display_mode' => $box_info['Display_mode']
        );

        $rlSmarty->assign('box_option', $box_option);
        $rlSmarty->assign_by_ref('listings_box', $listings);

        if ($listings) {
            $rlSmarty->preAjaxSupport();

            $results = array(
                'next'  => $next,
                'count' => $count,
                'ids'   => $GLOBALS['rlListings']->selectedIDs,
                'html'  => $rlSmarty->fetch($tpl, null, null, false)
            );

            $rlSmarty->postAjaxSupport($results, $page_info, $tpl);
        }

        return $results;
    }

    /**
     *  Remove not supported box positions for plugin related boxes
     */
    public function rejectBoxSides()
    {
        global $l_block_sides;

        foreach ($this->rejectedBoxSides as $side) {
            unset($l_block_sides[$side]);
        }
    }

    /**
     * Add necessary indexes in table to improve performance in boxes
     *
     * @since 3.0.7
     *
     * @param array|null $data
     *
     * @return void
     */
    public function addIndexes(?array $data = []): void
    {
        global $rlDb;

        if (!$data || !$data['box_type']) {
            return;
        }

        if ($data['box_type'] === 'popular') {
            if (!$rlDb->getRow("SHOW INDEXES FROM `{db_prefix}listings` WHERE `Column_name` = 'Shows'")) {
                $rlDb->query("ALTER TABLE `{db_prefix}listings` ADD INDEX (`Shows`)");
            }
        }
    }

    /**
     * Remove unnecessary indexes from table
     *
     * @since 3.0.7
     *
     * @return void
     */
    public function removeIndexes(): void
    {
        global $rlDb;

        if (!$rlDb->getOne('Box_type', "`Box_type` = 'popular'", 'listing_box')
            && $rlDb->getRow("SHOW INDEXES FROM `{db_prefix}listings` WHERE `Column_name` = 'Shows'")
        ) {
            $rlDb->query("ALTER TABLE `{db_prefix}listings` DROP INDEX `Shows`");
        }
    }

    /**
     *  Update plugin to version 3.1.0
     */
    public function update310()
    {
        global $rlDb;

        $rlDb->addColumnsToTable(
            array(
                'Use_category' => "ENUM('1','0') NOT NULL DEFAULT '0'",
                'Use_subcats' => "ENUM('1','0') NOT NULL DEFAULT '0'",
                'Category_IDs' => "VARCHAR(255) NOT NULL",
            ),
            'listing_box'
        );
    }

    /**
     *  Update to 3.2.0
     */
    public function update320()
    {
        global $rlDb;

        $rlDb->addColumnsToTable([
            'Filters' => "TEXT NOT NULL",
            'Load_more' => "ENUM('0','1') NOT NULL DEFAULT '0' AFTER `Use_category`"
        ], 'listing_box');
    }
}
