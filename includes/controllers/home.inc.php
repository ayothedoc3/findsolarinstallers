<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.10.1
 *  LICENSE: FL1KU6QLIUJA - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: bizlisting.xyz
 *  FILE: HOME.INC.PHP
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

use Flynax\Classes\Agencies;
use Flynax\Utils\Category;

$reefless->loadClass('Search');
$rlSearch->getHomePageSearchForm();

if ($tpl_settings['home_page_slides']) {
    $home_slides = $rlDb->fetch(
        array('Picture`, `ID` AS `Key', 'URL'),
        array('Status' => 'active'),
        "ORDER BY `Position`",
        null,
        'slides'
    );
    $home_slides = $rlLang->replaceLangKeys($home_slides, 'slides', array('title', 'description'));
    $rlSmarty->assign_by_ref('home_slides', $home_slides);

    if ($home_slides) {
        $rlStatic->addHeaderCss(RL_TPL_BASE . 'components/content-slider/carousel.css');
    }
}

if (!$config['home_page_h1']) {
    $rlSmarty->assign('no_h1', true);
}

/* enable rss */
$rss = array('title' => $page_info['title']);
$rlSmarty->assign_by_ref('rss', $rss);

if ($_GET['agent-invite']) {
    $agencies = new Agencies();

    $agentInviteInfo = $agencies->setInviteKey($_GET['agent-invite'])->getInviteInfo();
    $rlSmarty->assign('agentInviteInfo', $agentInviteInfo);

    $agencyInfo = $rlAccount->getProfile((int) $agentInviteInfo['Agency_ID']);
    $rlSmarty->assign('agencyInfo', $agencyInfo);

    $agencyTitle = $agencyInfo['Personal_address']
        ? "<a target=\"_blank\" href=\"{$agencyInfo['Personal_address']}\">{$agencyInfo['Full_name']}</a>"
        : $agencyInfo['Full_name'];

    $lang['confirmation_invite_notice'] = str_replace('{agency}', $agencyTitle, $lang['confirmation_invite_notice']);
}

if ($tpl_settings['category_menu']) {
    $category_menu = Category::getCategoryIconMenu();
    $rlSmarty->assign_by_ref('category_menu', $category_menu);
}

$rlHook->load('homeBottom');
