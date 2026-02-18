<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.10.1
 *  LICENSE: FL1KU6QLIUJA - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: bizlisting.xyz
 *  FILE: LISTING_DETAILS.INC.PHP
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

use Flynax\Classes\ListingData;

$reefless->loadClass('Listings');
$reefless->loadClass('MembershipPlan');

// Get listing info
$sql = "SELECT `T1`.*, `T2`.`Type` AS `Listing_type`, `T2`.`Key` AS `Cat_key`, `T2`.`Type` AS `Cat_type`,";
$sql .= "`T2`.`Path` AS `Cat_path`, `T2`.`Parent_keys`, `T2`.`Parent_IDs`, `T2`.`Parent_ID`, ";

if ($config['multilingual_paths'] && RL_LANG_CODE !== $config['lang']) {
    $sql .= 'IF(`T2`.`Path_' . RL_LANG_CODE . "` <> '', ";
    $sql .= '`T2`.`Path_' . RL_LANG_CODE . '`, `T2`.`Path`) AS `Path`, ';
} else {
    $sql .= '`T2`.`Path`, ';
}

if ($config['membership_module']) {
    $sql .= "IF (`T1`.`Plan_type` = 'account', `T7`.`Image`, `T3`.`Image`) AS `Image`, ";
    $sql .= "IF (`T1`.`Plan_type` = 'account', `T7`.`Image_unlim`, `T3`.`Image_unlim`) AS `Image_unlim`, ";
    $sql .= "IF (`T1`.`Plan_type` = 'account', `T7`.`Video`, `T3`.`Video`) AS `Video`, ";
    $sql .= "IF (`T1`.`Plan_type` = 'account', `T7`.`Video_unlim`, `T3`.`Video_unlim`) AS `Video_unlim`, ";
} else {
    $sql .= "`T3`.`Image`, `T3`.`Image_unlim`, `T3`.`Video`, `T3`.`Video_unlim`, ";
}

