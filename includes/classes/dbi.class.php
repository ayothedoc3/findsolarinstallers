<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.10.1
 *  LICENSE: FL1KU6QLIUJA - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: bizlisting.xyz
 *  FILE: DBI.CLASS.PHP
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

/**
 * Database class supports mysqli library
 *
 * Class is handling mysql connection and all queries in the application using mysqli library
 */
class rlDatabase
{
    /**
     * Current table name
     *
     * @var string
     */
    public $tName = null;

    /**
     * Current mysql API version
     *
     * @var string
     */
    public $mysqlVer = null;

    /**
     * Mysql calculate found rows
     *
     * @var bool
     */
    public $calcRows = false;

    /**
     * SQL query start time
     *
     * @var bool
     **/
    public $start = 0;

    /**
     * Rows mapping
     *
     * Examples: ['Key', 'Value'], [false, 'Key'], 'ID' and etc.
     */
    public $outputRowsMap = false;

    /**
     * Die/Exit if st. errors
     */
    public $dieIfError = true;

    /**
     * Mysqli client object
     *
     * @since 4.10.0 - Value replaced to the mysqli client object and type changed to protected
     *
     * @var object
     */
    protected static $mysqli = null;

    /**
     * Total number of rows found
     *
     * @var int
     */
    public $foundRows = 0;

    /**
     * Open mysql connection and select database
     *
     * @param string $host
     * @param int    $port
     * @param string $user
     * @param string $pass
     * @param string $base_name
     */
    public function connect($host, $port = 3306, $user = '', $pass = '', $base_name = '')
    {
        if (!empty(self::$mysqli->server_info)) {
            return;
        }

        try {
            self::$mysqli = mysqli_connect($host, $user, $pass, $base_name, $port);
        } catch (mysqli_sql_exception $e) {
            if ($this->dieIfError === false) {
                return false;
            }
            die($e->getMessage());
        }

        if (false === self::$mysqli) {
            if ($this->dieIfError === false) {
                return false;
            }
            die('Could not connect to MySQL server');
        }

        self::$mysqli->set_charset('utf8mb4');
        $this->query("SET sql_mode = ''");
    }

    /**
     * Set current table name
     *
     * @param string $name - Name of the table
     */
    public function setTable($name)
    {
        $this->tName = $name;
    }

    /**
     * Reset table name
     */
    public function resetTable()
    {
        $this->tName = null;
    }

    /**
     * Get latest insert id
     *
     * @return int
     */
    public function insertID()
    {
        return self::$mysqli->insert_id;
    }

    /**
     * Returns a string description of the last error
     *
     * @since v4.4
     * @return string
     */
    public function lastError()
    {
        if (!self::$mysqli) {
            return 'Could not connect to MySQL server';
        }
        return self::$mysqli->error;
    }

    /**
     * Returns the error code for the most recent function call
     *
     * @since v4.4
     * @return int
     */
    public function lastErrno()
    {
        return self::$mysqli ? self::$mysqli->errno : -1;
    }

    /**
     * Get affected rows from latest executed query
     *
     * @return int
     */
    public function affectedRows()
    {
        return self::$mysqli->affected_rows;
    }

    /**
     * Closes a previously opened database connection
     *
     * @since 4.6.0 - Added $force param
     *
     * @param bool $force - Close the connection immediately
     */
    public function connectionClose($force = false)
    {
        if ($force === true) {
            self::$mysqli->close();
        } else {
            register_shutdown_function(function() {
                self::$mysqli->close();
            });
        }
    }

    /**
     * Run SQL query
     *
     * @param string $sql - SQL query string
     * @return mixed
     */
    public function query($sql)
    {
        $this->checkConnection();

        $this->calcTime('start');

        $res = self::$mysqli->query($sql);

        if (!$res) {
            if ($this->dieIfError === false) {
                return false;
            }
            $this->error($sql);
        }

        $this->calcTime('end', $sql);

        return $res;
    }

