#!/usr/bin/env php
<?php
/**
 * Solar Installer Bulk Import Script
 *
 * Imports enriched solar installer data from CSV into the Flynax database.
 * This script should be run AFTER:
 *   1. Flynax is installed and configured (config.inc.php populated)
 *   2. solar_setup.sql has been executed to create custom fields/categories
 *   3. The data pipeline has produced enriched_solar_installers.csv
 *
 * Usage:
 *   php scripts/bulk_import.php --input data/enriched_solar_installers.csv --batch-size 500
 *   php scripts/bulk_import.php --input data/enriched_solar_installers.csv --dry-run
 *   php scripts/bulk_import.php --input data/enriched_solar_installers.csv --offset 1000 --limit 5000
 *
 * Options:
 *   --input        Path to enriched_solar_installers.csv (required)
 *   --batch-size   Number of rows per transaction batch (default: 500)
 *   --dry-run      Validate and parse CSV without inserting into the database
 *   --offset       Start processing from row N (0-indexed, default: 0)
 *   --limit        Maximum number of rows to process (default: all)
 *   --help         Show this help message
 */

// ============================================================================
// SAFETY: CLI only
// ============================================================================
if (php_sapi_name() !== 'cli') {
    die('This script must be run from the command line.');
}

set_time_limit(0);
ini_set('memory_limit', '1G');

// ============================================================================
// Bootstrap Flynax — load config.inc.php for DB constants
// ============================================================================
define('CRON_FILE', true); // Prevent session/browser logic in control.inc.php

$flynaxRoot = dirname(__DIR__);
$configPath = $flynaxRoot . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'config.inc.php';

if (!file_exists($configPath)) {
    fwrite(STDERR, "ERROR: Flynax config not found at: {$configPath}\n");
    fwrite(STDERR, "Make sure Flynax is installed before running this script.\n");
    exit(1);
}

require_once $configPath;

// Verify that the config was populated (post-install state)
if (!defined('RL_DBHOST') || !defined('RL_DBUSER') || !defined('RL_DBNAME') || !defined('RL_DBPREFIX')) {
    fwrite(STDERR, "ERROR: Database constants (RL_DBHOST, RL_DBUSER, etc.) are not defined.\n");
    fwrite(STDERR, "Flynax must be fully installed before running this import.\n");
    exit(1);
}

// ============================================================================
// Constants & Configuration
// ============================================================================

/** Map CSV primary_category values to Flynax category IDs */
$categoryMap = [
    'solar_installation'    => 2000,
    'residential_solar'     => 2001,
    'commercial_solar'      => 2002,
    'solar_maintenance'     => 2003,
    'solar_battery_storage' => 2004,
    'solar_pool_heating'    => 2005,
    'ev_charger_solar'      => 2006,
];

/** Default plan for imported listings */
define('IMPORT_PLAN_ID', 50);
define('IMPORT_PLAN_TYPE', 'listing');

/** Flynax table prefix */
$prefix = defined('RL_DBPREFIX') ? RL_DBPREFIX : 'fl_';

/** Error log path */
$errorLogPath = $flynaxRoot . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'import_errors.log';

/**
 * Fields from the CSV that map into fl_listings_data key-value pairs.
 * The key is the CSV column name; the value is the Flynax listings_data Key.
 * If the value is null, we use the CSV column name as-is.
 */
$listingDataFieldMap = [
    'company_name'           => 'title',
    'company_description'    => 'company_description',
    'phone'                  => 'company_phone',
    'email'                  => 'company_email',
    'website'                => 'company_website',
    'services_offered'       => 'services_offered',
    'panel_brands'           => 'panel_brands',
    'certifications'         => 'certifications',
    'financing_available'    => 'financing_available',
    'free_consultation'      => 'free_consultation',
    'warranty_years'         => 'warranty_years',
    'system_size_range'      => 'system_size_range',
    'years_in_business'      => 'years_in_business',
    'installations_completed' => 'installations_completed',
    'service_area_radius'    => 'service_area_radius',
    'google_rating'          => 'google_rating',
    'total_reviews'          => 'total_reviews',
];

// ============================================================================
// Helper Functions
// ============================================================================

/**
 * Generate a unique username from a company name.
 * Slugifies the name (lowercase alphanumeric only), truncates to 20 chars,
 * and appends a random 6-char hex suffix.
 */