$sql .= "CONCAT('categories+name+', `T2`.`Key`) AS `Category_pName` ";
$sql .= "FROM `{db_prefix}listings` AS `T1` ";
$sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
$sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T3` ON `T1`.`Plan_ID` = `T3`.`ID` ";
$sql .= "LEFT JOIN `{db_prefix}accounts` AS `T5` ON `T1`.`Account_ID` = `T5`.`ID` ";

if ($config['membership_module']) {
    $sql .= "LEFT JOIN `{db_prefix}membership_plans` AS `T7` ON `T1`.`Plan_ID` = `T7`.`ID` ";
}
$sql .= "WHERE `T1`.`ID` = '{$listing_id}' AND `T2`.`Status` = 'active' AND `T5`.`Status` = 'active' ";

if (!$config['ld_keep_alive']) {
    $sql .= "AND `T1`.`Status` = 'active' ";
}

$rlHook->load('listingDetailsSql', $sql);

$sql .= "LIMIT 1";

$listing_data = $rlDb->getRow($sql);
$listing_type = $rlListingTypes->types[$listing_data['Listing_type']];
$listing_data = ListingData::fillOptionsForListing($listing_data, $listing_type, true, true);

$rlSmarty->assign_by_ref('listing_data', $listing_data);
$rlSmarty->assign_by_ref('listing_type', $listing_type);

$listing_title = $listing_data['listing_title'];

// Define membership plan allowed services
$rlMembershipPlan->isContactsAllow();
$rlMembershipPlan->isSendMessage();
$allow_photos = $rlMembershipPlan->isPhotoAllow($listing_data);
$rlSmarty->assign_by_ref('allow_photos', $allow_photos);

if ($listing_data['Status'] != 'active' && $config['ld_keep_alive']) {
    $page_info['Listing_details_inactive'] = true;
    foreach (explode(",", $config['ld_keep_hiddenfields']) as $key => $unset) {
        unset($listing_data[$unset]);
    }

    if ($tpl_settings['type'] == 'responsive_42') {
        unset($blocks['get_more_details']);
    }
}

/* validate listing url */
if ($config['mod_rewrite'] && $listing_data) {
    $rlListings->originalUrlRedirect('listing', $listing_data);
}

// get "Login" parameter of "View Details" page
$page_info['Login'] = $rlDb->getOne('Login', "`Key` = 'view_details'", 'pages');

if (empty($listing_id)
    || empty($listing_data)
    || ($listing_data['Status'] != 'active'
        && $listing_data['Account_ID'] != $account_info['ID']
        && !$config['ld_keep_alive']
    )
) {
    $sError = true;
} elseif ($listing_data['Status'] != 'active' && !$config['ld_keep_alive']) {
    $errors[] = $lang['error_listing_expired'];
} else {
    if (($rlAccount->isLogin() && $page_info['Login']) || !$page_info['Login']) {
        $rlHook->load('listingDetailsTop');

        // count visit
        if ($config['count_listing_visits']) {
            register_shutdown_function(array($rlListings, 'countVisit'), $listing_data['ID']);
        }

        /* enable print page */
        $print = array(
            'item' => 'listing',
            'id'   => $listing_data['ID'],
        );
        $rlSmarty->assign_by_ref('print', $print);

        /* display add to favourite icon */
        $navIcons[] = '<a title="' . $lang['add_to_favorites'] . '" id="fav_' . $listing_data['ID'] . '" class="icon add_favorite" href="javascript:void(0)"> <span></span> </a>';

        // Add "back to search results" link | DEPRECATED FROM 4.1.0 >
        if ($_SESSION['keyword_search_data']) {
            $navIcons = array_reverse($navIcons);

            if ($config['mod_rewrite']) {
                $pagingPath = $_SESSION['keyword_search_pageNum'] > 1
                    ? ["index{$_SESSION['keyword_search_pageNum']}"]
                    : null;
                $return_link = $reefless->getPageUrl('search', $pagingPath);
            } else {
                $pagingVars = $_SESSION['keyword_search_pageNum'] > 1
                    ? "pg={$_SESSION['keyword_search_pageNum']}"
                    : null;
                $return_link = $reefless->getPageUrl('search', null, null, $pagingVars);
            }

            $navIcons[] = '<a title="'
                . $lang['back_to_search_results']
                . '" href="' . $return_link . '">&larr; '
                . $lang['back_to_search_results'] . '</a>';
            $navIcons = array_reverse($navIcons);
        } elseif ($_SESSION[$listing_type['Key'] . '_post']) {
            $navIcons = array_reverse($navIcons);

            if ($_SESSION[$listing_type['Key'] . '_advanced']) {
                $pagingPath = [$advanced_search_url, $search_results_url];
            } else {
                $pagingPath = [$search_results_url];
            }

            if ($config['mod_rewrite']) {
                if ($_SESSION[$listing_type['Key'] . '_pageNum'] > 1) {
                    $pagingPath[] = "index{$_SESSION[$listing_type['Key'] . '_pageNum']}";
                }

                $return_link = $reefless->getPageUrl($listing_type['Page_key'], $pagingPath);
            } else {
                $pagingVars = $_SESSION[$listing_type['Key'] . '_pageNum'] > 1
                    ? "pg={$_SESSION[$listing_type['Key'] . '_pageNum']}"
                    : null;
                $return_link = $reefless->getPageUrl($listing_type['Page_key'], $pagingPath, null, $pagingVars);
            }

            $navIcons[] = '<a title="'
                . $lang['back_to_search_results']
                . '" href="' . $return_link . '">&larr; '
                . $lang['back_to_search_results'] . '</a>';
            $navIcons = array_reverse($navIcons);
        }
        // DEPRECATED

        $rlSmarty->assign_by_ref('navIcons', $navIcons);

        // define "is owner"
        $rlSmarty->assign('is_owner', $account_info['ID'] == $listing_data['Account_ID']);

        /* build listing structure */
        $category_id = $listing_data['Category_ID'];
        $listing = $rlListings->getListingDetails($category_id, $listing_data, $listing_type);
        $rlSmarty->assign_by_ref('listing', $listing);

        /* get seller information */
        $seller_info = $rlAccount->getProfile((int) $listing_data['Account_ID']);
        $rlSmarty->assign_by_ref('seller_info', $seller_info);

        // re-assign is_contact_allowed value in case if the logged in user is owner of the listing
        if ($account_info['ID'] == $seller_info['ID']) {
            $rlMembershipPlan->is_contact_allowed = true;
        }

        // get short form details in case if own page option disabled
        $owner_short_details = $rlAccount->getShortDetails($seller_info, $seller_info['Account_type_ID'], true);
        if ($account_info['ID'] != $seller_info['ID']) {
            $rlMembershipPlan->fakeValues($owner_short_details);
        }
        $rlSmarty->assign_by_ref('owner_short_details', $owner_short_details);

        /* get location data for google map */
        $fields_list = $rlListings->fieldsList;

        $location = false;
        foreach ($fields_list as $key => $value) {
            if ($fields_list[$key]['Map'] && !empty($listing_data[$fields_list[$key]['Key']])) {
                $mValue = addslashes($value['value']);
                $location['search'] .= $mValue . ', ';
                $location['show'] .= $lang[$value['pName']] . ': <b>' . $mValue . '<\/b><br />';
                unset($mValue);
            }
        }
        if (!empty($location)) {
            $location['search'] = substr($location['search'], 0, -2);
        }
        if ($listing_data['Loc_latitude'] && $listing_data['Loc_longitude']) {
            $location['direct'] = $listing_data['Loc_latitude'] . ',' . $listing_data['Loc_longitude'];
        }
        $rlSmarty->assign_by_ref('location', $location);
        /* get location data for google map end */

        // Add information about the categories to the breadcrumbs
        $reefless->loadClass('Categories');
        $rlCategories->buildCategoryBreadCrumbs($bread_crumbs, $category_id, $listing_type);

        /**
         * Prevent problems caused by using this deprecated variable.
         * @todo Prevent usage in plugins (replace with $bread_crumbs) and remove it
         * @deprecated 4.10.1
         */
        $cat_bread_crumbs = [];

        /**
         * @since 4.7.1
         */
        $rlHook->load('listingDetailsBeforeMetaData', $page_info, $listing, $listing_data);

        $bread_crumbs[] = array(
            'title' => $listing_title,
            'name'  => $lang['pages+name+view_details'],
        );

        $page_info['name']  = $listing_title;
        $page_info['title'] = $listing_title;

        $page_info['meta_description'] = $rlListings->replaceMetaFields($listing_data['Category_ID'], $listing_data, 'description');
        $page_info['meta_title'] = $rlListings->replaceMetaFields($listing_data['Category_ID'], $listing_data, 'title');

        $photos_limit = $listing_data['Image_unlim'] ? true : $listing_data['Image'];
        $videos_limit = $listing_data['Video_unlim'] ? true : $listing_data['Video'];

        // Get listing media
        $media = Flynax\Utils\ListingMedia::get($listing_id, $photos_limit, $videos_limit, $listing_type);
        $rlSmarty->assign_by_ref('photos', $media);

        /* get amenties */
        if ($config['map_amenities']) {
            $rlDb->setTable('map_amenities');
            $amenities = $rlDb->fetch(array('Key', 'Default'), array('Status' => 'active'), "ORDER BY `Position`");
            $amenities = $rlLang->replaceLangKeys($amenities, 'map_amenities', array('name'));
            $rlSmarty->assign_by_ref('amenities', $amenities);
        }

        /* populate tabs */
        $tabs = array(
            'listing'     => array(
                'key'  => 'listing',
                'name' => $lang['listing'],
            ),
            'tell_friend' => array(
                'key'  => 'tell_friend',
                'name' => $lang['tell_friend'],
            ),
        );

        if ($page_info['Listing_details_inactive'] || !$config['tell_a_friend_tab']) {
            unset($tabs['tell_friend']);
        }
        $rlSmarty->assign_by_ref('tabs', $tabs);

        $reefless->loadClass('Message');

        /* register ajax methods */
        $rlXajax->registerFunction(array('tellFriend', $rlListings, 'ajaxTellFriend'));
        $rlXajax->registerFunction(array('contactOwner', $rlMessage, 'ajaxContactOwner'));

        $rlHook->load('listingDetailsBottom');

        $rlStatic->addHeaderCss(RL_TPL_BASE . 'controllers/listing_details/listing_details.css');

        if ($media) {
            $rlStatic->addComponentCSS('listingDetailsGalleryComponents', 'listing-details-gallery');
        }

        $rlStatic->addHeaderCss(RL_TPL_BASE . 'components/uploaded-file/uploaded-file.css');

        if ($config['show_call_owner_button']) {
            $rlStatic->addHeaderCss(RL_TPL_BASE . 'components/call-owner/call-owner-buttons.css');
        }
    } else {
        // remove box with contact seller form
        unset($blocks['get_more_details']);
        $rlCommon->defineBlocksExist($blocks);
    }
}
