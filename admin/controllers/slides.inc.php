<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.10.1
 *  LICENSE: FL1KU6QLIUJA - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: bizlisting.xyz
 *  FILE: SLIDES.INC.PHP
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

// Ext js action
if ($_GET['q'] == 'ext') {
    /* system config */
    require_once '../../includes/config.inc.php';
    require_once RL_ADMIN_CONTROL . 'ext_header.inc.php';

    // Cell data update
    if ($_GET['action'] == 'update') {
        $field = Valid::escape($_GET['field']);
        $value = Valid::escape($_GET['value']);
        $id    = (int) $_GET['id'];

        if ($field == 'Title') {
            $update = array(
                'fields' => array(
                    'Value' => $value,
                ),
                'where'  => array(
                    'Key'  => 'slides+title+' . $id,
                    'Code' => RL_LANG_CODE
                ),
            );

            $table = 'lang_keys';
        } else {
            $update = array(
                'fields' => array(
                    $field => $value,
                ),
                'where'  => array(
                    'ID' => $id,
                ),
            );

            $table = 'slides';
        }

        $rlHook->load('apExtSlidesUpdate', $update, $table);

        if ($table === 'lang_keys') {
            $rlLang->updatePhrase($update);
        } else {
            $rlDb->updateOne($update, $table);
        }
        exit;
    }

    $limit   = (int) $_GET['limit'];
    $start   = (int) $_GET['start'];
    $sort    = Valid::escape($_GET['sort']);
    $sortDir = Valid::escape($_GET['dir']);

    $sql = "
        SELECT DISTINCT `T1`.*, `T2`.`Value` AS `Title`
        FROM `{db_prefix}slides` AS `T1`
        LEFT JOIN `{db_prefix}lang_keys` AS `T2`
            ON CONCAT('slides+title+', `T1`.`ID`) = `T2`.`Key`
            AND `T2`.`Code` = '" . RL_LANG_CODE . "'
    ";

    $rlHook->load('apExtSlidesModifyWhere', $sql);

    if ($sort) {
        $sortField = $sort == 'title' ? "`T2`.`Value`" : "`T1`.`{$sort}`";
        $sql .= "ORDER BY {$sortField} {$sortDir} ";
    }
    $sql .= "LIMIT {$start}, {$limit}";

    $rlHook->load('apExtSlidesSql', $sql);

    $data  = $rlDb->getAll($sql);
    $count = $rlDb->getTotalCount($sql);

    foreach ($data as &$item) {
        $item['Status'] = $lang[$item['Status']];
        $item['src']    = RL_FILES_URL . 'slides/' . $item['Picture'];
    }

    $rlHook->load('apExtSlidesData', $data);

    echo json_encode(['total' => $count, 'data' => $data]);
    exit;
}

