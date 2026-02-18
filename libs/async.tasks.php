<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.10.1
 *  LICENSE: FL1KU6QLIUJA - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: bizlisting.xyz
 *  FILE: ASYNC.TASKS.PHP
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

use Flynax\Utils\AsyncTasks;
use Flynax\Utils\Util;

const ASYNC_TASKS = true;

require dirname(__DIR__) . '/includes/config.inc.php';
require RL_INC . 'control.inc.php';

try {
    define('RL_LANG_CODE', $config['lang']);
    $languages = $rlLang->getLanguagesList();
    $lang = $rlLang->getLangBySide('frontEnd', $config['lang']);

    require RL_LIBS . 'system.lib.php';

    $reefless->setTimeZone();
    $reefless->setLocalization();

    $pages = Util::getPages(['Key', 'Path'], ['Status' => 'active'], null, ['Key', 'Path']);

    // Detects request via Bash command
    if ($_SERVER['argv'] && $_SERVER['argv']['0'] === AsyncTasks::SCRIPT_PATH && $taskID = (int) $_SERVER['argv']['1']) {
        AsyncTasks::execute($taskID);
    }
    // Detects HTTP request
    elseif ($_REQUEST && $taskID = (int) $_REQUEST['id']) {
        AsyncTasks::execute($taskID);
    }
} catch (\Exception $e) {
    $rlDebug->logger('Error in async tasks script: ' . $e->getMessage());
} finally {
    $rlDb->connectionClose();
}
