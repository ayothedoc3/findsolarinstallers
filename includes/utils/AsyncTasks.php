<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.10.1
 *  LICENSE: FL1KU6QLIUJA - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: bizlisting.xyz
 *  FILE: ASYNCTASKS.PHP
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

namespace Flynax\Utils;

use GuzzleHttp\Client;
use InvalidArgumentException;

/**
 * Class allows run the tasks asynchronous
 * Like: translate text fields of listings/accounts; send emails and etc.
 * @since 4.9.3
 */
class AsyncTasks
{
    /**
     * Name of table with tasks
     */
    public const TABLE = 'async_tasks';

    /**
     * Name of table with system prefix
     */
    public const TABLE_WITH_PREFIX = '{db_prefix}' . self::TABLE;

    /**
     * Limit of tasks per run
     */
    public const LIMIT = 10;

    /**
     * Name of PHP script for async tasks
     */
    public const SCRIPT_NAME = 'async.tasks.php';

    /**
     * Full path of PHP script of async tasks
     */
    public const SCRIPT_PATH = RL_LIBS . self::SCRIPT_NAME;

    /**
     * URL of PHP script of async tasks
     */
    public const SCRIPT_URL = RL_LIBS_URL . self::SCRIPT_NAME;

    /**
     * Creates a new task
     *
     * @param  string  $type    - Type of task
     * @param  array   $data    - Array with necessary data for task
     * @param  boolean $runTask - Run current task immediately in background
     * @param  array   $task    - Additional info of task
     * @return boolean
     */
    public static function create(string $type, array $data = [], bool $runTask = false, array $task = []): bool
    {
        global $rlDb, $rlHook;

        if (!$type) {
            new InvalidArgumentException('Error: Missing required "type" parameter.');
        }

        $task = [
            'Type' => $type,
            'Data' => $data ? json_encode($data) : '',
            'Task' => $task ? json_encode($task) : '',
        ];

        if (!$id = (int) $rlDb->fetch(['ID'], $task, null, 1, self::TABLE, 'row')['ID']) {
            $rlDb->insertOne($task, self::TABLE, ['Data', 'Task']);
            $id = (int) $rlDb->insertID();
        }

        $rlHook->load('asyncTasksCreateTaskBeforeRun', $id, $type, $data, $runTask, $task);

        if ($runTask && $type && $id) {
            self::run($id, $type);
        }

        return true;
    }

    /**
     * Run tasks by type or ID in background
     *
     * @param  integer $id   - ID of task
     * @param  string  $type - Type of tasks
     * @return void
     */
    public static function run(int $id = 0, string $type = ''): void
    {
        $isExecAvailable = function_exists('exec') && PHP_BINDIR;

        if (!$isExecAvailable) {
            $client  = new Client(['timeout' => 0.1]);
        }

        foreach (self::get($id, $type) as $task) {
            try {
                if ($isExecAvailable) {
                    exec(PHP_BINDIR . '/php -f ' . self::SCRIPT_PATH . " {$task['ID']} >> /dev/null &");
                } else {
                    $promise = $client->requestAsync('GET', self::SCRIPT_URL . "?id={$task['ID']}");
                    $promise->wait();
                }
            } catch (\Exception $e) {
                // do nothing, the timeout exception is intended
            }
        }
    }

    /**
     * Get list of necessary tasks: by ID, by Type or list
     *
     * @param  integer $id - ID of task
     * @param  string  $type - Type of tasks
     * @return array
     */
    public static function get(int $id = 0, string $type = ''): array
    {
        global $rlDb;

        if ($id) {
            $tasks = [$rlDb->fetch('*', ['ID' => $id], null, 1, self::TABLE, 'row')];
        } elseif ($type) {
            $tasks = (array) $rlDb->fetch('*', ['Type' => $type], null, self::LIMIT, self::TABLE);
        } else {
            $tasks = (array) $rlDb->fetch('*', [], null, self::LIMIT, self::TABLE);
        }

        return $tasks;
    }

