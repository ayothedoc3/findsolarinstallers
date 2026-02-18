#!/usr/bin/env php
<?php
/**
 * Solar Listing Updater
 *
 * Updates existing Flynax listings with fresh data from the pipeline.
 * Used by pipeline_orchestrator.py for incremental updates (changed ratings,
 * review counts, business status, etc.)
 *
 * Usage:
 *   php scripts/update_listing.php --input data/updates.csv
 *   php scripts/update_listing.php --input data/updates.csv --dry-run
 *   php scripts/update_listing.php --deactivate data/closed.csv
 *
 * CSV formats:
 *   Updates:     listing_id,field_key,new_value
 *   Deactivate:  listing_id
 */

// CLI only
if (php_sapi_name() !== 'cli') {
    die('This script must be run from the command line.');
}

set_time_limit(0);
ini_set('memory_limit', '512M');

// Bootstrap Flynax
define('CRON_FILE', true);
$flynaxRoot = dirname(__DIR__);
$configPath = $flynaxRoot . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'config.inc.php';

if (!file_exists($configPath)) {
    fwrite(STDERR, "ERROR: Flynax config not found at: {$configPath}\n");
    exit(1);
}

require_once $configPath;

if (!defined('RL_DBHOST') || !defined('RL_DBUSER') || !defined('RL_DBNAME')) {
    fwrite(STDERR, "ERROR: Database constants not defined. Is Flynax installed?\n");
    exit(1);
}

// Parse CLI arguments
$options = getopt('', ['input:', 'deactivate:', 'dry-run', 'help']);

if (isset($options['help'])) {
    echo <<<HELP
Solar Listing Updater

Usage:
  php update_listing.php --input <csv>        Update listing fields from CSV
  php update_listing.php --deactivate <csv>   Deactivate listings from CSV
  php update_listing.php --dry-run            Preview changes without writing

CSV format for --input:   listing_id,field_key,new_value
CSV format for --deactivate:  listing_id  (one per line)

HELP;
    exit(0);
}

$inputFile      = $options['input'] ?? null;
$deactivateFile = $options['deactivate'] ?? null;
$dryRun         = isset($options['dry-run']);

if (empty($inputFile) && empty($deactivateFile)) {
    fwrite(STDERR, "ERROR: --input or --deactivate required. Use --help for usage.\n");
    exit(1);
}

// Database connection
$prefix = defined('RL_DBPREFIX') ? RL_DBPREFIX : 'fl_';
$dbPort = defined('RL_DBPORT') ? (int)RL_DBPORT : 3306;
$dbPass = defined('RL_DBPASS') ? RL_DBPASS : '';

$mysqli = @mysqli_connect(RL_DBHOST, RL_DBUSER, $dbPass, RL_DBNAME, $dbPort);
if (!$mysqli) {
    fwrite(STDERR, "ERROR: DB connection failed: " . mysqli_connect_error() . "\n");
    exit(1);
}
$mysqli->set_charset('utf8mb4');

$ts = function () { return date('Y-m-d H:i:s'); };
$stats = ['updated' => 0, 'deactivated' => 0, 'skipped' => 0, 'errors' => 0];