// Default controller script
if (in_array($_GET['action'], array('add', 'edit'))) {
    $bcAStep = $lang[$_GET['action']];

    $allLangs = $GLOBALS['languages'];
    $rlSmarty->assign_by_ref('allLangs', $allLangs);

    if ($_GET['action'] == 'edit') {
        $item_id = (int) $_GET['slide'];

        // Get item info
        $item_info = $rlDb->fetch('*', array('ID' => $item_id), null, null, 'slides', 'row');
        $rlSmarty->assign_by_ref('item_info', $item_info);

        if (!$_POST['fromPost']) {
            $_POST['status'] = $item_info['Status'];
            $_POST['url'] = $item_info['URL'];

            $rlDb->setTable('lang_keys');

            // Get titles
            $titles = $rlDb->fetch(
                array('Code', 'Value'),
                array('Key' => 'slides+title+' . $item_id)
            );
            foreach ($titles as $title) {
                $_POST['title'][$title['Code']] = $title['Value'];
            }

            // Get description
            $descriptions = $rlDb->fetch(
                array('Code', 'Value'),
                array('Key' => 'slides+description+' . $item_id)
            );
            foreach ($descriptions as $desc) {
                $_POST['description'][$desc['Code']] = $desc['Value'];
            }
        }
    }

    $rlHook->load('apPhpSlidesPost');

    if (isset($_POST['submit'])) {
        $picture = '';
        $errors  = array();

        // Check picture
        if (!$_FILES['picture']['name'] && $_GET['action'] == 'add') {
            $errors[] = $lang['no_photos_uploaded'];
            $error_fields[] = 'picture';
        }

        // Check title
        foreach ($allLangs as $lng) {
            if (empty($_POST['title'][$lng['Code']])) {
                $errors[] = str_replace(
                    '{field}',
                    "<b>" . $lang['title'] . "({$lng['name']})</b>",
                    $lang['notice_field_empty']
                );
                $error_fields[] = "title[{$lng['Code']}]";
            }

            $titles[$lng['Code']] = $_POST['title'][$lng['Code']];
        }

        // Check URL
        if ($_POST['url'] && !Valid::isURL($_POST['url'])) {
            $errors[] = str_replace(
                '{field}',
                "<b>{$lang['url']}</b>",
                $lang['notice_field_not_valid']
            );
            $error_fields[] = 'url';
        }

        if (!$errors && $_FILES['picture']['name']) {
            $reefless->loadClass('Actions');

            $allowed_ext = array('jpg', 'jpeg', 'png', 'webp');
            $file_ext    = pathinfo($_FILES['picture']['name'], PATHINFO_EXTENSION);

            if (!in_array($file_ext, $allowed_ext)) {
                $errors[] = str_replace(
                    array('{ext}', '{types}'),
                    array($file_ext, implode(', ', $allowed_ext)),
                    $lang['error_wrong_file_type']
                );
            } elseif (!$_FILES['picture']['size']) {
                $errors[] = $lang['error_maxFileSize'];
            } elseif ($picture = $rlActions->upload('picture', mt_rand())) {
                rename(
                    RL_FILES . $picture,
                    RL_FILES . 'slides' . RL_DS . $picture
                );

                // Remove previous picture
                if ($item_info['Picture']) {
                    unlink(RL_FILES . 'slides' . RL_DS . $item_info['Picture']);
                }
            } else {
                $errors[] = $lang['not_image_file'];
                $error_fields[] = 'picture';
            }
        }

        $rlHook->load('apPhpSlidesValidate');

        if (!$errors) {
            $fields = array(
                'Picture'  => $picture,
                'URL'      => $_POST['url'],
                'Status'   => $_POST['status']
            );

            if ($_GET['action'] == 'add') {
                // Get max position
                $fields['position'] = $rlDb->getRow(
                    "SELECT MAX(`Position`) AS `Max` FROM `{db_prefix}slides`",
                    'Max'
                ) + 1;

                $rlHook->load('apPhpSlidesBeforeAdd', $fields);

                if ($action = $rlDb->insertOne($fields, 'slides')) {
                    $item_id = $rlDb->insertID();

                    $rlHook->load('apPhpSlidesAfterAdd', $fields, $item_id);

                    // Save titles
                    $insertPhrases = [];
                    foreach ($allLangs as $lng) {
                        $insertPhrases[] = array(
                            'Code'   => $lng['Code'],
                            'Module' => 'common',
                            'Status' => 'active',
                            'Key'    => 'slides+title+' . $item_id,
                            'Value'  => $titles[$lng['Code']],
                        );

                        // Save description if specified
                        if ($_POST['description'][$lng['Code']]) {
                            $insertPhrases[] = array(
                                'Code'   => $lng['Code'],
                                'Module' => 'common',
                                'Status' => 'active',
                                'Key'    => 'slides+description+' . $item_id,
                                'Value'  => $_POST['description'][$lng['Code']],
                            );
                        }
                    }

                    $rlLang->createPhrases($insertPhrases);

                    $message = $lang['item_added'];
                    $aUrl = array('controller' => $controller);
                } else {
                    trigger_error('Unable to add new slide, db->insert() failed', E_USER_ERROR);
                }
            } elseif ($_GET['action'] == 'edit') {
                if (!$picture) {
                    unset($fields['Picture']);
                }

                $data = array(
                    'fields' => $fields,
                    'where'  => array('ID' => $item_id),
                );

                $rlHook->load('apPhpSlidesBeforeEdit', $data);

                // Update slide
                if ($action = $rlDb->update($data, 'slides')) {
                    $rlHook->load('apPhpSlidesAfterEdit', $data);

                    $insertPhrases = [];
                    $updatePhrases = [];
                    foreach ($allLangs as $lng) {
                        // Update title
                        if ($rlDb->getOne(
                            'ID',
                            "`Key` = 'slides+title+{$item_id}' AND `Code` = '{$lng['Code']}'",
                            'lang_keys'
                        )) {
                            $updatePhrases[] = array(
                                'fields' => array(
                                    'Value' => $titles[$lng['Code']],
                                ),
                                'where'  => array(
                                    'Code' => $lng['Code'],
                                    'Key'  => 'slides+title+' . $item_id,
                                ),
                            );
                        }
                        // Insert title
                        else {
                            $insertPhrases[] = array(
                                'Code'   => $lng['Code'],
                                'Module' => 'common',
                                'Key'    => 'slides+title+' . $item_id,
                                'Value'  => $titles[$lng['Code']],
                            );
                        }

                        // Update description
                        if ($rlDb->getOne(
                            'ID',
                            "`Key` = 'slides+description+{$item_id}' AND `Code` = '{$lng['Code']}'",
                            'lang_keys'
                        )) {
                            $updatePhrases[] = array(
                                'fields' => array(
                                    'Value' => $_POST['description'][$lng['Code']],
                                ),
                                'where'  => array(
                                    'Code' => $lng['Code'],
                                    'Key'  => 'slides+description+' . $item_id,
                                ),
                            );
                        }
                        // Insert description
                        else {
                            $insertPhrases[] = array(
                                'Code'   => $lng['Code'],
                                'Module' => 'common',
                                'Key'    => 'slides+description+' . $item_id,
                                'Value'  => $_POST['description'][$lng['Code']],
                            );
                        }
                    }

                    $rlLang->createPhrases($insertPhrases);
                    $rlLang->updatePhrases($updatePhrases);
                }

                $message = $lang['item_edited'];
                $aUrl = array('controller' => $controller);
            }

            if ($action) {
                $reefless->loadClass('Notice');
                $rlNotice->saveNotice($message);
                $reefless->redirect($aUrl);
            }
        }
    }
}
