<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.10.1
 *  LICENSE: FL1KU6QLIUJA - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: bizlisting.xyz
 *  FILE: SETTINGS.TPL.PHP
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

/* template settings */
$tpl_settings = array(
    'type' => 'responsive_42', // DO NOT CHANGE THIS SETTING
    'version' => 2.0,
    'name' => 'general_flatty',
    'inventory_menu' => false,
    'right_block' => false,
    'long_top_block' => true,
    'featured_price_tag' => true,
    'ffb_list' => false, //field bound boxes plugins list
    'fbb_custom_tpl' => true,
    'header_banner' => true,
    'header_banner_size_hint' => '728x90',
    'home_page_gallery' => false,
    'search_on_map_page' => true,
    'category_alphabet_box' => true,
    'browse_add_listing_icon' => true,
    'listing_details_nav_mode' => 'h1_mixed',
    'listing_details_simple_gallary' => true,
    'listing_grid_except_fields' => array('title', 'bedrooms', 'bathrooms', 'square_feet', 'time_frame', 'phone', 'pay_period'),
    'sidebar_sticky_pages' => array('listing_details'),
    'sidebar_restricted_pages' => array(),
    'svg_icon_fill' => true,
    'default_listing_grid_mode' => 'list',
    'listing_grid_mode_only' => false,
    'bootstrap_grid_no_xl' => true,
    'qtip' => array(
        'background' => '4b4b4b',
        'b_color'    => '4b4b4b',
    ),
    'font_family' => [
        'local' => true,
        'name' => 'Alegreya Sans',
        'format' => 'woff2',
        'weights' => [
            '300' => 'AlegreyaSans-Light',
            '400' => 'AlegreyaSans-Regular',
            '500' => 'AlegreyaSans-Medium',
        ]
    ]
);

if ( is_object($rlSmarty) ) {
    $rlSmarty -> assign_by_ref('tpl_settings', $tpl_settings);
}
