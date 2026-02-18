<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.10.1
 *  LICENSE: FL1KU6QLIUJA - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: bizlisting.xyz
 *  FILE: UPGRADE_LISTING.INC.PHP
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

use Flynax\Utils\Util;

$reefless->loadClass('Plan');
$reefless->loadClass('Subscription'); // >= v4.4

unset($_SESSION['complete_payment']);

if (isset($_GET['canceled'])) {
    $errors[] = $lang['notice_payment_canceled'];
}

/* get listing info */
$listing_id = intval($_REQUEST['id'] ?: $_REQUEST['item']);

if ($listing_id) {
    $sql = "SELECT `T1`.*, `T1`.`Category_ID`, `T1`.`Status`, UNIX_TIMESTAMP(`T1`.`Pay_date`) AS `Pay_date`, `T1`.`Crossed`, ";
    $sql .= "`T2`.`Type` AS `Listing_type`, `T2`.`Path` AS `Category_path`, `T1`.`Last_type` AS `Listing_mode` ";
    $sql .= "FROM `{db_prefix}listings` AS `T1` ";
    $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
    $sql .= "WHERE `T1`.`ID` = {$listing_id} AND `T1`.`Account_ID` = '{$account_info['ID']}' ";

    $rlHook->load('upgradeListingSql', $sql);

    $sql .= "LIMIT 1";
    $listing = $rlDb->getRow($sql);
    $rlSmarty->assign_by_ref('listing', $listing); // >= v4.4
}
if (!$listing || !$listing_id) {
    if ($account_info['ID']) {
        $sError = true;
    } else {
        $errors[] = $lang['notice_should_login'];
    }
} else {
    /* get posting type of listing */
    $listing_type = $rlListingTypes->types[$listing['Listing_type']];
    $rlSmarty->assign_by_ref('listing_type', $listing_type);

    // get listing title
    $listing_title = $rlListings->getListingTitle($listing['Category_ID'], $listing, $listing['Listing_type']);
    $rlSmarty->assign_by_ref('listing_title', $listing_title);

    $category = $rlCategories->getCategory($listing['Category_ID']);

    /* simulate post plan id and listing mode */
    if (!$_POST['from_post']) {
        $_POST['plan'] = $listing['Plan_ID'];
    }
    if (!$_POST['listing_type']) {
        $_POST['listing_type'] = $listing['Listing_mode'];
    }

    /* get listing plans for current user type */
    $featured = isset($_GET['featured']) || $_GET['nvar_1'] == 'featured' ? true : false;
    $rlSmarty->assign('featured', $featured);

    $plans = $rlPlan->getPlanByCategory($listing['Category_ID'], $account_info['Type'], $featured);
    foreach ($plans as $key => $value) {
        $tmp_plans[$value['ID']] = $value;
    }
    $plans = $tmp_plans;
    unset($tmp_plans);
    $rlSmarty->assign_by_ref('plans', $plans);

    $l_type = $rlListingTypes->types[$listing['Listing_type']];

    /* add bread crumbs item */
    $my_page_key = $config['one_my_listings_page'] ? 'my_all_ads' : 'my_' . $l_type['Key'];
    $bc_last = array_pop($bread_crumbs);
    $bread_crumbs[] = array(
        'name'  => $lang['pages+name+' . $my_page_key],
        'title' => $lang['pages+title+' . $my_page_key],
        'path'  => $pages[$my_page_key],
    );
    $bread_crumbs[] = $bc_last;

    if (isset($_GET['item'])) {
        $link = $reefless->getListingUrl($listing);
        $rlSmarty->assign_by_ref('link', $link);
    } else {
        // get active subscription
        $subscription = $rlSubscription->getActiveSubscription($listing_id, $plans[$listing['Plan_ID']]['Type']); // >= v4.4
        $rlSmarty->assign_by_ref('subscription', $subscription); // >= v4.4

        if ($featured) {
            $page_info['name'] = $lang['upgrade_to_featured'];
            $page_info['title'] = $lang['upgrade_to_featured'];
        }
        if ($_POST['upgrade']) {
            $plan_id = (int) $_POST['plan'];
            $listing_mode = $_POST['ad_type'];

            /* get plan info */
            $plan_info = $plans[$plan_id];
            $current_plan_info = $plans[$listing['Plan_ID']];

            $rlHook->load('phpListingsUpgradePlanInfo');

            /* check plan id */
            if (empty($plan_id)) {
                $errors[] = $lang['notice_listing_plan_does_not_chose'];
            }

            /* check limited plans using */
            if ($plan_info['Using'] >= $plan_info['Limit'] && $plan_info['Limit'] > 0
                || $plan_info['Limit'] > 0 && $plan_info['Using'] == 0 && $plan_info['Using'] != ''
            ) {
                $errors[] = $lang['plan_limit_using_hack'];
            }

            /* check rest listings using */
            if ($plan_info['Package_ID']
                && $listing_mode
                && $plan_info[ucfirst($listing_mode) . '_remains'] <= 0
                && $plan_info[ucfirst($listing_mode) . '_listings'] > 0
                && $plan_info['Listing_number'] > 0
            ) {
                $errors[] = $lang['plan_option_using_hack'];
            }

            // Prevent usage not allowed plan/package
            if ($plan_info['plan_disabled']) {
                $errors[] = $lang['plan_option_using_hack'];
            }

            /* do plan upgrade */
            if (empty($errors)) {
                $reefless->loadClass('Mail');
                $reefless->loadClass('Notice');
                $reefless->loadClass('Account');

                /* payment handler */
                // upgrade to featured MODE
                if ($plan_info['Type'] == 'featured') {
                    // redirect to checkout
                    if ($plan_info['Price'] > 0) {
                        $rlPayment->clear();
                        $rlPayment->setRedirect();

                        // get listing title
                        $listing_title = $rlListings->getListingTitle($category['ID'], $listing, $listing_type['Key']);

                        // save payment details
                        $item_name = $lang[$plan_info['Type'] . '_plan'];
                        $cancel_url = $reefless->getPageUrl($page_info['Key'], null, null, "canceled&item={$listing_id}");
                        $success_url = $reefless->getPageUrl($page_info['Key'], null, null, "completed&item={$listing_id}");

                        // set payment options
                        $rlPayment->setOption('service', $plan_info['Type']);
                        $rlPayment->setOption('total', $plan_info['Price']);
                        $rlPayment->setOption('plan_id', $plan_info['ID']);
                        $rlPayment->setOption('item_id', $listing_id);
                        $rlPayment->setOption('item_name', $listing_title . ' (#' . $listing_id . ')');
                        $rlPayment->setOption('plan_key', 'listing_plans+name+' . $plan_info['Key']);
                        $rlPayment->setOption('account_id', $account_info['ID']);
                        $rlPayment->setOption('callback_class', 'rlListings');
                        $rlPayment->setOption('callback_method', 'upgradeListing');
                        $rlPayment->setOption('cancel_url', $cancel_url);
                        $rlPayment->setOption('success_url', $success_url);

                        // set recurring option
                        if ($plan_info['Subscription'] && $_POST['subscription'] == $plan_info['ID']) {
                            $rlPayment->enableRecurring();
                        }

                        // set bread crumbs
                        $my_page_key = $config['one_my_listings_page']
                        ? 'my_all_ads'
                        : 'my_' . $listing['Listing_type'];

                        $rlPayment->setBreadCrumbs(
                            array(
                                'name'  => $lang['pages+name+' . $my_page_key],
                                'title' => $lang['pages+title+' . $my_page_key],
                                'path'  => $pages[$my_page_key],
                            )
                        );

                        $rlPayment->init($errors);
                    } else {
                        $update = array(
                            'fields' => array(
                                'Featured_ID'   => $plan_info['ID'],
                                'Featured_date' => 'NOW()',
                            ),
                            'where'  => array(
                                'ID' => $listing_id,
                            ),
                        );

                        if ($rlDb->updateOne($update, 'listings')) {
                            /* limited option handler */
                            if ($plan_info['Limit'] > 0) {
                                if ($plan_info['Using'] == '') {
                                    $plan_using_insert = array(
                                        'Account_ID'       => $account_info['ID'],
                                        'Plan_ID'          => $plan_info['ID'],
                                        'Listings_remains' => $plan_info['Limit'] - 1,
                                        'Type'             => 'limited',
                                        'Date'             => 'NOW()',
                                        'IP'               => Util::getClientIP(),
                                    );
                                    $rlDb->insertOne($plan_using_insert, 'listing_packages');
                                } else {
                                    $plan_using_update = array(
                                        'fields' => array(
                                            'Account_ID'       => $account_info['ID'],
                                            'Plan_ID'          => $plan_info['ID'],
                                            'Listings_remains' => $plan_info['Using'] - 1,
                                            'Type'             => 'limited',
                                            'Date'             => 'NOW()',
                                            'IP'               => Util::getClientIP(),
                                        ),
                                        'where'  => array(
                                            'ID' => $plan_info['Plan_using_ID'],
                                        ),
                                    );
                                    $rlDb->updateOne($plan_using_update, 'listing_packages');
                                }
                            }

                            /* send notification to listing owner */
                            $mail_tpl = $rlMail->getEmailTemplate('listing_upgraded_to_featured');

                            $link = $reefless->getListingUrl($listing);
                            $_SESSION['notice_link'] = $link;

                            $find = array('{name}', '{listing}', '{plan_name}', '{plan_price}', '{start_date}', '{expiration_date}');
                            $replace = array(
                                $account_info['Full_name'],
                                '<a href="' . $link . '">' . $listing_title . '</a>',
                                $plan_info['name'],
                                $lang['free'],
                                date(str_replace(array('b', '%'), array('M', ''), RL_DATE_FORMAT)),
                                date(str_replace(array('b', '%'), array('M', ''), RL_DATE_FORMAT), strtotime('+' . $plan_info['Listing_period'] . ' days')),
                            );

                            $mail_tpl['body'] = str_replace($find, $replace, $mail_tpl['body']);
                            $mail_tpl['body'] = preg_replace('/\{if.*\{\/if\}(<br\s+\/>)?/', '', $mail_tpl['body']);

                            $rlMail->send($mail_tpl, $account_info['Mail']);

                            /* send notification to administrator */
                            $mail_tpl = $rlMail->getEmailTemplate('listing_upgraded_to_featured_for_admin');

                            $link = RL_URL_HOME . ADMIN . '/index.php?controller=listings&amp;action=view&amp;id=' . $listing_id;

                            $find = array('{listing}', '{plan_name}', '{listing_id}', '{owner}', '{start_date}', '{expiration_date}');
                            $replace = array(
                                '<a href="' . $link . '">' . $listing_title . '</a>',
                                $plan_info['name'],
                                $listing_id,
                                $account_info['Full_name'],
                                date(str_replace(array('b', '%'), array('M', ''), RL_DATE_FORMAT)),
                                date(str_replace(array('b', '%'), array('M', ''), RL_DATE_FORMAT), strtotime('+' . $plan_info['Listing_period'] . ' days')),
                            );

                            $mail_tpl['body'] = str_replace($find, $replace, $mail_tpl['body']);
                            $rlMail->send($mail_tpl, $config['notifications_email']);
                        }
                    }
                }
                // update plan MODE | redirect to checkout
                elseif (($plan_info['Type'] == 'package' && !isset($plan_info['Listings_remains']) && $plan_info['Price'] > 0) || ($plan_info['Type'] == 'listing' && $plan_info['Price'] > 0)) {
                    $update_plan_id = $plan_info['ID'];
                    $update_featured_id = ($plan_info['Featured'] && !$plan_info['Advanced_mode']) || ($plan_info['Advanced_mode'] && $listing_mode == 'featured') ? $plan_info['ID'] : '';

                    // clear payment options
                    $rlPayment->clear();
                    $rlPayment->setRedirect();

                    // get listing title
                    $listing_title = $rlListings->getListingTitle($listing['Category_ID'], $listing, $listing_type['Key']);

                    // save payment details
                    $cancel_url = $reefless->getPageUrl($page_info['Key'], null, null, "canceled&item={$listing_id}");
                    $success_url = $reefless->getPageUrl($page_info['Key'], null, null, "completed&item={$listing_id}");

                    // set payment options
                    $rlPayment->setOption('service', $plan_info['Type']);
                    $rlPayment->setOption('total', $plan_info['Price']);
                    $rlPayment->setOption('plan_id', $plan_info['ID']);
                    $rlPayment->setOption('item_id', $listing_id);
                    $rlPayment->setOption('item_name', $listing_title . ' (#' . $listing_id . ')');
                    $rlPayment->setOption('plan_key', 'listing_plans+name+' . $plan_info['Key']);
                    $rlPayment->setOption('account_id', $account_info['ID']);
                    $rlPayment->setOption('callback_class', 'rlListings');
                    $rlPayment->setOption('callback_method', 'upgradeListing');
                    $rlPayment->setOption('cancel_url', $cancel_url);
                    $rlPayment->setOption('success_url', $success_url);

                    // set recurring option
                    if ($plan_info['Subscription'] && $_POST['subscription'] == $plan_info['ID']) {
                        $rlPayment->enableRecurring();
                    }

                    // if select featured option
                    if ($_POST['listing_type'] == 'featured') {
                        $rlPayment->setOption('params', 'featured');
                    }

                    // set bread crumbs
                    $my_page_key = $config['one_my_listings_page']
                    ? 'my_all_ads'
                    : 'my_' . $listing['Listing_type'];

                    $rlPayment->setBreadCrumbs(
                        array(
                            'name'  => $lang['pages+name+' . $my_page_key],
                            'title' => $lang['pages+title+' . $my_page_key],
                            'path'  => $pages[$my_page_key],
                        )
                    );

                    $rlPayment->init($errors);
                }
                // update plan MODE | available package or free listing
                elseif (($plan_info['Type'] == 'package' && ($plan_info['Package_ID'] || $plan_info['Price'] <= 0)) || ($plan_info['Type'] == 'listing' && $plan_info['Price'] <= 0)) {
                    $update_featured_id = ($plan_info['Featured'] && !$plan_info['Advanced_mode']) || $listing_mode == 'featured' ? $plan_info['ID'] : '';
                    $upgrade_featured_date = ($plan_info['Featured'] && !$plan_info['Advanced_mode']) || $listing_mode == 'featured' ? 'IF(UNIX_TIMESTAMP(NOW()) > UNIX_TIMESTAMP(DATE_ADD(`Featured_date`, INTERVAL ' . $plan_info['Listing_period'] . ' DAY)) OR IFNULL(UNIX_TIMESTAMP(`Featured_date`), 0) = 0, NOW(), DATE_ADD(`Featured_date`, INTERVAL ' . $plan_info['Listing_period'] . ' DAY))' : '';
                    $upgrade_date = 'IF(UNIX_TIMESTAMP(NOW()) > UNIX_TIMESTAMP(DATE_ADD(`Pay_date`, INTERVAL ' . $plan_info['Listing_period'] . ' DAY)) OR IFNULL(UNIX_TIMESTAMP(`Pay_date`), 0) = 0, NOW(), DATE_ADD(`Pay_date`, INTERVAL ' . $plan_info['Listing_period'] . ' DAY))';

                    $update = array(
                        'fields' => array(
                            'Plan_ID'       => $plan_info['ID'],
                            'Pay_date'      => $upgrade_date,
                            'Featured_ID'   => $update_featured_id,
                            'Featured_date' => $upgrade_featured_date,
                            'Last_type'     => $listing_mode,
                            'Cron_notified' => '0',
                        ),
                        'where'  => array(
                            'ID' => $listing_id,
                        ),
                    );

                    /* update listing posting date */
                    if ($config['posting_date_update']) {
                        $update['fields']['Date'] = 'NOW()';
                    }

                    if ($listing['Status'] == 'incomplete' && $listing['Last_step'] == 'checkout') {
                        $update['fields']['Status'] = $config['listing_auto_approval'] ? 'active' : 'pending';
                        $update['fields']['Last_step'] = '';
                    }

                    if ($listing['Status'] == 'expired' || $listing['Status'] == 'approval') {
                        $update['fields']['Status'] = 'active';
                    }

                    if ($rlDb->updateOne($update, 'listings')) {
                        // available package mode
                        if ($plan_info['Type'] == 'package' && $plan_info['Package_ID']) {
                            if (($plan_info['Listing_number'] > 0 && $plan_info['Listings_remains'] > 0)
                                || $plan_info['Listing_number'] == 0
                            ) {
                                $update_entry = array(
                                    'fields' => array(
                                        'Listings_remains' => $plan_info['Listing_number'] == 0
                                        ? $plan_info['Listing_number']
                                        : $plan_info['Listings_remains'] - 1,
                                    ),
                                    'where' => array(
                                        'ID' => $plan_info['Package_ID'],
                                    ),
                                );

                                if ($plan_info[ucfirst($listing_mode) . '_listings'] != 0) {
                                    $update_entry['fields'][ucfirst($listing_mode) . '_remains'] = $plan_info[ucfirst($listing_mode) . '_remains'] - 1;
                                }
                                $rlDb->updateOne($update_entry, 'listing_packages');
                            } else {
                                echo "Logic error occurred, contact Flynax support please..."; // have to be removed after testing!
                                exit;
                            }
                        }
                        // free package mode
                        elseif ($plan_info['Type'] == 'package' && !$plan_info['Package_ID'] && $plan_info['Price'] <= 0) {
                            $insert_entry = array(
                                'Account_ID'       => $account_info['ID'],
                                'Plan_ID'          => $plan_info['ID'],
                                'Listings_remains' => $plan_info['Listing_number'] == 0
                                ? $plan_info['Listing_number']
                                : $plan_info['Listing_number'] - 1,
                                'Type'             => 'package',
                                'Date'             => 'NOW()',
                                'IP'               => Util::getClientIP(),
                            );

                            if ($plan_info['Featured'] && $plan_info['Advanced_mode'] && $plan_info['Standard_listings']) {
                                $insert_entry['Standard_remains'] = $plan_info['Standard_listings'];
                            }
                            if ($plan_info['Featured'] && $plan_info['Advanced_mode'] && $plan_info['Featured_listings']) {
                                $insert_entry['Featured_remains'] = $plan_info['Featured_listings'];
                            }

                            if ($plan_info[ucfirst($listing_mode) . '_listings'] != 0) {
                                $insert_entry[ucfirst($listing_mode) . '_remains'] = $plan_info[ucfirst($listing_mode) . '_listings'] - 1;
                            }

                            $rlDb->insertOne($insert_entry, 'listing_packages');
                        }
                        // limited listing mode
                        elseif ($plan_info['Type'] == 'listing' && $plan_info['Limit'] > 0) {
                            /* update/insert limited plan using entry */
                            if (empty($plan_info['Using'])) {
                                $plan_using_insert = array(
                                    'Account_ID'       => $account_info['ID'],
                                    'Plan_ID'          => $plan_info['ID'],
                                    'Listings_remains' => $plan_info['Limit'] - 1,
                                    'Type'             => 'limited',
                                    'Date'             => 'NOW()',
                                    'IP'               => Util::getClientIP(),
                                );

                                $rlDb->insertOne($plan_using_insert, 'listing_packages');
                            } else {
                                $plan_using_update = array(
                                    'fields' => array(
                                        'Account_ID'       => $account_info['ID'],
                                        'Plan_ID'          => $plan_info['ID'],
                                        'Listings_remains' => $plan_info['Using'] - 1,
                                        'Type'             => 'limited',
                                        'Date'             => 'NOW()',
                                        'IP'               => Util::getClientIP(),
                                    ),
                                    'where'  => array(
                                        'ID' => $plan_info['Plan_using_ID'],
                                    ),
                                );

                                $rlDb->updateOne($plan_using_update, 'listing_packages');
                            }
                        }

                        /* update listing images count if plan allows less photos then previous plan */
                        if (!$plan_info['Image_unlim'] && $plan_info['Image'] < $listing['Photos_count'] && $plan_info['Type'] != 'featured') {
                            $photos_count_update = array(
                                'fields' => array(
                                    'Photos_count' => $plan_info['Image'],
                                ),
                                'where'  => array(
                                    'ID' => $listing['ID'],
                                ),
                            );

                            $rlDb->updateOne($photos_count_update, 'listings');
                        }

                        /* recount category listings count */
                        if ($config['listing_auto_approval'] && !$rlListings->isActive($listing_id)) {
                            $rlCategories->listingsIncrease($category['ID']);
                            $rlCategories->accountListingsIncrease($account_info['ID']);
                        }

                        /* send message to listing owner */
                        $mail_tpl = $rlMail->getEmailTemplate(($config['listing_auto_approval'] || $listing['Status'] == 'active') ? 'listing_upgraded_active' : 'listing_upgraded_approval');

                        if ($config['listing_auto_approval']) {
                            $link = $reefless->getListingUrl($listing);
                        } else {
                            $link = $rlAccount::getMyListingsPageURL($listing['Listing_type']);
                        }

                        $mail_tpl['body'] = str_replace(
                            array('{name}', '{link}', '{plan}'),
                            array($account_info['Full_name'], '<a href="' . $link . '">' . $link . '</a>', $plan_info['name']),
                            $mail_tpl['body']
                        );
                        $rlMail->send($mail_tpl, $account_info['Mail']);
                    }
                }

                if (!$errors) {
                    // Crossed data control
                    if (!$plan_info['Cross'] && $current_plan_info['Cross'] && $listing['Crossed']) {
                        foreach (explode(',', $listing['Crossed']) as $crossed_category_id) {
                            $rlCategories->listingsDecrease($crossed_category_id);
                        }

                        $sql = "UPDATE `{db_prefix}listings` SET `Crossed` = '' WHERE `ID` = '{$listing['ID']}'";
                        $rlDb->query($sql);
                    }

                    $rlNotice->saveNotice($lang['notice_listing_upgraded']);
                    Util::redirect($rlAccount::getMyListingsPageURL($listing_type, $account_info['Lang']));
                }
            }
        }
    }
}
