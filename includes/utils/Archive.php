<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.10.1
 *  LICENSE: FL1KU6QLIUJA - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: bizlisting.xyz
 *  FILE: ARCHIVE.PHP
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

/**
 * Class for packing/unpacking archives
 *
 * @since 4.7.1
 */
class Archive
{
    /**
     * Unpack archive to destination directory
     *
     * @param string $archive       - Path of archive
     * @param string $destination   - Path of folder where archive must be unpacked
     * @param bool   $removeArchive - Archive will be removed after unpacking
     *
     * @return bool
     */
    public static function unpack(string $archive, string $destination, bool $removeArchive = true): bool
    {
        global $rlDebug;

        if (!$archive || !$destination) {
            return false;
        }

        if (!class_exists('ZipArchive')) {
            $rlDebug->logger('ZipArchive class is missing in server.');
            return false;
        }

        // Create folder if it's not exist
        $GLOBALS['reefless']->rlMkdir($destination);

        $zip    = new \ZipArchive;
        $result = $zip->open($archive);

        if ($result === true) {
            $zip->extractTo($destination);
            $zip->close();
        } else {
            $rlDebug->logger('Zip Archive | Archive cannot be unpacked, error code: ' . $result);
            $result = false;
        }

        if ($removeArchive) {
            unlink($archive);
        }

        return $result;
    }

    /**
     * Pack file/folder to archive
     *
     * @param string|array $source  - Path of file/directory
     *                              - Example of file: /root/folder/.../file.php
     *                              - Example of folder: /root/folder/.../folder
     *                              - Example of array: ['/root/folder/.../file.php', '/root/folder/.../folder',]
     * @param string       $archive - Path of archive, example: /root/folder/.../archive.zip
     *
     * @return bool
     */
    public static function pack($source, string $archive): bool
    {
        global $rlDebug;

        if (!$source || !$archive) {
            return false;
        }

        if (!class_exists('ZipArchive')) {
            $rlDebug->logger('ZipArchive class is missing in server.');
            return false;
        }

        // Prepare list of files for packing
        $files = [];

        if (is_string($source)) {
            if (is_file($source)) {
                $files[] = $source;
            } elseif (is_dir($source)) {
                // Add missing directory separator
                $files[] = $source . (!in_array(substr($source, -1, 1), ['/', '\\']) ? RL_DS : '');
            }
        } else if (is_array($source)) {
            foreach ($source as $item) {
                if (is_file($item) || is_dir($item)) {
                    if (is_file($item)) {
                        $files[] = $item;
                    } elseif (is_dir($item)) {
                        // Add missing directory separator
                        $files[] = $item . (!in_array(substr($item, -1, 1), ['/', '\\']) ? RL_DS : '');
                    }
                }
            }
        }

        unlink($archive);

        if (!$files) {
            return false;
        }


        $zip    = new \ZipArchive;
        $result = $zip->open($archive, \ZipArchive::CREATE);

        if ($result === true) {
            foreach ($files as $file) {
                $source     = realpath($file);
                $folderName = pathinfo($source)['dirname'];

                if (is_dir($source)) {
                    $iterator = new \RecursiveDirectoryIterator($source);

                    // skip dot files while iterating
                    $iterator->setFlags(\RecursiveDirectoryIterator::SKIP_DOTS);
                    $childFiles = new \RecursiveIteratorIterator(
                        $iterator,
                        \RecursiveIteratorIterator::SELF_FIRST
                    );

                    foreach ($childFiles as $childFile) {
                        $childFile = realpath($childFile);

                        if (is_dir($childFile)) {
                            $zip->addEmptyDir(str_replace($folderName, '', $childFile . '/'));
                        } else if (is_file($childFile)) {
                            $zip->addFile($childFile, str_replace($folderName, '', $childFile));
                        }
                    }
                } else if (is_file($source)) {
                    $zip->addFile($source, str_replace($folderName, '', $source));
                }
            }

            $zip->close();
        } else {
            $rlDebug->logger("Zip Archive | Archive cannot be packed, error code: " . $result);
        }

        return file_exists($archive);
    }
}
