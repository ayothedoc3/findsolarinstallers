<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.10.1
 *  LICENSE: FL1KU6QLIUJA - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: bizlisting.xyz
 *  FILE: RLACTIONS.CLASS.PHP
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

class rlActions
{
    /**
     * @var language class object
     **/
    public $rlLang;

    /**
     * @var validator class object
     **/
    public $rlValid;

    /**
     * @var configuration class object
     **/
    public $rlConfig;

    /**
     * @var photoSaveOriginal
     **/
    public $photoSaveOriginal = false;

    /**
     * @var photoOriginal
     **/
    public $photoOriginal = false;

    /**
     * @var string
     */
    public  $action;

    /**
     * @var bool - Allow html tags
     */
    public $rlAllowHTML = false;

    /**
     * class constructor
     **/
    public function __construct()
    {
        global $rlLang, $rlValid, $rlConfig;

        $this->rlLang  = &$rlLang;
        $this->rlValid = &$rlValid;
        $this->rlConfig = &$rlConfig;
    }

    /**
     * delete item
     *
     * @param array $fields - data array: array( 'field' => 'value' )
     * @param array $table - table name
     * @param string $options - aditional deleting options
     * @param string $limit - rows number | DEPRECATED
     * @param string $key - main item key or ID
     * @param array $lang_keys - languages phrase's keys
     * @param string $className - class name to search methods for
     * @param string $deleteMethod - delete method name
     * @param string $restoreMethod - restore method name
     * @param string $plugin - plugin key
     *
     * @return bool
     **/
    public function delete($fields = false, $table = false, $options = false, $limit = 1/*deprecated*/, $key = false, $lang_keys = false, $className = false, $deleteMethod = false, $restoreMethod = false, $plugin = false)
    {
        if (!is_array($table)) {
            $table = array($table);
        }

        if ($GLOBALS['config']['trash']) {
            $this->trash($fields, $table, $key, $lang_keys, $className, $deleteMethod, $restoreMethod, $plugin);
            $this->action = 'dropped';
        } else {
            $this->remove($fields, $table, $key, $options, $lang_keys, $className, $deleteMethod, $restoreMethod, $plugin);
            $this->action = 'deleted';
        }
    }

    /**
     * remove/delete rows from DB
     *
     * @param array $fields - data array: array( 'field' => 'value' )
     * @param array $table - table name
     * @param string $key - main item key or ID
     * @param string $options - additional deleting options
     * @param array $lang_keys - languages phrase's keys
     * @param string $className - class name to search methods for
     * @param string $deleteMethod - delete method name
     * @param string $restoreMethod - restore method name
     * @param string $plugin - plugin key
     *
     * @return bool
     **/
    public function remove($fields = false, $table = false, $key = false, $options = false, $lang_keys = false, $className = false, $deleteMethod = false, $restoreMethod = false, $plugin = '')
    {
        global $rlDb, $reefless, $rlCache;

        if (!is_array($fields)) {
            $msg = "remove method can not be run, incorrect structure of \$data parameter";
            trigger_error($msg, E_USER_WARNING);
            $GLOBALS['rlDebug']->logger($msg);

            return false;
        }

        if (empty($table)) {
            $msg = "remove method can not be run, database table does not chose";
            trigger_error($msg, E_USER_WARNING);
            $GLOBALS['rlDebug']->logger($msg);

            return false;
        }

        foreach ($table as $tbl) {
            $sql = "DELETE FROM `" . RL_DBPREFIX . $tbl . "` WHERE ";

            foreach ($fields as $field => $value) {
                if ($table[0] == 'accounts' && $tbl != 'accounts' && $field == 'ID') {
                    $field = "Account_ID";
                }

                $criterion .= "`{$field}` = '{$value}' AND ";
                $sql .= "`{$field}` = '{$value}' AND ";
            }

            if ($options != null && !empty($options) && $tbl != 'lang_keys') {
                $sql .= $options;
            } else {
                $sql = substr($sql, 0, -4);
            }

            $sql .= " LIMIT 1";
            $rlDb->query($sql);
        }

        // Delete language's keys
        if (!empty($lang_keys) && is_array($lang_keys)) {
            foreach ($lang_keys as $phrase) {
                $langKeys[] = $phrase['Key'];
            }

            if (!empty($langKeys)) {
                $rlDb->query("DELETE FROM `{db_prefix}lang_keys` WHERE `Key` IN ('" . implode("','", $langKeys) . "')");
                $rlCache->updatePhrases();
            }
        }

        // call delete method
        if ($className && $deleteMethod) {
            $reefless->loadClass($className, null, $plugin);
            $className = 'rl' . $className;

            if (!method_exists($className, $deleteMethod)) {
                $GLOBALS['rlDebug']->logger("There are not such method ({$deleteMethod}) in loaded class ({$className})");
                return false;
            }

            global $$className;
            $$className->$deleteMethod($key);
        }

        return true;
    }