    /**
     * Get all data from the table
     *
     * @param string $sql - mySQL query string
     * @param mixed $outputMap - 'index_key' ||
     *                           array('index_key', 'value_row_key') ||
     *                           array(false, 'value_row_key')
     * Example:
     *     'Key': return: ['key1' => all_selected_rows], etc...
     *     array('Key', 'Path'):  return: ['key1' => 'Path'], etc...
     *     array(false, 'Path'):  return: [0 => 'Path'], etc...
     *
     * @return array
     */
    public function getAll($sql, $outputMap = false)
    {
        $res = $this->query($sql);

        // mapping
        $map_index = $map_value = false;
        if ($outputMap) {
            if (is_string($outputMap)) {
                $map_index = trim($outputMap);
            } else if (is_array($outputMap) && 2 === count($outputMap)) {
                if ($outputMap[0] !== false) {
                    $map_index = trim($outputMap[0]);
                }

                $map_value = trim($outputMap[1]);
            }
        }

        // Convert to array
        $ret = array();
        while ($row = $res->fetch_assoc()) {
            $row_value = ($map_value && array_key_exists($map_value, $row)) ? $row[$map_value] : $row;

            // Add to array
            if ($map_index && array_key_exists($map_index, $row)) {
                $ret[$row[$map_index]] = $row_value;
            } else {
                array_push($ret, $row_value);
            }
            unset($row, $row_value);
        }

        return $ret;
    }

    /**
     * Get one field of result row
     *
     * @param  string $field  - Field name
     * @param  string $where  - Select condition
     * @param  string $table  - Table name
     * @param  string $prefix - Table prefix
     * @return string         - Data as associative array
     */
    public function getOne($field = false, $where = null, $table = null, $prefix = false)
    {
        if ($table == null) {
            if ($this->tName != null) {
                $table = $this->tName;
            } else {
                $this->tableNoSel();
            }
        }

        if (!$field || !$where) {
            return '';
        }

        $prefix = $prefix ?: RL_DBPREFIX;
        $sql = "SELECT `{$field}` FROM `{$prefix}{$table}` WHERE {$where} LIMIT 1";

        $res = $this->query($sql);
        $ret = $res->fetch_row();

        return is_array($ret) ? $ret[0] : '';
    }

    /**
     * get one row from the table
     *
     * @param string $sql - mySQL query string
     * @param string $field - return only it
     *
     * @return array - Data as associative array / string
     **/
    public function getRow($sql = false, $field = false)
    {
        if (!(bool) preg_match('/LIMIT\s+[0-9]+/', $sql) && !is_numeric(strpos($sql, 'SHOW'))) {
            $sql .= ' LIMIT 1';
        }

        $res = $this->query($sql);
        $row = $res->fetch_assoc();

        if ($field !== false) {
            return $row[$field];
        }
        return $row;
    }

    /**
     * Select data by criteria from the table
     *
     * @param array $fields - fields names array: array( 'field1', 'field2', 'field3')
     * @param array $where  - array of selected criteria:
     *           array(
     *             'field name' => 'value',
     *             'field name' => 'value'
     *                 )
     * @param string $options  - options string: "ORDER BY `field` "
     * @param int|array $limit - limit parameters: int (rows number) or array( 'from', 'rows' )
     * @param string $table    - table name
     * @param string $action   - selected type: all table content or one row
     *
     * @return array - Data as associative array
     */
    public function fetch($fields = '*', $where = null, $options = null, $limit = null, $table = null, $action = 'all')
    {
        if ($table == null) {
            if ($this->tName != null) {
                $table = $this->tName;
            } else {
                $this->tableNoSel();
            }
        }

        $query = "SELECT ";

        if (is_array($fields)) {
            foreach ($fields as $sel_field) {
                $query .= "`{$sel_field}`,";
            }
            $query = substr($query, 0, -1);
        } else {
            $query .= " * ";
        }

        $query .= " FROM `" . RL_DBPREFIX . $table . "` ";

        if (is_array($where)) {
            $query .= " WHERE ";

            foreach ($where as $key => $value) {
                $GLOBALS['rlValid']->sql($value);
                $query .= " (`{$key}` = '{$value}') AND";
            }
            $query = substr($query, 0, -3);
        }

        if ($options != null) {
            $query .= " " . $options . " ";
        }

        if (is_array($limit)) {
            $qStart = (int) $limit[0];
            $qLimit = (int) $limit[1];
            $query .= " LIMIT {$qStart}, {$qLimit} ";
        } else {
            if ($action == 'row' && empty($limit)) {
                $limit = 1;
            }

            if (!empty($limit)) {
                $query .= " LIMIT {$limit} ";
            }
        }

        if ($action == 'row') {
            $output = $this->getRow($query);
        } else {
            $output = $this->getAll($query, $this->outputRowsMap);
            $this->outputRowsMap = false;
        }

        if ($this->calcRows) {
            $this->foundRows = $this->getTotalCount($query);
        }

        return $output;
    }

