<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.10.1
 *  LICENSE: FL1KU6QLIUJA - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: bizlisting.xyz
 *  FILE: RLTMPCATEGORIES.CLASS.PHP
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

class rlTmpCategories
{
    /**
     * delete category
     *
     * @package ajax
     *
     * @param int $id - custom category ID
     *
     **/
    public function ajaxDeleteTmpCategory($id = false)
    {
        global $_response, $lang, $rlListingTypes, $reefless, $rlDb;

        // check admin session expire
        if ($reefless->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        if (!$id) {
            return $_response;
        }

        $id = (int) $id;

        /* inform category owner */
        $tmp_category = $rlDb->fetch(array('Parent_ID', 'Account_ID', 'Name', 'Listing_ID'), array('ID' => $id), null, 1, 'tmp_categories', 'row');
        $category = $rlDb->fetch(array('Type', 'Path', 'ID'), array('ID' => $tmp_category['Parent_ID']), null, 1, 'categories', 'row');

        $reefless->loadClass('Account');
        $reefless->loadClass('Mail');
        $reefless->loadClass('Listings');

        $owner_info = $GLOBALS['rlAccount']->getProfile((int) $tmp_category['Account_ID']);

        $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate('custom_category_deleted', $owner_info['Lang']);
        $mail_tpl['body'] = str_replace(array('{category_name}', '{name}'), array($tmp_category['Name'], $owner_info['Full_name']), $mail_tpl['body']);

        if ($tmp_category['Listing_ID']) {
            $listing_info = $rlDb->fetch('*', array('ID' => $tmp_category['Listing_ID']), null, 1, 'listings', 'row');
            $listing_title = $GLOBALS['rlListings']->getListingTitle($category['ID'], $listing_info, $category['Type']);

            $link = $reefless->getListingUrl($listing_info);
            $link = '<a href="' . $link . '">' . $listing_title . '</a>';

            $mail_tpl['body'] = str_replace(array('{link}', '{if listing}', '{/if}'), array($link, '', ''), $mail_tpl['body']);
        } else {
            $mail_tpl['body'] = preg_replace('/\{if listing\}(.*)\{\/if\}/', '', $mail_tpl['body']);
        }

        $GLOBALS['rlMail']->send($mail_tpl, $owner_info['Mail']);

        /* deelte category */
        $GLOBALS['rlActions']->delete(array('ID' => $id), array('tmp_categories'), null, 1, $id);

        $del_mode = $GLOBALS['rlActions']->action;

        $_response->script("printMessage('notice', '{$lang['category_' . $del_mode]}');");

        $_response->script("categoriesGrid.reload();");

        return $_response;
    }

    /**
     * activate custom category
     *
     * @package ajax
     *
     * @param int $id - custom category ID
     *
     **/
    public function ajaxActivateTmpCategory($id)
    {
        global $_response, $lang, $rlCache, $reefless, $rlDb, $rlLang, $languages, $rlListings;

        // check admin session expire
        if ($reefless->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        if (!$id) {
            return $_response;
        }

        $id = (int) $id;

        $sql = "SELECT `T1`.*, `T2`.`ID` AS `Category_ID`, `T2`.`Position`, `T2`.`Path`, `T2`.`Level`, `T2`.`Type`, `T2`.`Tree` AS `Parent_Tree` ";
        $sql .= "FROM `{db_prefix}tmp_categories` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Parent_ID` = `T2`.`ID` ";
        $sql .= "WHERE `T1`.`ID` = {$id} LIMIT 1";
        $tmp_category = $rlDb->getRow($sql);

        $max_position = $rlDb->getRow("SELECT MAX(`Position`) AS `Max` FROM `{db_prefix}categories` WHERE `Parent_ID` = {$tmp_category['Parent_ID']}");
        $max_position = $max_position['Max'] + 1;

        /* load the utf8 lib */
        loadUTF8functions('ascii', 'utf8_to_ascii', 'unicode');

        $key = $tmp_category['Name'];

        /* check name */
        if (!utf8_is_ascii($key)) {
            $key = utf8_to_ascii($tmp_category['Name']);
        }

        $path = $GLOBALS['rlValid']->str2path($key);
        $tc_key = 'custom_' . $GLOBALS['rlValid']->str2key($key);

        if ($tmp_category['Listing_ID']) {
            $l_sql = "SELECT `T1`.`ID` FROM `{db_prefix}listings` AS `T1` ";
            $l_sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T2` ON `T1`.`Plan_ID` = `T2`.`ID` ";
            $l_sql .= "WHERE UNIX_TIMESTAMP(DATE_ADD(`T1`.`Pay_date`, INTERVAL `T2`.`Listing_period` DAY)) > UNIX_TIMESTAMP(NOW()) AND `T1`.`Status` = 'active' ";
            $l_sql .= "AND `T1`.`ID` = '{$tmp_category['Listing_ID']}' ";
            $l_sql .= "LIMIT 1";

            $listing_active = $rlDb->getRow($l_sql);
        }

        if ($tmp_category['Parent_ID']) {
            $reefless->loadClass('Categories');

            $parent_ids[] = $tmp_category['Parent_ID'];
            if ($parents = $GLOBALS['rlCategories']->getParentIDs($tmp_category['Parent_ID'])) {
                $parent_ids = array_merge($parents, $parent_ids);
            }
            $parent_ids = implode(',', $parent_ids);

            // get Keys of parent categories
            $sql = "SELECT GROUP_CONCAT(DISTINCT `Key`) as `Keys` FROM `{db_prefix}categories` ";
            $sql .= "WHERE FIND_IN_SET(`ID`, '{$parent_ids}') ";
            $sql .= "ORDER BY FIND_IN_SET(`ID`, '{$parent_ids}')";

            $parent_keys = $reefless->getRow($sql, 'Keys');
        }

        $insertData = array(
            'Position'    => $max_position,
            'Path'        => $tmp_category['Path'] . '/' . $path,
            'Level'       => $tmp_category['Level'] + 1,
            'Parent_ID'   => $tmp_category['Parent_ID'],
            'Parent_IDs'  => $parent_ids,
            'Parent_keys' => $parent_keys,
            'Type'        => $tmp_category['Type'],
            'Tree'        => $tmp_category['Parent_Tree'] . '.' . $max_position,
            'Key'         => $tc_key,
            'Count'       => empty($listing_active) ? 0 : 1,
            'Lock'        => 0,
            'Modified'    => 'NOW()',
            'Status'      => 'active',
        );

        $rlDb->insertOne($insertData, 'categories');

        $insert_category_id = $rlDb->insertID();

        if ($tmp_category['Listing_ID'] && $insert_category_id) {
            $updateData = array(
                'fields' => array('Category_ID' => $insert_category_id),
                'where'  => array('ID' => $tmp_category['Listing_ID']),
            );

            $rlDb->updateOne($updateData, 'listings');
        }

        $insertPhrases = [];
        foreach ($languages as $language) {
            $insertPhrases[] = array(
                'Code'   => $language['Code'],
                'Module' => 'category',
                'Status' => 'active',
                'Key'    => 'categories+name+' . $tc_key,
                'Value'  => $tmp_category['Name'],
            );
        }
        $rlLang->createPhrases($insertPhrases);

        $reefless->loadClass('Mail');
        $reefless->loadClass('Account');

        /* inform category owner */
        $owner_info = $GLOBALS['rlAccount']->getProfile((int) $tmp_category['Account_ID']);

        $categoryURL = $reefless->getCategoryUrl($insertData);
        $category_location = "<a href=\"{$categoryURL}\">{$categoryURL}</a>";

        $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate('custom_category_activated', $owner_info['Lang']);
        $mail_tpl['body'] = str_replace(
            ['{category_name}', '{category_location}', '{name}'],
            [$tmp_category['Name'], $category_location, $owner_info['Full_name']],
            $mail_tpl['body']
        );

        if ($tmp_category['Listing_ID']) {
            $reefless->loadClass('Listings');
            $listing = $rlListings->getListing((int) $tmp_category['Listing_ID'], true);
            $link = "<a href=\"{$listing['url']}\">{$listing['listing_title']}</a>";
            $mail_tpl['body'] = str_replace(array('{link}', '{if listing}', '{/if}'), array($link, '', ''), $mail_tpl['body']);
        } else {
            $mail_tpl['body'] = preg_replace('/\{if listing\}(.*)\{\/if\}/', '', $mail_tpl['body']);
        }

        $GLOBALS['rlMail']->send($mail_tpl, $owner_info['Mail']);

        /* remove tmp category */
        $dsql = "DELETE FROM `{db_prefix}tmp_categories` WHERE `ID` = '{$tmp_category['ID']}' LIMIT 1";
        $rlDb->query($dsql);

        $rlCache->updateCategories();

        $_response->script("
            categoriesGrid.reload();
            printMessage('notice', '{$lang['category_activated']}');
        ");

        return $_response;
    }
}