    /**
     * drop the item to the trash box
     *
     * @param array $fields - data array: array( 'field' => 'value' )
     * @param array $tables - system tables
     * @param string $key - main item key or ID
     * @param array $lang_keys - languages phrase's keys
     * @param string $className - class name to search methods for
     * @param string $deleteMethod - delete method name
     * @param string $restoreMethod - restore method name
     * @param string $plugin - plugin key
     *
     * @return bool
     **/
    public function trash($fields = false, $tables = false, $key = false, $lang_keys = false, $className = false, $deleteMethod = false, $restoreMethod = false, $plugin = false)
    {
        global $rlDb;

        if (!is_array($fields)) {
            $msg = "trash method can not be run, incorrect structure of \$fields parameter";
            trigger_error($msg, E_USER_WARNING);
            $GLOBALS['rlDebug']->logger($msg);
            return false;
        }

        if (empty($tables)) {
            $msg = "trash method can not be run, System zones does not chose";
            trigger_error($msg, E_USER_WARNING);
            $GLOBALS['rlDebug']->logger($msg);
            return false;
        }

        foreach ($tables as $table) {
            $sql = "UPDATE `" . RL_DBPREFIX . $table . "` SET ";
            $sql .= "`Status` = 'trash' WHERE ";
            $criterion = '';

            foreach ($fields as $field => $value) {
                $criterion .= "`{$field}` = '{$value}' AND ";

                // simulation field
                if ($tables[0] == 'accounts' && $table != 'accounts' && $field == 'ID') {
                    $field = "Account_ID";
                }

                $sql .= "`{$field}` = '{$value}' AND";
            }

            $sql = substr($sql, 0, -3);
            $rlDb->query($sql);

            $tables_set .= $table . ",";
        }
        $tables_set = substr($tables_set, 0, -1);

        // Set trash status for language's keys
        if (!empty($lang_keys)) {
            $l_update = [];
            foreach ($lang_keys as $lKey => $lVal) {
                $l_update[$lKey]['where'] = $lVal;
                $l_update[$lKey]['fields'] = array('Status' => 'trash');
            }

            if ($l_update) {
                $rlDb->update($l_update, 'lang_keys');
            }
        }

        // Insert data into trash box
        $criterion = substr($criterion, 0, -4);

        $admin = (defined('REALM') && REALM == 'admin') ? $_SESSION['sessAdmin']['user_id'] : 0;

        $qTrash = array(
            'Zones'          => $tables_set,
            'Key'            => $key,
            'Criterion'      => $criterion,
            'Class_name'     => $className,
            'Restore_method' => $restoreMethod,
            'Remove_method'  => $deleteMethod,
            'Admin_ID'       => $admin,
            'Date'           => 'NOW()',
            'Lang_keys'      => serialize($lang_keys),
            'Plugin'         => $plugin,
        );

        $rlDb->insertOne($qTrash, 'trash_box', ['Criterion']);

        return true;
    }

    /**
     * insert data in db | DEPRECATED
     *
     * @since 4.9.2 - $codeFields added
     *
     * @deprecated 4.6.0 - Use rlDb->insert()
     *
     * @param array $data   - updated criterias:
     *           array(
     *               [field] => [value],
     *               [field] => [value],
     *               ...     => ...
     *           )
     * @param string $table - table name
     * @param array  $htmlFields - fields keys which can contain HTML
     * @param array  $codeFields - keys of fields which contains mysql code
     * @return bool
     **/
    public function insertOne($data, $table = null, $htmlFields = [], $codeFields = ['Pay_date', 'Featured_date', 'Date'])
    {
        $GLOBALS['rlDb']->rlAllowHTML = $this->rlAllowHTML;
        return $GLOBALS['rlDb']->insertOne($data, $table, $htmlFields, $codeFields);
    }

    /**
     * insert data in db | DEPRECATED
     *
     * @since 4.9.2 - $codeFields added
     *
     * @deprecated 4.6.0 - Use rlDb->insert()
     *
     * @param array $data   - array format:
     *           array(
     *                   [item] => array(
     *                       [field] => [value],
     *                       [field] => [value],
     *                       ...     => ...
     *                   )
     *           )
     * @param string $table - table name
     * @param array  $htmlFields - fields keys which can contain HTML
     * @param array  $codeFields - keys of fields which contains mysql code
     * @return bool
     **/
    public function insert($data, $table, $htmlFields = [], $codeFields = ['Pay_date', 'Featured_date', 'Date'])
    {
        $GLOBALS['rlDb']->rlAllowHTML = $this->rlAllowHTML;
        return $GLOBALS['rlDb']->insert($data, $table, $htmlFields, $codeFields);
    }