    /**
     * Execute the task by ID
     *
     * @param  integer $id
     * @return void
     */
    public static function execute(int $id): void
    {
        global $rlDb, $rlHook;

        if (!$id) {
            return;
        }

        $task = $rlDb->fetch('*', ['ID' => $id], null, 1, self::TABLE, 'row');
        $data = $task['Data'] ? json_decode($task['Data'], true) : [];

        $isTaskCompleted = false;

        /**
         * @since 4.10.1
         */
        $rlHook->load('phpAsyncTasksBeforeExecute', $id, $task, $data, $isTaskCompleted);

        switch ($task['Type']) {
            case 'translate_listing':
            case 'translate_account':
                if ($data && $data['ID']) {
                    if ($task['Type'] === 'translate_account' && !$rlDb->getOne('ID', "`ID` = '{$data['ID']}'", 'accounts')) {
                        $isTaskCompleted = true;
                        break;
                    }

                    if ($task['Type'] === 'translate_listing' && !$rlDb->getOne('ID', "`ID` = '{$data['ID']}'", 'listings')) {
                        $isTaskCompleted = true;
                        break;
                    }

                    if (($task['Type'] === 'translate_listing' && Translator::translateListingText($data['ID']))
                        || ($task['Type'] === 'translate_account' && Translator::translateAccountText($data['ID']))
                    ) {
                        $isTaskCompleted = true;
                    }
                }
                break;
            /* @since 4.10.1 */
            case 'send_email':
                if ($data && $data['class'] && $data['method']) {
                    $class  = unserialize($data['class']);
                    $method = $data['method'];

                    if ($class && $method && $class instanceof \PHPMailer\PHPMailer\PHPMailer) {
                        $class->Username = $class->Username ? base64_decode($class->Username) : '';
                        $class->Password = $class->Password ? base64_decode($class->Password) : '';
                        $class->Body     = $class->Body ? base64_decode($class->Body) : '';

                        try {
                            if ($class->$method()) {
                                if (method_exists($class, 'ClearAddresses')) {
                                    $class->ClearAddresses();
                                }
                                if (method_exists($class, 'ClearAttachments')) {
                                    // Remove attachments
                                    if (method_exists($class, 'getAttachments')
                                        && is_array($class->getAttachments())
                                    ) {
                                        foreach ($class->getAttachments() as $attachment) {
                                            $filePath = $attachment[0] ?? null;

                                            if ($filePath && file_exists($filePath)) {
                                                unlink($filePath);
                                            }
                                        }
                                    }

                                    $class->ClearAttachments();
                                }

                                $isTaskCompleted = true;
                            }
                        } catch (\Exception $e) {
                            $GLOBALS['rlDebug']->logger(
                                'Error in async email sending:'
                                    . isset($class->ErrorInfo) ? $class->ErrorInfo : $e->getMessage()
                            );
                        }
                    }
                }
                break;
        }

        /**
         * @since 4.10.1
         */
        $rlHook->load('phpAsyncTasksAfterExecute', $task, $data, $isTaskCompleted);

        if ($isTaskCompleted) {
            $rlDb->delete(['ID' => $id], self::TABLE);
        }
    }

    /**
     * Remove tasks by provided data
     *
     * @param  string $type - Type of task
     * @param  array  $data - Array with necessary data for task
     * @param  array  $task - Additional info of task
     * @return void
     */
    public static function remove(string $type, array $data = [], array $task = []): void
    {
        if (!$type) {
            new InvalidArgumentException('Error: Missing required "type" parameter.');
        }

        $where = [
            'Type' => $type,
            'Data' => $data ? json_encode($data) : '',
            'Task' => $task ? json_encode($task) : '',
        ];

        $GLOBALS['rlDb']->delete($where, self::TABLE, null, 0);
    }
}