    /**
     * Return mysql error
     *
     * @return string - MySQL error
     */
    public function error()
    {
        return self::$mysqli->error;
    }

    /**
     * Calculate query time
     *
     * @param string $mode - Start or end of the query
     */
    public function calcTime($mode = 'start', $sql = false)
    {
        if (!RL_DB_DEBUG) {
            return false;
        }

        if ($mode == 'start') {
            $time = microtime();
            $time = explode(" ", $time);
            $time = $time[1] + $time[0];
            $this->start = $time;
        } else {
            if (!$_SESSION['sql_debug_time']) {
                $_SESSION['sql_debug_time'] = 0;
            }

            $time = microtime();
            $time = explode(" ", $time);
            $time = $time[1] + $time[0];
            $finish = $time;
            $totaltime = ($finish - $this->start);
            $_SESSION['sql_debug_time'] += $totaltime;
            $color = 'green';
            if ($totaltime >= 0.1) {
                $color = 'red';
            } elseif ($totaltime > 0.01 && $totaltime < 0.1) {
                $color = 'orange';
            }
            printf("The query took <span style=\"color: {$color};\"><b>%f</b></span> seconds to load.<br />", $totaltime);
            $backtrace = debug_backtrace();
            $level = count($backtrace);
            $log = $level > 1 ? $backtrace[$level - 3] : $backtrace[0];
            echo $log['file'] . "({$log['line']}) / function: {$log['function']}<br />";
            echo $sql . '<br /><br />';
        }
    }

    /**
     * Get mysql version
     *
     * @since 4.5.0
     */
    public function getClientInfo()
    {
        return mysqli_get_client_info();
    }

    /**
     * Display no table selected error
     *
     * @todo show error, write logs
     */
    public function tableNoSel()
    {
        $GLOBALS['rlDebug']->logger("SQL query can't be run, it isn't table name selected");
        die('Table not selected, see error log');
    }

    /**
     * Get total number of rows found in database using specified SQL code (with WHERE and LIMIT)
     *
     * @since 4.10.0
     *
     * @param  string   $sql
     * @return void|int
     */
    public function getTotalCount(string $sql = '')
    {
        if (!$sql = (string) trim(preg_replace('/\s+/', ' ', $sql))) {
            return null;
        }

        $totalCountSQL = preg_replace('/,\s\(\(?\s?SELECT.*\)\)?\sAS\s\`\w+`/', '', $sql); // Remove internal subqueries
        $totalCountSQL = preg_replace('/SELECT\s(.*?)\sFROM/i', 'SELECT COUNT(*) FROM', $totalCountSQL);
        $totalCountSQL = preg_replace('/\s+ORDER\s+BY\s+.*/i', '', $totalCountSQL);
        $totalCountSQL = preg_replace('/\s+GROUP\s+BY\s+.*/i', '', $totalCountSQL);
        $totalCountSQL = preg_replace('/\s+LIMIT\s+\d+.*/i', '', $totalCountSQL);

        return (int) $this->getAll($totalCountSQL)[0]['COUNT(*)'];
    }

    /**
     * Escape string
     *
     * @since 4.10.0
     *
     * @param  string     $string
     * @return bool|false
     */
    public function realEscapeString(?string $string = '')
    {
        if (!$string) {
            return false;
        }

        $this->checkConnection();

        return self::$mysqli->real_escape_string($string);
    }

    /**
     * Check connection and reconnect to database if necessary
     *
     * @since 4.10.0
     *
     * @return void
     */
    public function checkConnection()
    {
        if (!empty(self::$mysqli->server_info)) {
            return;
        }

        $this->connect(RL_DBHOST, RL_DBPORT, RL_DBUSER, RL_DBPASS, RL_DBNAME);
        $this->connectionClose();
    }
}