    /**
     * update database information | DEPRECATED
     *
     * @since 4.9.2 - $codeFields added
     *
     * @deprecated 4.6.0 - Use rlDb->update()
     *
     * @param array $data   - updated criterias:
     *           array(
     *             [item] => array
     *               (
     *                   [fields] => array()
     *                   [where] =>  array()
     *               )
     *           )
     * @param string $table - table name
     * @param array  $htmlFields - fields keys which can contain HTML
     * @param array  $codeFields - keys of fields which contains mysql code
     * @return bool
     **/
    public function update($data, $table, $htmlFields = [], $codeFields = ['Pay_date', 'Featured_date', 'Date'])
    {
        $GLOBALS['rlDb']->rlAllowHTML = $this->rlAllowHTML;
        return $GLOBALS['rlDb']->update($data, $table, $htmlFields, $codeFields);
    }

    /**
     * update one db row | DEPRECATED
     *
     * @since 4.9.2 - $codeFields added
     *
     * @deprecated 4.6.0 - Use rlDb->update()
     *
     * @param array $data   - updated criterias:
     *           array(
     *                   [fields] => array()
     *                   [where] =>  array()
     *           )
     * @param string $table - table name
     * @param array  $htmlFields - fields keys which can contain HTML
     * @param array  $codeFields - keys of fields which contains mysql code
     * @return bool
     **/
    public function updateOne($data, $table = null, $htmlFields = false, $codeFields = ['Pay_date', 'Featured_date', 'Date'])
    {
        $GLOBALS['rlDb']->rlAllowHTML = $this->rlAllowHTML;
        return $GLOBALS['rlDb']->updateOne($data, $table, $htmlFields, $codeFields);
    }

    /**
     * file upload (from form)
     *
     * @param string $field        - field name from the form
     * @param string/array $file   - new file name
     * @param string $resize_type  - resolution type
     *        resize types: W = Width, H =   Height, P = Percentage, C = Custom (array(w, h))
     * @param mixed  $resolution   - resolution size
     * @param string $parent  - parent input field name
     * @param bool $watermark  - wotermark on uploaded image
     *
     * @return bool
     **/
    public function upload($field = false, $file = false, $resize_type = false, $resolution = false, $parent = false, $watermark = true)
    {
        global $config, $l_deny_files_regexp, $rlHook, $reefless;

        $tmp = $_SESSION['tmp_files'];

        $rlHook->load('phpUpload');

        // Get file from tmp directory
        if ((is_readable(RL_UPLOAD . $tmp[$parent][$field]) && $tmp[$parent][$field]) || (is_readable(RL_UPLOAD . $tmp[$field]) && $tmp[$field])) {
            $file_tmp_name = $tmp[$parent][$field] ?: $tmp[$field];

            /* prevent denied files upload */
            if (preg_match($l_deny_files_regexp, $file_tmp_name)) {
                return false;
            }

            $file_ext = pathinfo($file_tmp_name, PATHINFO_EXTENSION);
            $file_name = $file . '.' . $file_ext;
            $file_dir = RL_UPLOAD . $file_tmp_name;
        }
        // Upload file
        else {
            $file_tmp_name = $parent && $_FILES[$parent] ? $_FILES[$parent]['name'][$field] : $_FILES[$field]['name'];

            // No file uploaded by user
            if (!$file_tmp_name) {
                return false;
            }

            // Prevent denied files upload
            if (preg_match($l_deny_files_regexp, $file_tmp_name)) {
                return false;
            }

            $file_tmp_dir = $parent && $_FILES[$parent] ? $_FILES[$parent]['tmp_name'][$field] : $_FILES[$field]['tmp_name'];
            $file_ext = pathinfo($file_tmp_name, PATHINFO_EXTENSION);
            $file_name = $file . '.' . $file_ext;
            $file_dir = RL_UPLOAD . $file_name;
            $upload_dir = dirname($file_dir);

            if ($this->photoSaveOriginal) {
                $file_name_original = $file . '_original.' . $file_ext;
                $this->photoOriginal = $file_name_original;
            }

            if (!is_dir($upload_dir)) {
                $reefless->rlMkdir($upload_dir);
            }

            if (move_uploaded_file($file_tmp_dir, $file_dir)) {
                $reefless->rlChmod($file_dir);
            } else {
                $GLOBALS['rlDebug']->logger("Unable to move_uploaded_file {$file_name} to {$upload_dir} directory.");
                return false;
            }
        }

        if (is_readable($file_dir)) {
            $final_distanation = RL_FILES . $file_name;

            if (!is_dir(dirname($final_distanation))) {
                $reefless->rlMkdir(dirname($final_distanation));
            }

            if ($this->photoSaveOriginal && $this->photoOriginal) {
                copy($file_dir, RL_FILES . $this->photoOriginal);
            }

            if (!empty($resize_type) && !empty($resolution)) {
                $reefless->loadClass('Resize');
                $reefless->loadClass('Crop');

                /* crop image */
                if (is_array($resolution)) {
                    $GLOBALS['rlCrop']->loadImage($file_dir);
                    $GLOBALS['rlCrop']->cropBySize($resolution[0], $resolution[1], ccCENTER);
                    $GLOBALS['rlCrop']->saveImage($file_dir, $config['img_quality']);
                    $GLOBALS['rlCrop']->flushImages();
                }

                /* resize image */
                $resize_type = strtoupper($resize_type);
                $GLOBALS['rlResize']->resize($file_dir, $final_distanation, $resize_type, $resolution, true, $watermark);
            } else {
                copy($file_dir, $final_distanation);
            }

            if (is_readable($final_distanation)) {
                unlink($file_dir);

                if ($parent) {
                    unset($_SESSION['tmp_files'][$parent][$field]);
                } else if ($field) {
                    unset($_SESSION['tmp_files'][$field]);
                }

                return $file_name;
            }
        }

        return false;
    }