function generateUsername(string $companyName): string
{
    $slug = preg_replace('/[^a-z0-9]/', '', strtolower($companyName));
    $slug = substr($slug, 0, 20);
    if (empty($slug)) {
        $slug = 'solar';
    }
    return $slug . '_' . substr(md5(uniqid(mt_rand(), true)), 0, 6);
}

/**
 * Write a timestamped message to STDOUT.
 */
function logInfo(string $message): void
{
    $ts = date('Y-m-d H:i:s');
    echo "[{$ts}] {$message}\n";
}

/**
 * Write a timestamped error to STDERR and append to the error log file.
 */
function logError(string $message, string $logPath): void
{
    $ts = date('Y-m-d H:i:s');
    $line = "[{$ts}] ERROR: {$message}";
    fwrite(STDERR, $line . "\n");
    file_put_contents($logPath, $line . "\n", FILE_APPEND | LOCK_EX);
}

/**
 * Print usage/help and exit.
 */
function showHelp(): void
{
    global $argv;
    $script = basename($argv[0]);
    echo <<<HELP
Solar Installer Bulk Import Script

Usage:
  php {$script} --input <csv_path> [options]

Required:
  --input <path>       Path to enriched_solar_installers.csv

Options:
  --batch-size <n>     Rows per transaction batch (default: 500)
  --dry-run            Validate and parse without inserting
  --offset <n>         Skip the first N data rows (default: 0)
  --limit <n>          Maximum rows to process (default: all)
  --help               Show this help message

Examples:
  php {$script} --input data/enriched_solar_installers.csv
  php {$script} --input data/enriched_solar_installers.csv --batch-size 200 --dry-run
  php {$script} --input data/enriched_solar_installers.csv --offset 5000 --limit 10000

HELP;
    exit(0);
}

// ============================================================================
// Parse CLI Arguments
// ============================================================================

$options = getopt('', ['input:', 'batch-size:', 'dry-run', 'offset:', 'limit:', 'help']);

if (isset($options['help'])) {
    showHelp();
}

$inputFile  = $options['input'] ?? null;
$batchSize  = isset($options['batch-size']) ? (int) $options['batch-size'] : 500;
$dryRun     = isset($options['dry-run']);
$rowOffset  = isset($options['offset']) ? (int) $options['offset'] : 0;
$rowLimit   = isset($options['limit']) ? (int) $options['limit'] : 0;

if (empty($inputFile)) {
    fwrite(STDERR, "ERROR: --input is required. Use --help for usage.\n");
    exit(1);
}

if ($batchSize < 1) {
    fwrite(STDERR, "ERROR: --batch-size must be a positive integer.\n");
    exit(1);
}

// Resolve relative paths from the Flynax root
if (!file_exists($inputFile)) {
    $inputFile = $flynaxRoot . DIRECTORY_SEPARATOR . $inputFile;
}

if (!file_exists($inputFile) || !is_readable($inputFile)) {
    fwrite(STDERR, "ERROR: CSV file not found or not readable: {$inputFile}\n");
    exit(1);
}

// ============================================================================
// Database Connection
// ============================================================================

logInfo("Connecting to database at " . RL_DBHOST . ":" . (defined('RL_DBPORT') ? RL_DBPORT : 3306) . "...");

$dbPort = defined('RL_DBPORT') ? (int) RL_DBPORT : 3306;
$dbPass = defined('RL_DBPASS') ? RL_DBPASS : '';

$mysqli = @mysqli_connect(RL_DBHOST, RL_DBUSER, $dbPass, RL_DBNAME, $dbPort);

if (!$mysqli) {
    fwrite(STDERR, "ERROR: Database connection failed: " . mysqli_connect_error() . "\n");
    exit(1);
}

$mysqli->set_charset('utf8mb4');

logInfo("Database connection established.");

// ============================================================================
// Validate: Ensure solar categories exist
// ============================================================================

logInfo("Validating solar categories in {$prefix}categories...");

$catCheckSql = "SELECT `ID`, `Key` FROM `{$prefix}categories` WHERE `ID` IN ("
    . implode(',', array_values($categoryMap))
    . ")";
$catResult = $mysqli->query($catCheckSql);

if (!$catResult) {
    fwrite(STDERR, "ERROR: Failed to query categories: " . $mysqli->error . "\n");
    $mysqli->close();
    exit(1);
}

$existingCategories = [];
while ($row = $catResult->fetch_assoc()) {
    $existingCategories[(int) $row['ID']] = $row['Key'];
}
$catResult->free();

