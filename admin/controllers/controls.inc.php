<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.10.1
 *  LICENSE: FL1KU6QLIUJA - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: bizlisting.xyz
 *  FILE: CONTROLS.INC.PHP
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

$allLangs = &$GLOBALS['languages'];
$rlSmarty->assign_by_ref('allLangs', $allLangs);

$reefless->loadClass('Controls', 'admin');

/* register ajax methods */
$rlXajax->registerFunction(array('recountListings', $rlControls, 'ajaxRecountListings'));
$rlXajax->registerFunction(array('rebuildCatLevels', $rlControls, 'ajaxRebuildCatLevels'));
$rlXajax->registerFunction(array('reorderFields', $rlControls, 'ajaxReorderFields'));
$rlXajax->registerFunction(array('recountListingsMP', $rlControls, 'ajaxRecountListingsMP'));

$rlHook->load('apPhpControlsBottom');
