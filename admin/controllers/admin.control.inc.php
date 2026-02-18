<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.10.1
 *  LICENSE: FL1KU6QLIUJA - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: bizlisting.xyz
 *  FILE: ADMIN.CONTROL.INC.PHP
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

// Include PSR-4 autoloader
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

/* define interface */
define('REALM', 'admin');

/* send headers */
header("Content-Type: text/html; charset=utf-8");
header("Cache-Control: store, no-cache, max-age=3600, must-revalidate");

require_once RL_CLASSES . 'rlDb.class.php';
require_once RL_CLASSES . 'reefless.class.php';

$rlDb = new rlDb();
$reefless = new reefless();

$reefless->sessionStart();

/* load classes */
$reefless->connect(RL_DBHOST, RL_DBPORT, RL_DBUSER, RL_DBPASS, RL_DBNAME);
$reefless->loadClass('Debug');
$reefless->loadClass('Config');
$reefless->loadClass('Lang');
$reefless->loadClass('Valid');
$reefless->loadClass('Notice');
$reefless->loadClass('Admin', 'admin');
$reefless->loadClass('Actions');
$reefless->loadClass('Hook');
$reefless->loadClass('Custom');
$reefless->loadClass('Common');

/* ajax library load */
require_once RL_LIBS . 'ajax' . RL_DS . 'xajax_core' . RL_DS . 'xajax.inc.php';

$rlXajax = new xajax();
$_response = new xajaxResponse();
$GLOBALS['_response'] = $_response;

$rlXajax->configure('javascript URI', RL_URL_HOME . 'libs/ajax/');
$rlXajax->configure('debug', RL_AJAX_DEBUG);

$rlXajax->setCharEncoding('UTF-8');

$reefless->loadClass('AjaxAdmin', 'admin');
$rlXajax->registerFunction(array('logIn', $rlAjaxAdmin, 'ajaxLogIn'));
$rlXajax->registerFunction(array('logOut', $rlAjaxAdmin, 'ajaxLogOut'));
$rlXajax->registerFunction(array('removeTmpFile', $reefless, 'ajaxRemoveTmpFile'));
/* ajax library end */

/* smarty library load */
require_once RL_LIBS . 'smarty' . RL_DS . 'Smarty.class.php';
$reefless->loadClass('Smarty');
/* smarty library load end */

/**
 * Load active plugins
 * @since 4.5.1
 **/
$plugins = $rlCommon->getInstalledPluginsList();
$GLOBALS['plugins'] = &$plugins;
$rlSmarty->assign('plugins', $plugins);

/**
 * @since 4.9.1
 */
$rlSmarty->assign_by_ref('domain_info', $domain_info);

/* register functions */
$rlSmarty->register_function('fckEditor', [$rlSmarty, 'fckEditor']);
$rlSmarty->registerFunctions();

$reefless->validatePOST();

/* utf8 library functions */
function loadUTF8functions()
{
    $names = func_get_args();

    if (empty($names)) {
        return false;
    }

    foreach ($names as $name) {
        if (file_exists(RL_LIBS . 'utf8' . RL_DS . 'utils' . RL_DS . $name . '.php')) {
            require_once RL_LIBS . 'utf8' . RL_DS . 'utils' . RL_DS . $name . '.php';
        }
    }
}