$missingCats = [];
foreach ($categoryMap as $csvCat => $catId) {
    if (!isset($existingCategories[$catId])) {
        $missingCats[] = "{$csvCat} (ID: {$catId})";
    }
}

if (!empty($missingCats)) {
    fwrite(STDERR, "ERROR: Missing solar categories in database. Run solar_setup.sql first.\n");
    fwrite(STDERR, "  Missing: " . implode(', ', $missingCats) . "\n");
    $mysqli->close();
    exit(1);
}

logInfo("All " . count($categoryMap) . " solar categories verified.");

// ============================================================================
// Open and Parse CSV
// ============================================================================

logInfo("Opening CSV: {$inputFile}");

$csvHandle = fopen($inputFile, 'r');
if (!$csvHandle) {
    fwrite(STDERR, "ERROR: Could not open CSV file: {$inputFile}\n");
    $mysqli->close();
    exit(1);
}

// Read header row
$headers = fgetcsv($csvHandle);
if ($headers === false || empty($headers)) {
    fwrite(STDERR, "ERROR: CSV file is empty or has no header row.\n");
    fclose($csvHandle);
    $mysqli->close();
    exit(1);
}

// Trim BOM and whitespace from headers
$headers = array_map(function ($h) {
    return trim($h, "\xEF\xBB\xBF \t\n\r\0\x0B");
}, $headers);

$headerIndex = array_flip($headers);

logInfo("CSV headers: " . implode(', ', $headers));

// Validate required CSV columns
$requiredColumns = ['company_name', 'primary_category', 'state', 'city'];
$missingColumns = [];
foreach ($requiredColumns as $col) {
    if (!isset($headerIndex[$col])) {
        $missingColumns[] = $col;
    }
}
if (!empty($missingColumns)) {
    fwrite(STDERR, "ERROR: CSV is missing required columns: " . implode(', ', $missingColumns) . "\n");
    fclose($csvHandle);
    $mysqli->close();
    exit(1);
}

// ============================================================================
// Count total rows for progress display
// ============================================================================

$totalRows = 0;
$savedPos = ftell($csvHandle);
while (fgetcsv($csvHandle) !== false) {
    $totalRows++;
}
fseek($csvHandle, $savedPos); // Reset to after headers

logInfo("Total data rows in CSV: {$totalRows}");

if ($rowOffset > 0) {
    logInfo("Skipping first {$rowOffset} rows (--offset).");
}
if ($rowLimit > 0) {
    logInfo("Processing at most {$rowLimit} rows (--limit).");
}
if ($dryRun) {
    logInfo("** DRY RUN MODE ** — no database changes will be made.");
}

// ============================================================================
// Skip rows if --offset is specified
// ============================================================================

for ($i = 0; $i < $rowOffset; $i++) {
    $skipped = fgetcsv($csvHandle);
    if ($skipped === false) {
        logInfo("Reached end of CSV while skipping offset rows. Nothing to import.");
        fclose($csvHandle);
        $mysqli->close();
        exit(0);
    }
}

// ============================================================================
// Main Import Loop
// ============================================================================

$stats = [
    'processed' => 0,
    'imported'  => 0,
    'skipped'   => 0,
    'errors'    => 0,
];

$batchCount     = 0;  // Rows accumulated in current batch
$batchStarted   = false;
$globalRowNum   = $rowOffset; // Tracks absolute row number in CSV

logInfo("Starting import...");
$importStartTime = microtime(true);

