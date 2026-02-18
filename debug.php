<?php
/**
 * Diagnostic script — progressively loads each part of the Flynax bootstrap
 * to pinpoint where the application fails.
 *
 * Access: https://findsolarinstallers.xyz/debug.php
 * DELETE THIS FILE after debugging is complete.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');
set_time_limit(30);

header('Content-Type: text/plain; charset=utf-8');

function step($n, $desc) {
    echo "[STEP {$n}] {$desc} ... ";
    flush();
}
function ok($extra = '') {
    echo "OK" . ($extra ? " ({$extra})" : "") . "\n";
    flush();
}
function fail($msg) {
    echo "FAIL: {$msg}\n";
    flush();
}

echo "=== Flynax Bootstrap Diagnostic ===\n";
echo "PHP " . PHP_VERSION . " | " . php_sapi_name() . "\n";
echo "Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "\n";
echo "Host: " . ($_SERVER['HTTP_HOST'] ?? 'N/A') . "\n";
echo "X-Forwarded-Proto: " . ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'not set') . "\n";
echo "Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n\n";

// Step 1: Config file
step(1, 'Loading config.inc.php');
$configFile = __DIR__ . '/includes/config.inc.php';
if (!file_exists($configFile)) {
    fail("File not found: {$configFile}");
    exit(1);
}
require_once $configFile;
ok("RL_DBHOST=" . RL_DBHOST . ", RL_DBNAME=" . RL_DBNAME);

// Step 2: Key constants
step(2, 'Checking constants');
$constants = ['RL_DIR', 'RL_ROOT', 'RL_INC', 'RL_CLASSES', 'RL_LIBS', 'RL_TMP', 'RL_CACHE', 'RL_URL_HOME'];
$missing = [];
foreach ($constants as $c) {
    if (!defined($c)) $missing[] = $c;
}
if ($missing) {
    fail("Missing constants: " . implode(', ', $missing));
} else {
    ok("RL_URL_HOME=" . RL_URL_HOME);
}

// Step 3: Key directories
step(3, 'Checking directories');
$dirs = [
    'RL_INC'     => RL_INC,
    'RL_CLASSES' => RL_CLASSES,
    'RL_LIBS'    => RL_LIBS,
    'RL_TMP'     => RL_TMP,
    'RL_CACHE'   => RL_CACHE,
];
$issues = [];
foreach ($dirs as $name => $path) {
    if (!is_dir($path)) $issues[] = "{$name}={$path} (NOT FOUND)";
    elseif (!is_writable($path) && in_array($name, ['RL_TMP', 'RL_CACHE'])) $issues[] = "{$name}={$path} (NOT WRITABLE)";
}
if ($issues) {
    fail(implode('; ', $issues));
} else {
    ok();
}

// Step 4: Vendor autoload
step(4, 'Loading vendor/autoload.php');
$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if ($autoload === '/vendor/autoload.php') {
    $autoload = __DIR__ . '/vendor/autoload.php';
}
// The actual path used in control.inc.php
$autoload2 = dirname(__DIR__) . '/vendor/autoload.php';
$autoload3 = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoload3)) {
    require_once $autoload3;
    ok($autoload3);
} elseif (file_exists($autoload2)) {
    require_once $autoload2;
    ok($autoload2);
} else {
    fail("Not found at {$autoload3} or {$autoload2}");
}

// Step 5: DB classes
step(5, 'Loading DB classes');
require_once RL_CLASSES . 'dbi.class.php';
require_once RL_CLASSES . 'rlDb.class.php';
ok();

// Step 6: Reefless class
step(6, 'Loading reefless class');
require_once RL_CLASSES . 'reefless.class.php';
ok();

// Step 7: Instantiate
step(7, 'Creating rlDb + reefless instances');
$rlDb = new rlDb();
$reefless = new reefless();
ok();

// Step 8: DB connection
step(8, 'Connecting to database');
$reefless->connect(RL_DBHOST, RL_DBPORT, RL_DBUSER, RL_DBPASS, RL_DBNAME);
ok();

// Step 9: Test query
step(9, 'Running test query');
$result = $rlDb->getRow("SELECT COUNT(*) AS cnt FROM `" . RL_DBPREFIX . "config`");
ok("Config rows: " . ($result['cnt'] ?? 'N/A'));

// Step 10: Session
step(10, 'Starting session');
$reefless->sessionStart();
ok("Session ID: " . session_id());

// Step 11: Load core classes
step(11, 'Loading Debug class');
$reefless->loadClass('Debug');
ok();

step(12, 'Loading Config class');
$reefless->loadClass('Config');
ok();

step(13, 'Loading Lang class');
$reefless->loadClass('Lang');
ok();

step(14, 'Loading Valid class');
$reefless->loadClass('Valid');
ok();

step(15, 'Loading Hook class');
$reefless->loadClass('Hook');
ok();

step(16, 'Loading Custom class');
$reefless->loadClass('Custom');
ok();

step(17, 'Loading Listings class');
$reefless->loadClass('Listings');
ok();

step(18, 'Loading Categories class');
$reefless->loadClass('Categories');
ok();

step(19, 'Loading Cache class');
$reefless->loadClass('Cache');
ok();

// Step 20: xajax
step(20, 'Loading xajax library');
require_once RL_LIBS . 'ajax' . RL_DS . 'xajax_core' . RL_DS . 'xajax.inc.php';
$rlXajax = new xajax();
$_response = new xajaxResponse();
ok();

// Step 21: Config
step(21, 'Loading allConfig()');
$config = $rlConfig->allConfig();
ok("Template: " . ($config['template'] ?? 'N/A') . ", mod_rewrite: " . ($config['mod_rewrite'] ?? 'N/A'));

// Step 22: Smarty
step(22, 'Loading Smarty library');
require_once RL_LIBS . 'smarty' . RL_DS . 'Smarty.class.php';
ok();

step(23, 'Loading rlSmarty class');
$reefless->loadClass('Smarty');
ok("template_dir: " . $rlSmarty->template_dir . ", compile_dir: " . $rlSmarty->compile_dir);

// Step 24: Check template dir
step(24, 'Checking template directory');
if (is_dir($rlSmarty->template_dir)) {
    ok($rlSmarty->template_dir);
} else {
    fail("Template dir not found: " . $rlSmarty->template_dir);
}

// Step 25: Check compile dir writable
step(25, 'Checking compile directory writable');
if (is_writable($rlSmarty->compile_dir)) {
    ok($rlSmarty->compile_dir);
} else {
    fail("Compile dir not writable: " . $rlSmarty->compile_dir);
}

// Step 26: Plugins
step(26, 'Loading installed plugins list');
$plugins = $rlCommon->getInstalledPluginsList();
ok("Plugins: " . count($plugins));

// Step 27: Static class
step(27, 'Loading Static class');
$reefless->loadClass('Static');
ok();

// Step 28: Navigator
step(28, 'Loading Navigator class');
$reefless->loadClass('Navigator');
ok();

// Step 29: baseUrlRedirect
step(29, 'Testing baseUrlRedirect (would redirect?)');
$host_s = $rlValid->getDomain(RL_URL_HOME);
$host_r = $_SERVER['HTTP_HOST'] ?? 'unknown';
echo "\n  System host: {$host_s}\n  Request host: {$host_r}\n  ";
$isHttps = $reefless->isHttps();
echo "isHttps: " . ($isHttps ? 'true' : 'false') . "\n  ";
echo "RL_URL_HOME starts with https: " . (strpos(RL_URL_HOME, 'https') === 0 ? 'yes' : 'no') . "\n  ";
if (strpos(RL_URL_HOME, 'https') === 0 && !$isHttps) {
    echo "WARNING: HTTPS mismatch — would trigger redirect loop!\n  ";
}
ok();

echo "\n=== All steps passed! The Flynax bootstrap loaded successfully. ===\n";
echo "If this works but the main site doesn't, the issue is in index.php routing or template rendering.\n";