    /**
     * Add new value to enum database field
     *
     * @param bool $table - table
     * @param bool $field - field
     * @param bool $value - new value
     *
     * @return bool
     */
    public function enumAdd($table = false, $field = false, $value = false)
    {
        return $this->enum($table, $field, $value);
    }

    /**
     * Remove value from enum database field
     *
     * @param bool $table - table
     * @param bool $field - field
     * @param bool $value - new value
     *
     * @return bool
     */
    public function enumRemove($table = false, $field = false, $value = false)
    {
        return $this->enum($table, $field, $value, 'remove');
    }

    /**
     * Enum/set database fields manager
     *
     * @param bool   $table - table
     * @param bool   $field - field
     * @param bool   $value - new value
     * @param string $mode  - add or remove
     *
     * @return bool
     */
    public function enum($table = false, $field = false, $value = false, $mode = 'add')
    {
        global $rlDb;

        if (!$table || !$field || !$value) {
            return false;
        }

        $enum_row = $rlDb->getRow("SHOW COLUMNS FROM `" . RL_DBPREFIX . $table . "` LIKE '{$field}'");
        preg_match('/([a-z]*)\((.*)\)/', $enum_row['Type'], $matches);

        if (!in_array(strtolower($matches[1]), array('enum', 'set'))) {
            die('ENUM add/edit method (table: ' . $table . '): <b>' . $field . '</b> field is not ENUM or SET type field');
            return false;
        }

        $enum_values = explode(',', $matches[2]);

        if ($mode == 'add') {
            if (false !== array_search("'{$value}'", $enum_values)) {
                die('ENUM add/edit method (table: ' . $table . '): <b>' . $field . '</b> field already has <b>' . $value . '</b> value');
                return false;
            }

            array_push($enum_values, "'{$value}'");
        } elseif ($mode == 'remove') {
            $pos = array_search("'{$value}'", $enum_values);

            if ($pos === false) {
                return false;
            }

            unset($enum_values[$pos]);

            if (empty($enum_values)) {
                die('ENUM add/edit method (table: ' . $table . '): <b>' . $field . '</b> field will not has any values after your remove');
                return false;
            }

            $enum_values = array_values($enum_values);
        }

        $sql = "ALTER TABLE `" . RL_DBPREFIX . $table . "` CHANGE `{$field}` `{$field}` " . strtoupper($matches[1]) . "( " . implode(',', $enum_values) . " ) NOT NULL";
        if (strtolower($matches[1]) == 'enum') {
            $sql .= " DEFAULT {$enum_values[0]}";
        }

        $rlDb->query($sql);

        return true;
    }

    /**
     * display no table selected error
     *
     * @todo show error, write logs
     **/
    public function tableNoSel()
    {
        RlDebug::logger("SQL query can't be run, it isn't table name selected", null, null, 'Warning');
        return 'Table not selected, see error log';
    }
}