while (($csvRow = fgetcsv($csvHandle)) !== false) {
    // Enforce --limit
    if ($rowLimit > 0 && $stats['processed'] >= $rowLimit) {
        break;
    }

    $globalRowNum++;
    $stats['processed']++;

    // Map CSV columns to associative array
    if (count($csvRow) !== count($headers)) {
        logError("Row {$globalRowNum}: Column count mismatch (expected " . count($headers) . ", got " . count($csvRow) . "). Skipping.", $errorLogPath);
        $stats['skipped']++;
        continue;
    }

    $data = array_combine($headers, $csvRow);

    // ---------------------------------------------------------------
    // Validate row essentials
    // ---------------------------------------------------------------
    $companyName = trim($data['company_name'] ?? '');
    if (empty($companyName)) {
        logError("Row {$globalRowNum}: Empty company_name. Skipping.", $errorLogPath);
        $stats['skipped']++;
        continue;
    }

    $primaryCategory = trim($data['primary_category'] ?? '');
    if (!isset($categoryMap[$primaryCategory])) {
        logError("Row {$globalRowNum}: Unknown category '{$primaryCategory}' for '{$companyName}'. Skipping.", $errorLogPath);
        $stats['skipped']++;
        continue;
    }

    $categoryId = $categoryMap[$primaryCategory];

    // ---------------------------------------------------------------
    // Begin transaction at the start of each batch
    // ---------------------------------------------------------------
    if (!$batchStarted) {
        if (!$dryRun) {
            $mysqli->begin_transaction();
        }
        $batchStarted = true;
        $batchCount = 0;
    }

    // ---------------------------------------------------------------
    // Wrap individual row processing in a try/catch
    // ---------------------------------------------------------------
    try {
        // ==========================================================
        // (a) Create account in fl_accounts
        // ==========================================================
        $username = generateUsername($companyName);

        // Ensure username is unique (rare collision handling)
        if (!$dryRun) {
            $maxRetries = 5;
            for ($retry = 0; $retry < $maxRetries; $retry++) {
                $checkStmt = $mysqli->prepare(
                    "SELECT `ID` FROM `{$prefix}accounts` WHERE `Username` = ? LIMIT 1"
                );
                $checkStmt->bind_param('s', $username);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                $exists = $checkResult->fetch_assoc();
                $checkStmt->close();

                if (!$exists) {
                    break; // Username is available
                }

                // Regenerate
                $username = generateUsername($companyName);
            }

            if ($exists) {
                logError("Row {$globalRowNum}: Could not generate unique username for '{$companyName}' after {$maxRetries} attempts. Skipping.", $errorLogPath);
                $stats['skipped']++;
                $batchCount++;
                continue;
            }
        }

        // Generate random password (these accounts use social/email login, not password)
        $password     = bin2hex(random_bytes(16));
        $passwordHash = md5($password);

        // Extract location data from CSV
        $state     = trim($data['state'] ?? '');
        $city      = trim($data['city'] ?? '');
        $zipCode   = trim($data['zip_code'] ?? '');
        $address   = trim($data['address'] ?? '');
        $latitude  = trim($data['latitude'] ?? '');
        $longitude = trim($data['longitude'] ?? '');
        $email     = trim($data['email'] ?? '');
        $phone     = trim($data['phone'] ?? '');
        $website   = trim($data['website'] ?? '');
        $aboutMe   = trim($data['company_description'] ?? '');

        // Build the full location address string
        $locParts = array_filter([$address, $city, $state, $zipCode]);
        $locAddress = implode(', ', $locParts);

        if (!$dryRun) {
            // 20 placeholders: 19 column values as ? + about_me as ?
            // Date = NOW() and Status = 'active' are SQL literals
            $accountSql = "INSERT INTO `{$prefix}accounts` (
                `Type`, `Username`, `Password`, `Password_hash`,
                `First_name`, `Last_name`, `company_name`,
                `Mail`, `Display_email`, `phone`, `website`,
                `zip_code`, `address`,
                `Loc_latitude`, `Loc_longitude`, `Loc_address`,
                `country`, `country_level1`, `country_level2`,
                `Date`, `Status`, `about_me`
            ) VALUES (
                ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?,
                ?, ?, ?,
                ?, ?, ?,
                NOW(), 'active', ?
            )";

            $acctStmt = $mysqli->prepare($accountSql);
            if (!$acctStmt) {
                throw new RuntimeException("Prepare account INSERT failed: " . $mysqli->error);
            }

            $acctType      = 'dealer';
            $firstName     = ''; // Company accounts have no individual first/last name
            $lastName      = '';
            $displayEmail  = $email;
            $country       = 'US'; // Solar installers are US-based

            $acctStmt->bind_param(
                'ssssssssssssssssssss',
                $acctType,        // 1  Type
                $username,        // 2  Username
                $password,        // 3  Password
                $passwordHash,    // 4  Password_hash
                $firstName,       // 5  First_name
                $lastName,        // 6  Last_name
                $companyName,     // 7  company_name
                $email,           // 8  Mail
                $displayEmail,    // 9  Display_email
                $phone,           // 10 phone
                $website,         // 11 website
                $zipCode,         // 12 zip_code
                $address,         // 13 address
                $latitude,        // 14 Loc_latitude
                $longitude,       // 15 Loc_longitude
                $locAddress,      // 16 Loc_address
                $country,         // 17 country
                $state,           // 18 country_level1
                $city,            // 19 country_level2
                $aboutMe          // 20 about_me
            );

            if (!$acctStmt->execute()) {
                throw new RuntimeException("Account INSERT failed: " . $acctStmt->error);
            }
            $accountId = $acctStmt->insert_id;
            $acctStmt->close();
        } else {
            $accountId = 0; // Placeholder for dry run
        }

        // ==========================================================
        // (c) Create listing in fl_listings
        // ==========================================================
        if (!$dryRun) {
            $listingSql = "INSERT INTO `{$prefix}listings` (
                `Account_ID`, `Category_ID`, `Plan_ID`, `Plan_type`,
                `Status`, `Date`,
                `Loc_latitude`, `Loc_longitude`, `Loc_address`,
                `country`, `country_level1`, `country_level2`
            ) VALUES (
                ?, ?, ?, ?,
                'active', NOW(),
                ?, ?, ?,
                ?, ?, ?
            )";

            $listStmt = $mysqli->prepare($listingSql);
            if (!$listStmt) {
                throw new RuntimeException("Prepare listing INSERT failed: " . $mysqli->error);
            }

            $planId   = IMPORT_PLAN_ID;
            $planType = IMPORT_PLAN_TYPE;

            $listStmt->bind_param(
                'iiisssssss',
                $accountId,   // 1  Account_ID
                $categoryId,  // 2  Category_ID
                $planId,      // 3  Plan_ID
                $planType,    // 4  Plan_type
                $latitude,    // 5  Loc_latitude
                $longitude,   // 6  Loc_longitude
                $locAddress,  // 7  Loc_address
                $country,     // 8  country
                $state,       // 9  country_level1
                $city         // 10 country_level2
            );

            if (!$listStmt->execute()) {
                throw new RuntimeException("Listing INSERT failed: " . $listStmt->error);
            }
            $listingId = $listStmt->insert_id;
            $listStmt->close();
        } else {
            $listingId = 0; // Placeholder for dry run
        }

        // ==========================================================
        // (e) Insert custom field values into fl_listings_data
        // ==========================================================
        if (!$dryRun) {
            $dataInsertSql = "INSERT INTO `{$prefix}listings_data` (`Listing_ID`, `Key`, `Value`) VALUES (?, ?, ?)";
            $dataStmt = $mysqli->prepare($dataInsertSql);
            if (!$dataStmt) {
                throw new RuntimeException("Prepare listings_data INSERT failed: " . $mysqli->error);
            }

            foreach ($listingDataFieldMap as $csvCol => $flynaxKey) {
                $value = isset($data[$csvCol]) ? trim($data[$csvCol]) : '';
                if ($value === '') {
                    continue; // Skip empty values
                }

                $dataStmt->bind_param('iss', $listingId, $flynaxKey, $value);
                if (!$dataStmt->execute()) {
                    throw new RuntimeException("listings_data INSERT failed for key '{$flynaxKey}': " . $dataStmt->error);
                }
            }
            $dataStmt->close();
        }

        // ==========================================================
        // (f) Create listing package in fl_listing_packages
        // ==========================================================
        if (!$dryRun) {
            $pkgSql = "INSERT INTO `{$prefix}listing_packages` (`Account_ID`, `Plan_ID`, `Type`, `Date`) VALUES (?, ?, ?, NOW())";
            $pkgStmt = $mysqli->prepare($pkgSql);
            if (!$pkgStmt) {
                throw new RuntimeException("Prepare listing_packages INSERT failed: " . $mysqli->error);
            }

            $pkgType = IMPORT_PLAN_TYPE;
            $pkgPlanId = IMPORT_PLAN_ID;
            $pkgStmt->bind_param('iis', $accountId, $pkgPlanId, $pkgType);

            if (!$pkgStmt->execute()) {
                throw new RuntimeException("listing_packages INSERT failed: " . $pkgStmt->error);
            }
            $pkgStmt->close();
        }

        // ==========================================================
        // (g) Update account Listings_count
        // ==========================================================
        if (!$dryRun) {
            $updateCountSql = "UPDATE `{$prefix}accounts` SET `Listings_count` = `Listings_count` + 1 WHERE `ID` = ?";
            $ucStmt = $mysqli->prepare($updateCountSql);
            if (!$ucStmt) {
                throw new RuntimeException("Prepare account Listings_count UPDATE failed: " . $mysqli->error);
            }
            $ucStmt->bind_param('i', $accountId);
            if (!$ucStmt->execute()) {
                throw new RuntimeException("Account Listings_count UPDATE failed: " . $ucStmt->error);
            }
            $ucStmt->close();
        }

        // Row imported successfully
        $stats['imported']++;
        $batchCount++;

        // Progress output
        $effectiveTotal = $rowLimit > 0 ? min($rowLimit, $totalRows - $rowOffset) : ($totalRows - $rowOffset);
        $stateAbbr = strtoupper(substr($state, 0, 2));
        if ($stats['processed'] % 100 === 0 || $stats['processed'] === 1) {
            logInfo("[{$stats['processed']}/{$effectiveTotal}] Imported: {$companyName} ({$stateAbbr})");
        }

    } catch (RuntimeException $e) {
        $stats['errors']++;
        $batchCount++;
        logError("Row {$globalRowNum} ({$companyName}): " . $e->getMessage(), $errorLogPath);
    }

    // ---------------------------------------------------------------
    // Commit batch when batch size is reached
    // ---------------------------------------------------------------
    if ($batchCount >= $batchSize && $batchStarted) {
        if (!$dryRun) {
            if (!$mysqli->commit()) {
                logError("Batch commit failed: " . $mysqli->error . ". Attempting rollback.", $errorLogPath);
                $mysqli->rollback();
                // Note: stats already counted the individual rows; batch failure is logged
            }
        }
        $batchStarted = false;
        $batchCount = 0;

        // Brief progress summary at each batch boundary
        $elapsed = round(microtime(true) - $importStartTime, 1);
        logInfo("Batch committed. Processed: {$stats['processed']} | Imported: {$stats['imported']} | Errors: {$stats['errors']} | Time: {$elapsed}s");
    }
}