// ============================================================================
// MODE 1: Update listing fields
// ============================================================================
if (!empty($inputFile)) {
    // Resolve relative paths
    if (!file_exists($inputFile)) {
        $inputFile = $flynaxRoot . DIRECTORY_SEPARATOR . $inputFile;
    }
    if (!file_exists($inputFile) || !is_readable($inputFile)) {
        fwrite(STDERR, "ERROR: CSV not found: {$inputFile}\n");
        $mysqli->close();
        exit(1);
    }

    echo "[{$ts()}] Updating listings from: {$inputFile}\n";
    if ($dryRun) {
        echo "[{$ts()}] ** DRY RUN MODE **\n";
    }

    $handle = fopen($inputFile, 'r');
    $headers = fgetcsv($handle);

    // Trim BOM
    $headers = array_map(function ($h) {
        return trim($h, "\xEF\xBB\xBF \t\n\r\0\x0B");
    }, $headers);

    $headerIndex = array_flip($headers);

    // Require listing_id, field_key, new_value columns
    $required = ['listing_id', 'field_key', 'new_value'];
    foreach ($required as $col) {
        if (!isset($headerIndex[$col])) {
            fwrite(STDERR, "ERROR: CSV missing required column: {$col}\n");
            fclose($handle);
            $mysqli->close();
            exit(1);
        }
    }

    // Prepare statements
    $updateStmt = $mysqli->prepare(
        "UPDATE `{$prefix}listings_data` SET `Value` = ? WHERE `Listing_ID` = ? AND `Key` = ?"
    );
    $insertStmt = $mysqli->prepare(
        "INSERT INTO `{$prefix}listings_data` (`Listing_ID`, `Key`, `Value`) VALUES (?, ?, ?)"
    );

    $rowNum = 0;
    while (($row = fgetcsv($handle)) !== false) {
        $rowNum++;
        if (count($row) !== count($headers)) {
            $stats['skipped']++;
            continue;
        }

        $data = array_combine($headers, $row);
        $listingId = (int)$data['listing_id'];
        $fieldKey  = trim($data['field_key']);
        $newValue  = trim($data['new_value']);

        if ($listingId <= 0 || empty($fieldKey)) {
            $stats['skipped']++;
            continue;
        }

        if (!$dryRun) {
            // Try update first
            $updateStmt->bind_param('sis', $newValue, $listingId, $fieldKey);
            $updateStmt->execute();

            if ($updateStmt->affected_rows === 0) {
                // Row didn't exist, insert it
                $insertStmt->bind_param('iss', $listingId, $fieldKey, $newValue);
                if (!$insertStmt->execute()) {
                    $stats['errors']++;
                    fwrite(STDERR, "[{$ts()}] ERROR row {$rowNum}: " . $insertStmt->error . "\n");
                    continue;
                }
            }
        }

        $stats['updated']++;

        if ($rowNum % 500 === 0) {
            echo "[{$ts()}] Processed {$rowNum} updates ...\n";
        }
    }

    $updateStmt->close();
    $insertStmt->close();
    fclose($handle);

    echo "[{$ts()}] Field updates complete: {$stats['updated']} updated, {$stats['skipped']} skipped, {$stats['errors']} errors\n";
}

// ============================================================================
// MODE 2: Deactivate listings
// ============================================================================
if (!empty($deactivateFile)) {
    if (!file_exists($deactivateFile)) {
        $deactivateFile = $flynaxRoot . DIRECTORY_SEPARATOR . $deactivateFile;
    }
    if (!file_exists($deactivateFile) || !is_readable($deactivateFile)) {
        fwrite(STDERR, "ERROR: Deactivation CSV not found: {$deactivateFile}\n");
        $mysqli->close();
        exit(1);
    }

    echo "[{$ts()}] Deactivating listings from: {$deactivateFile}\n";

    $deactStmt = $mysqli->prepare(
        "UPDATE `{$prefix}listings` SET `Status` = 'expired' WHERE `ID` = ? AND `Status` = 'active'"
    );

    $handle = fopen($deactivateFile, 'r');
    $headers = fgetcsv($handle);

    while (($row = fgetcsv($handle)) !== false) {
        $listingId = (int)($row[0] ?? 0);
        if ($listingId <= 0) {
            $stats['skipped']++;
            continue;
        }

        if (!$dryRun) {
            $deactStmt->bind_param('i', $listingId);
            $deactStmt->execute();

            if ($deactStmt->affected_rows > 0) {
                $stats['deactivated']++;
            } else {
                $stats['skipped']++;
            }
        } else {
            $stats['deactivated']++;
        }
    }

    $deactStmt->close();
    fclose($handle);

    // Update category counts after deactivation
    if (!$dryRun && $stats['deactivated'] > 0) {
        $catIds = '2000,2001,2002,2003,2004,2005,2006';
        $mysqli->query("
            UPDATE `{$prefix}categories`
            SET `Count` = (
                SELECT COUNT(*) FROM `{$prefix}listings`
                WHERE `{$prefix}listings`.`Category_ID` = `{$prefix}categories`.`ID`
                  AND `{$prefix}listings`.`Status` = 'active'
            )
            WHERE `ID` IN ({$catIds})
        ");
    }

    echo "[{$ts()}] Deactivation complete: {$stats['deactivated']} deactivated, {$stats['skipped']} skipped\n";
}

// Final summary
echo "\n";
echo "==========================================================\n";
echo "  UPDATE COMPLETE" . ($dryRun ? " (DRY RUN)" : "") . "\n";
echo "==========================================================\n";
echo "  Fields updated:    {$stats['updated']}\n";
echo "  Deactivated:       {$stats['deactivated']}\n";
echo "  Skipped:           {$stats['skipped']}\n";
echo "  Errors:            {$stats['errors']}\n";
echo "==========================================================\n";

$mysqli->close();
exit($stats['errors'] > 0 ? 2 : 0);
