<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.10.1
 *  LICENSE: FL1KU6QLIUJA - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: bizlisting.xyz
 *  FILE: NEWS.INC.PHP
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

/* ext js action */
if ($_GET['q'] == 'ext') {
    /* system config */
    require_once '../../includes/config.inc.php';
    require_once RL_ADMIN_CONTROL . 'ext_header.inc.php';
    require_once RL_LIBS . 'system.lib.php';

    /* date update */
    if ($_GET['action'] == 'update') {
        $type = $rlValid->xSql($_GET['type']);
        $field = $rlValid->xSql($_GET['field']);
        $value = $rlValid->xSql(nl2br($_GET['value']));
        $id = (int) $_GET['id'];
        $key = $rlValid->xSql($_GET['key']);

        $updateData = array(
            'fields' => array(
                $field => $value,
            ),
            'where'  => array(
                'ID' => $id,
            ),
        );

        $rlHook->load('apExtNewsUpdate');

        $rlDb->updateOne($updateData, 'news');

        $rlCache->updateNewsInBox();
        exit;
    }

    /* data read */
    $limit = (int) $_GET['limit'];
    $start = (int) $_GET['start'];
    $sort = $rlValid->xSql($_GET['sort']);
    $sortDir = $rlValid->xSql($_GET['dir']);

    $sql = "SELECT DISTINCT `T1`.*, `T1`.`ID` AS `Key`, `T2`.`Value` AS `title`, `T3`.`Value` AS `Category` ";
    $sql .= "FROM `{db_prefix}news` AS `T1` ";
    $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T2` ON CONCAT('news+title+',`T1`.`ID`) = `T2`.`Key` AND `T2`.`Code` = '" . RL_LANG_CODE . "' ";
    $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T3` ON CONCAT('news_categories+name+',`T1`.`Category_ID`) = `T3`.`Key` AND `T3`.`Code` = '" . RL_LANG_CODE . "' ";
    $sql .= "WHERE `T1`.`Status` <> 'trash' ";
    if ($sort) {
        $sortField = $sort == 'title' ? "`T2`.`Value`" : "`T1`.`{$sort}`";
        $sql .= "ORDER BY {$sortField} {$sortDir} ";
    }
    $sql .= "LIMIT {$start}, {$limit}";

    $rlHook->load('apExtNewsSql');

    $data  = $rlDb->getAll($sql);
    $count = $rlDb->getTotalCount($sql);

    foreach ($data as &$item) {
        $item['Category'] = $item['Category'] ?: $GLOBALS['lang']['not_available'];
        $item['Status']   = $GLOBALS['lang'][$item['Status']];
        $item['src']      = $item['Picture'] ? RL_FILES_URL . 'news/' . $item['Picture'] : '';
    }

    $rlHook->load('apExtNewsData');

    $output['total'] = $count;
    $output['data']  = $data;

    echo json_encode($output);
}
/* ext js action end */
elseif ($_GET['q'] == 'ext_categories') {
    // System config
    require_once '../../includes/config.inc.php';
    require_once RL_ADMIN_CONTROL . 'ext_header.inc.php';
    require_once RL_LIBS . 'system.lib.php';

    if ($_GET['action'] === 'update') {
        $type  = $rlValid->xSql($_GET['type']);
        $field = $rlValid->xSql($_GET['field']);
        $value = $rlValid->xSql(nl2br($_GET['value']));
        $id    = (int) $_GET['id'];
        $key   = $rlValid->xSql($_GET['key']);

        $updateData = [
            'fields' => [$field => $value],
            'where'  => ['ID' => $id],
        ];

        $rlHook->load('apExtNewsCategoriesUpdate', $updateData);

        $rlDb->updateOne($updateData, 'news_categories');

        if ($field === 'Status') {
            $rlDb->query("UPDATE `{db_prefix}news` SET `Status` = '{$value}' WHERE `Category_ID` = {$id}");
            $rlCache->updateNewsInBox();
            $rlCache->updateNewsCategories();
        }
        exit;
    }

    // data read
    $limit   = (int) $_GET['limit'];
    $start   = (int) $_GET['start'];
    $sort    = $rlValid->xSql($_GET['sort']);
    $sortDir = $rlValid->xSql($_GET['dir']);

    $sql = "SELECT `T1`.*, `T1`.`ID` AS `Key`, `T2`.`Value` AS `Name` ";
    $sql .= "FROM `{db_prefix}news_categories` AS `T1` ";
    $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T2` ON CONCAT('news_categories+name+', `T1`.`ID`) = `T2`.`Key` AND `T2`.`Code` = '" . RL_LANG_CODE . "' ";
    $sql .= "WHERE `T1`.`Status` <> 'trash' ";
    if ($sort) {
        $sortField = $sort == 'Name' ? "`T2`.`Value`" : "`T1`.`{$sort}`";
        $sql .= "ORDER BY {$sortField} {$sortDir} ";
    }
    $sql .= "LIMIT {$start}, {$limit}";

    $rlHook->load('apExtNewsCategoriesSql', $sql);

    $newsCategories = $rlDb->getAll($sql);
    $count = $rlDb->getTotalCount($sql);

    foreach ($newsCategories as &$category) {
        $category['Status'] = $GLOBALS['lang'][$category['Status']];
    }

    $rlHook->load('apExtNewsCategoriesData', $newsCategories, $count);

    echo json_encode(['total' => $count, 'data' => $newsCategories]);
} else {
    $rlHook->load('apPhpNewsTop');

    /* additional bread crumb step */
    if ($_GET['action']) {
        if ($_GET['mode'] === 'categories') {
            $bcAStep = $_GET['action'] == 'add' ? $lang['add_category'] : $lang['edit_category'];
        } else {
            $bcAStep = $_GET['action'] == 'add' ? $lang['add_news'] : $lang['edit_news'];
        }
    } elseif ($_GET['mode'] === 'categories') {
        $bcAStep = $lang['categories'];
    }

    if ($_GET['action'] == 'add' || $_GET['action'] == 'edit') {
        /* get all languages */
        $allLangs = $GLOBALS['languages'];
        $rlSmarty->assign_by_ref('allLangs', $allLangs);

        if ($_GET['mode'] === 'categories') {
            if ($_GET['action'] == 'edit') {
                $id = (int) $_GET['id'];

                // Get news category info
                $news_category_info = $rlDb->fetch('*', array('ID' => $id), "AND `Status` <> 'trash'", 1, 'news_categories', 'row');
                $rlSmarty->assign_by_ref('news_info', $news_category_info);
            }

            if ($_GET['action'] == 'edit' && !$_POST['fromPost']) {
                $_POST['status'] = $news_category_info['Status'];
                $_POST['path']   = $news_category_info['Path'];

                // Get names
                $names = $rlDb->fetch(['Code', 'Value'], ['Key' => "news_categories+name+{$id}"], "AND `Status` <> 'trash'", null, 'lang_keys');
                foreach ($names as $nKey => $nVal) {
                    $_POST['name'][$names[$nKey]['Code']] = $names[$nKey]['Value'];
                }

                // Get titles
                $titles = $rlDb->fetch(['Code', 'Value'], ['Key' => "news_categories+title+{$id}"], "AND `Status` <> 'trash'", null, 'lang_keys');
                foreach ($titles as $nKey => $nVal) {
                    $_POST['title'][$titles[$nKey]['Code']] = $titles[$nKey]['Value'];
                }

                // Get H1 headings
                $h1 = $rlDb->fetch(['Code', 'Value'], ['Key' => "news_categories+h1+{$id}"], "AND `Status` <> 'trash'", null, 'lang_keys');
                foreach ($h1 as $nKey => $nVal) {
                    $_POST['h1_heading'][$h1[$nKey]['Code']] = $h1[$nKey]['Value'];
                }

                // Get meta descriptions
                $metaDescriptions = $rlDb->fetch(['Code', 'Value'], ['Key' => "news_categories+meta_description+{$id}"], "AND `Status` <> 'trash'", null, 'lang_keys');
                foreach ($metaDescriptions as $nKey => $nVal) {
                    $_POST['meta_description'][$metaDescriptions[$nKey]['Code']] = $metaDescriptions[$nKey]['Value'];
                }

                $rlHook->load('apPhpNewsCategoriesPost');
            }

            if (isset($_POST['submit'])) {
                $errors = array();

                // Load the utf8 lib
                loadUTF8functions('ascii', 'utf8_to_ascii', 'unicode');

                // Check names
                $names = $_POST['name'];
                foreach ($allLangs as $lkey => $lval) {
                    if (empty($names[$allLangs[$lkey]['Code']])) {
                        $errors[] = str_replace('{field}', "<b>" . $lang['name'] . "({$allLangs[$lkey]['name']})</b>", $lang['notice_field_empty']);
                        $error_fields[] = "name[{$lval['Code']}]";
                    }
                }

                // Check path
                $f_path = $_POST['path'];
                if ($config['url_transliteration'] && !utf8_is_ascii($f_path)) {
                    $f_path = utf8_to_ascii($f_path);
                }
                $f_path = $rlValid->str2path($f_path);

                if (strlen($f_path) < 3) {
                    $errors[] = $lang['incorrect_page_address'];
                    $error_fields[] = 'path';
                }

                $where = "";
                if ($_GET['action'] == 'edit' && $id) {
                    $where .= "AND `ID` <> '{$id}' ";
                }

                $exist_path = $rlDb->fetch(array('ID', 'Status'), array('Path' => $f_path), $where, null, 'news_categories', 'row');

                if ($exist_path) {
                    $exist_error = str_replace('{path}', "<b>{$f_path}</b>", $lang['notice_page_path_exist']);

                    if ($exist_path['Status'] == 'trash') {
                        $exist_error .= " <b>(" . $lang['in_trash'] . ")</b>";
                    }

                    $errors[]       = $exist_error;
                    $error_fields[] = 'path';
                }

                $titles           = $_POST['title'];
                $h1               = $_POST['h1_heading'];
                $metaDescriptions = $_POST['meta_description'];

                $rlHook->load('apPhpNewsCategoriesValidate');

                if (!empty($errors)) {
                    $rlSmarty->assign_by_ref('errors', $errors);
                } else {
                    // Add/edit action
                    if ($_GET['action'] == 'add') {
                        // Write main section information
                        $data = array(
                            'Status'  => $_POST['status'],
                            'Path'    => $f_path,
                        );

                        $rlHook->load('apPhpNewsCategoriesBeforeAdd');

                        if ($action = $rlDb->insertOne($data, 'news_categories')) {
                            $id = $rlDb->insertID();

                            $rlHook->load('apPhpNewsCategoriesAfterAdd');

                            // Save phrases of news category
                            $insertPhrases = [];
                            foreach ($allLangs as $language) {
                                $insertPhrases[] = [
                                    'Code'   => $language['Code'],
                                    'Module' => 'common',
                                    'Status' => 'active',
                                    'Key'    => "news_categories+name+{$id}",
                                    'Value'  => trim($names[$language['Code']]),
                                ];

                                if (!empty($titles[$language['Code']])) {
                                    $insertPhrases[] = [
                                        'Code'   => $language['Code'],
                                        'Module' => 'common',
                                        'Status' => 'active',
                                        'Key'    => "news_categories+title+{$id}",
                                        'Value'  => trim($titles[$language['Code']]),
                                    ];
                                }

                                if (!empty($h1[$language['Code']])) {
                                    $insertPhrases[] = [
                                        'Code'   => $language['Code'],
                                        'Module' => 'common',
                                        'Status' => 'active',
                                        'Key'    => "news_categories+h1+{$id}",
                                        'Value'  => trim($h1[$language['Code']]),
                                    ];
                                }

                                if (!empty($metaDescriptions[$language['Code']])) {
                                    $insertPhrases[] = array(
                                        'Code'   => $language['Code'],
                                        'Module' => 'common',
                                        'Status' => 'active',
                                        'Key'    => "news_categories+meta_description+{$id}",
                                        'Value'  => trim($metaDescriptions[$language['Code']]),
                                    );
                                }
                            }

                            $rlLang->createPhrases($insertPhrases);

                            $rlCache->updateNewsCategories();

                            $message = $lang['category_added'];
                            $aUrl = ['controller' => $controller, 'mode' => 'categories'];
                        } else {
                            trigger_error("Can't add new news category (MYSQL problems)", E_USER_WARNING);
                            $rlDebug->logger("Can't add new news category (MYSQL problems)");
                        }
                    } elseif ($_GET['action'] == 'edit') {
                        $update_date = array(
                            'fields' => array(
                                'Status' => $_POST['status'],
                                'Path'   => $f_path,
                            ),
                            'where'  => array('ID' => $id),
                        );

                        $rlHook->load('apPhpNewsCategoriesBeforeEdit');

                        $action = $rlDb->updateOne($update_date, 'news_categories');

                        $rlHook->load('apPhpNewsCategoriesAfterEdit');

                        $insertPhrases = [];
                        $updatePhrases = [];
                        $deletePhrases = [];
                        foreach ($allLangs as $language) {
                            // Update/create category name
                            if ($rlDb->getOne('ID', "`Key` = 'news_categories+name+{$id}' AND `Code` = '{$language['Code']}'", 'lang_keys')) {
                                $updatePhrases[] = [
                                    'where'  => [
                                        'Code' => $language['Code'],
                                        'Key'  => "news_categories+name+{$id}",
                                    ],
                                    'fields' => [
                                        'Value' => $names[$language['Code']],
                                    ],
                                ];
                            } else {
                                $insertPhrases[] = [
                                    'Code'   => $language['Code'],
                                    'Module' => 'common',
                                    'Key'    => "news_categories+name+{$id}",
                                    'Value'  => $names[$language['Code']],
                                ];
                            }

                            // Update/create category title
                            if ($rlDb->getOne('ID', "`Key` = 'news_categories+title+{$id}' AND `Code` = '{$language['Code']}'", 'lang_keys')) {
                                $updatePhrases[] = [
                                    'where'  => [
                                        'Code' => $language['Code'],
                                        'Key'  => "news_categories+title+{$id}",
                                    ],
                                    'fields' => [
                                        'Value' => $titles[$language['Code']],
                                    ],
                                ];
                            } else {
                                $insertPhrases[] = [
                                    'Code'   => $language['Code'],
                                    'Module' => 'common',
                                    'Key'    => "news_categories+title+{$id}",
                                    'Value'  => $titles[$language['Code']],
                                ];
                            }

                            // Update/create category H1
                            if ($rlDb->getOne('ID', "`Key` = 'news_categories+h1+{$id}' AND `Code` = '{$language['Code']}'", 'lang_keys')) {
                                $updatePhrases[] = [
                                    'where'  => [
                                        'Code' => $language['Code'],
                                        'Key'  => "news_categories+h1+{$id}",
                                    ],
                                    'fields' => [
                                        'Value' => $h1[$language['Code']],
                                    ],
                                ];
                            } else {
                                $insertPhrases[] = [
                                    'Code'   => $language['Code'],
                                    'Module' => 'common',
                                    'Key'    => "news_categories+h1+{$id}",
                                    'Value'  => $h1[$language['Code']],
                                ];
                            }

                            // Update/create category meta description
                            if (!empty($metaDescriptions[$language['Code']])) {
                                if ($rlDb->getOne('ID', "`Key` = 'news_categories+meta_description+{$id}' AND `Code` = '{$language['Code']}'", 'lang_keys')) {
                                    $updatePhrases[] = [
                                        'where'  => [
                                            'Code' => $language['Code'],
                                            'Key'  => "news_categories+meta_description+{$id}",
                                        ],
                                        'fields' => [
                                            'Value' => trim($metaDescriptions[$language['Code']]),
                                        ],
                                    ];
                                } else {
                                    $insertPhrases[] = [
                                        'Code'   => $language['Code'],
                                        'Module' => 'common',
                                        'Status' => 'active',
                                        'Key'    => "news_categories+meta_description+{$id}",
                                        'Value'  => trim($metaDescriptions[$language['Code']]),
                                    ];
                                }
                            } else {
                                $deletePhrases[] = ['Key' => "news_categories+meta_description+{$id}", 'Code' => $language['Code']];

                            }
                        }

                        $rlLang->createPhrases($insertPhrases);
                        $rlLang->updatePhrases($updatePhrases);
                        $rlLang->deletePhrases($deletePhrases);

                        $rlCache->updateNewsCategories();

                        $message = $lang['category_edited'];
                        $aUrl = ['controller' => $controller, 'mode' => 'categories'];
                    }

                    if ($action) {
                        $reefless->loadClass('Notice');
                        $rlNotice->saveNotice($message);
                        $reefless->redirect($aUrl);
                    }
                }
            }
        } else {
            $sql = "SELECT `T1`.*, `T1`.`ID` AS `Key`, `T2`.`Value` AS `Name` ";
            $sql .= "FROM `{db_prefix}news_categories` AS `T1` ";
            $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T2` ON CONCAT('news_categories+name+', `T1`.`ID`) = `T2`.`Key` AND `T2`.`Code` = '" . RL_LANG_CODE . "' ";
            $sql .= "WHERE `T1`.`Status` = 'active' ";
            $newsCategories = $rlDb->getAll($sql);
            $rlSmarty->assign_by_ref('news_categories', $newsCategories);

            if ($_GET['action'] == 'edit') {
                $id = (int) $_GET['news'];

                // Get news info
                $news_info = $rlDb->fetch('*', array('ID' => $id), "AND `Status` <> 'trash'", 1, 'news', 'row');
                $rlSmarty->assign_by_ref('news_info', $news_info);
            }

            if ($_GET['action'] == 'edit' && !$_POST['fromPost']) {
                $_POST['category'] = $news_info['Category_ID'];
                $_POST['status']   = $news_info['Status'];
                $_POST['path']     = $news_info['Path'];
                $_POST['date']     = $news_info['Date'];

                // get titles
                $e_titles = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'news+title+' . $id), "AND `Status` <> 'trash'", null, 'lang_keys');
                foreach ($e_titles as $nKey => $nVal) {
                    $_POST['name'][$e_titles[$nKey]['Code']] = $e_titles[$nKey]['Value'];
                }

                // get meta description
                $meta_description = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'news+meta_description+' . $id), "AND `Status` <> 'trash'", null, 'lang_keys');
                foreach ($meta_description as $tKey => $tVal) {
                    $_POST['meta_description'][$meta_description[$tKey]['Code']] = $meta_description[$tKey]['Value'];
                }

                // get content
                $e_content = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'news+content+' . $id), "AND `Status` <> 'trash'", null, 'lang_keys');
                foreach ($e_content as $nKey => $nVal) {
                    $_POST['content_' . $e_content[$nKey]['Code']] = $e_content[$nKey]['Value'];
                }

                $rlHook->load('apPhpNewsPost');
            }

            if (isset($_POST['submit'])) {
                $errors = array();

                /* load the utf8 lib */
                loadUTF8functions('ascii', 'utf8_to_ascii', 'unicode');

                /* check titles */
                $f_title = $_POST['name'];

                foreach ($allLangs as $lkey => $lval) {
                    if (empty($f_title[$allLangs[$lkey]['Code']])) {
                        $errors[] = str_replace('{field}', "<b>" . $lang['title'] . "({$allLangs[$lkey]['name']})</b>", $lang['notice_field_empty']);
                        $error_fields[] = "name[{$lval['Code']}]";
                    }
                }

                /* check content */
                foreach ($allLangs as $lkey => $lval) {
                    if (empty($_POST['content_' . $allLangs[$lkey]['Code']])) {
                        $errors[] = str_replace('{field}', "<b>" . $lang['content'] . "({$allLangs[$lkey]['name']})</b>", $lang['notice_field_empty']);
                    } else {
                        $f_content[$allLangs[$lkey]['Code']] = $_POST['content_' . $allLangs[$lkey]['Code']];
                    }
                }

                /* check path */
                $f_path = $_POST['path'];

                if ($config['url_transliteration'] && !utf8_is_ascii($f_path)) {
                    $f_path = utf8_to_ascii($f_path);
                }
                $f_path = $rlValid->str2path($f_path);

                if (strlen($f_path) < 3) {
                    $errors[] = $lang['incorrect_page_address'];
                    $error_fields[] = 'path';
                }

                $where = "";

                if ($_GET['action'] == 'edit' && $id) {
                    $where .= "AND `ID` <> '{$id}' ";
                }

                $exist_path = $rlDb->fetch(array('ID', 'Status'), array('Path' => $f_path), $where, null, 'news', 'row');

                if ($exist_path) {
                    $exist_error = str_replace('{path}', "<b>{$f_path}</b>", $lang['notice_page_path_exist']);

                    if ($exist_path['Status'] == 'trash') {
                        $exist_error .= " <b>(" . $lang['in_trash'] . ")</b>";
                    }

                    $errors[]       = $exist_error;
                    $error_fields[] = 'path';
                }

                if (!$errors && $_FILES['picture']['name']) {
                    $reefless->loadClass('Actions');

                    $allowed_ext = array('jpg', 'jpeg', 'png', 'webp');
                    $file_ext    = strtolower(pathinfo($_FILES['picture']['name'], PATHINFO_EXTENSION));

                    if (!in_array($file_ext, $allowed_ext)) {
                        $errors[] = str_replace(
                            array('{ext}', '{types}'),
                            array($file_ext, implode(', ', $allowed_ext)),
                            $lang['error_wrong_file_type']
                        );
                    } elseif (!$_FILES['picture']['size']) {
                        $errors[] = $lang['error_maxFileSize'];
                    } elseif ($picture = $rlActions->upload('picture', mt_rand(), 'C', [$config['news_image_width'], $config['news_image_height']])) {
                        rename(
                            RL_FILES . $picture,
                            RL_FILES . 'news' . RL_DS . $picture
                        );

                        // Remove previous picture
                        if ($news_info['Picture']) {
                            unlink(RL_FILES . 'news' . RL_DS . $news_info['Picture']);
                        }
                    } else {
                        $errors[] = $lang['not_image_file'];
                        $error_fields[] = 'picture';
                    }
                }

                $rlHook->load('apPhpNewsValidate');

                if (!empty($errors)) {
                    $rlSmarty->assign_by_ref('errors', $errors);
                } else {
                    /* add/edit action */
                    if ($_GET['action'] == 'add') {
                        // write main section information
                        $data = array(
                            'Category_ID' => $_POST['category'],
                            'Status'      => $_POST['status'],
                            'Path'        => $f_path,
                            'Date'        => 'NOW()',
                            'Picture'     => $picture,
                        );

                        $rlHook->load('apPhpNewsBeforeAdd');

                        if ($action = $rlDb->insertOne($data, 'news')) {
                            $news_id = $rlDb->insertID();

                            $rlCache->updateNewsInBox();

                            $rlHook->load('apPhpNewsAfterAdd');

                            // Save news content
                            foreach ($allLangs as $key => $value) {
                                $lang_keys[] = array(
                                    'Code'   => $allLangs[$key]['Code'],
                                    'Module' => 'common',
                                    'Status' => 'active',
                                    'Key'    => 'news+title+' . $news_id,
                                    'Value'  => $f_title[$allLangs[$key]['Code']],
                                );

                                $lang_keys[] = array(
                                    'Code'   => $allLangs[$key]['Code'],
                                    'Module' => 'common',
                                    'Status' => 'active',
                                    'Key'    => 'news+content+' . $news_id,
                                    'Value'  => $f_content[$allLangs[$key]['Code']],
                                );

                                // Save meta description
                                $lang_keys[] = array(
                                    'Code'   => $allLangs[$key]['Code'],
                                    'Module' => 'common',
                                    'Status' => 'active',
                                    'Key'    => 'news+meta_description+' . $news_id,
                                    'Value'  => $_POST['meta_description'][$allLangs[$key]['Code']],
                                );
                            }

                            $rlLang->createPhrases($lang_keys);

                            $message = $lang['news_added'];
                            $aUrl = array("controller" => $controller);
                        } else {
                            trigger_error("Can't add new news article (MYSQL problems)", E_USER_WARNING);
                            $rlDebug->logger("Can't add new news article (MYSQL problems)");
                        }
                    } elseif ($_GET['action'] == 'edit') {
                        $update_date = array(
                            'fields' => array(
                                'Category_ID' => $_POST['category'],
                                'Status'      => $_POST['status'],
                                'Date'        => $_POST['date'],
                                'Path'        => $f_path,
                            ),
                            'where' => array('ID' => $id),
                        );

                        if ($picture) {
                            $update_date['fields']['Picture'] = $picture;
                        }

                        $rlHook->load('apPhpNewsBeforeEdit');

                        $action = $rlDb->updateOne($update_date, 'news');

                        $rlCache->updateNewsInBox();

                        $rlHook->load('apPhpNewsAfterEdit');

                        $insertPhrases = [];
                        $updatePhrases = [];
                        foreach ($allLangs as $key => $value) {
                            // Edit titles
                            if ($rlDb->getOne('ID', "`Key` = 'news+title+{$id}' AND `Code` = '{$allLangs[$key]['Code']}'", 'lang_keys')) {
                                $updatePhrases[] = array(
                                    'where'  => array(
                                        'Code' => $allLangs[$key]['Code'],
                                        'Key'  => 'news+title+' . $id,
                                    ),
                                    'fields' => array(
                                        'Value' => $f_title[$allLangs[$key]['Code']],
                                    ),
                                );
                            } else {
                                $insertPhrases[] = array(
                                    'Code'   => $allLangs[$key]['Code'],
                                    'Module' => 'common',
                                    'Key'    => 'news+title+' . $id,
                                    'Value'  => $f_title[$allLangs[$key]['Code']],
                                );
                            }

                            // Edit content
                            if ($rlDb->getOne('ID', "`Key` = 'news+content+{$id}' AND `Code` = '{$allLangs[$key]['Code']}'", 'lang_keys')) {
                                $updatePhrases[] = array(
                                    'where'  => array(
                                        'Code' => $allLangs[$key]['Code'],
                                        'Key'  => 'news+content+' . $id,
                                    ),
                                    'fields' => array(
                                        'Value' => $f_content[$allLangs[$key]['Code']],
                                    ),
                                );
                            } else {
                                $insertPhrases[] = array(
                                    'Code'   => $allLangs[$key]['Code'],
                                    'Module' => 'common',
                                    'Key'    => 'news+content+' . $id,
                                    'Value'  => $f_content[$allLangs[$key]['Code']],
                                );
                            }

                            // Edit meta description
                            if ($rlDb->getOne('ID', "`Key` = 'news+meta_description+{$id}' AND `Code` = '{$allLangs[$key]['Code']}'", 'lang_keys')) {
                                $updatePhrases[] = [
                                    'fields' => [
                                        'Value' => $_POST['meta_description'][$allLangs[$key]['Code']],
                                    ],
                                    'where' => [
                                        'Code' => $allLangs[$key]['Code'],
                                        'Key'  => "news+meta_description+{$id}",
                                    ],
                                ];
                            } else {
                                $insertPhrases[] = [
                                    'Value' => $_POST['meta_description'][$allLangs[$key]['Code']],
                                    'Code'  => $allLangs[$key]['Code'],
                                    'Key'   => "news+meta_description+{$id}",
                                ];
                            }
                        }

                        $rlLang->createPhrases($insertPhrases);
                        $rlLang->updatePhrases($updatePhrases);

                        $message = $lang['news_edited'];
                        $aUrl = array("controller" => $controller);
                    }

                    if ($action) {
                        $reefless->loadClass('Notice');
                        $rlNotice->saveNotice($message);
                        $reefless->redirect($aUrl);
                    }
                }
            }
        }
    }

    $rlHook->load('apPhpNewsBottom');
}