// ============================================================================
// Commit final partial batch
// ============================================================================
if ($batchStarted && !$dryRun) {
    if (!$mysqli->commit()) {
        logError("Final batch commit failed: " . $mysqli->error, $errorLogPath);
        $mysqli->rollback();
    }
}

fclose($csvHandle);

// ============================================================================
// Post-import: Update category counts
// ============================================================================
if (!$dryRun && $stats['imported'] > 0) {
    logInfo("Updating category listing counts...");

    $catIds = implode(',', array_values($categoryMap));
    $updateCatSql = "
        UPDATE `{$prefix}categories`
        SET `Count` = (
            SELECT COUNT(*)
            FROM `{$prefix}listings`
            WHERE `{$prefix}listings`.`Category_ID` = `{$prefix}categories`.`ID`
              AND `{$prefix}listings`.`Status` = 'active'
        )
        WHERE `ID` IN ({$catIds})
    ";

    if (!$mysqli->query($updateCatSql)) {
        logError("Category count update failed: " . $mysqli->error, $errorLogPath);
    } else {
        // Log updated counts
        $countResult = $mysqli->query(
            "SELECT `ID`, `Count` FROM `{$prefix}categories` WHERE `ID` IN ({$catIds})"
        );
        if ($countResult) {
            $catLabel = array_flip($categoryMap);
            while ($row = $countResult->fetch_assoc()) {
                $label = $catLabel[(int) $row['ID']] ?? 'unknown';
                logInfo("  Category '{$label}' (ID: {$row['ID']}): {$row['Count']} active listings");
            }
            $countResult->free();
        }
    }
}

// ============================================================================
// Final Summary
// ============================================================================
$totalTime = round(microtime(true) - $importStartTime, 2);
$rps = $stats['processed'] > 0 ? round($stats['processed'] / $totalTime, 1) : 0;

echo "\n";
echo "=============================================================\n";
echo "  IMPORT COMPLETE" . ($dryRun ? " (DRY RUN)" : "") . "\n";
echo "=============================================================\n";
echo "  Total processed:  {$stats['processed']}\n";
echo "  Imported:         {$stats['imported']}\n";
echo "  Skipped:          {$stats['skipped']}\n";
echo "  Errors:           {$stats['errors']}\n";
echo "  Time elapsed:     {$totalTime}s\n";
echo "  Throughput:       {$rps} rows/sec\n";
if ($stats['errors'] > 0) {
    echo "  Error log:        {$errorLogPath}\n";
}
echo "=============================================================\n";

// ============================================================================
// Cleanup
// ============================================================================
$mysqli->close();

exit($stats['errors'] > 0 ? 2 : 0);
