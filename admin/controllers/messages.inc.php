<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.10.1
 *  LICENSE: FL1KU6QLIUJA - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: bizlisting.xyz
 *  FILE: MESSAGES.INC.PHP
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

$reefless->loadClass('Message');
$reefless->loadClass('Account');

$id = (int) $_GET['id'];

$account_info = array(
    'ID'        => $_SESSION['sessAdmin']['user_id'],
    'Mail'      => $_SESSION['sessAdmin']['mail'],
    'Full_name' => $_SESSION['sessAdmin']['name'] ? $_SESSION['sessAdmin']['name'] : 'Administrator',
);
$rlSmarty->assign_by_ref('account_info', $account_info);

if ($id) {
    $contact = $rlAccount->getProfile((int) $id);
    $contact['Admin'] = 1;
    $rlSmarty->assign_by_ref('contact', $contact);

    /* get contact messages */
    $messages = $rlMessage->getMessages($id);

    if (empty($messages)) {
        $sError = true;
    } else {
        $rlSmarty->assign_by_ref('messages', $messages);
        $bcAStep[] = ['name' => str_replace('{name}', $contact['Full_name'], $lang['chat_with'])];
    }

    /* check new messages one more time */
    $message_info = $rlCommon->checkMessages();
    if (!empty($message_info)) {
        $rlSmarty->assign_by_ref('new_messages', $message_info);
    }

    $rlHook->load('messagesBottom');

    /* register ajax methods */
    $rlXajax->registerFunction(array('sendMessage', $rlMessage, 'ajaxSendMessage'));
    $rlXajax->registerFunction(array('refreshMessagesArea', $rlMessage, 'ajaxRefreshMessagesArea'));
    $rlXajax->registerFunction(array('removeMsg', $rlMessage, 'ajaxRemoveMsg'));
} else {
    $contacts = $rlMessage->getContacts();
    $rlSmarty->assign_by_ref('contacts', $contacts);

    $rlXajax->registerFunction(array('removeContacts', $rlMessage, 'ajaxRemoveContacts'));
}

$rlHook->load('apPhpMessagesBottom');
