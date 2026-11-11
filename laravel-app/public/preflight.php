<?php
declare(strict_types=1);

/**
 * Safe hosting preflight for the isolated DIT Laravel staging directory.
 * Does not read environment variables, database credentials, or phpinfo().
 * Remove this file after the hosting checks are complete.
 */
header('Content-Type: text/plain; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, max-age=0');

$required = [
    'ctype' => 'Ctype',
    'curl' => 'cURL',
    'dom' => 'DOM',
    'fileinfo' => 'Fileinfo',
    'filter' => 'Filter',
    'hash' => 'Hash',
    'mbstring' => 'Mbstring',
    'openssl' => 'OpenSSL',
    'pcre' => 'PCRE',
    'pdo' => 'PDO',
    'session' => 'Session',
    'tokenizer' => 'Tokenizer',
    'xml' => 'XML',
    'pdo_mysql' => 'PDO MySQL',
];

$phpVersion = PHP_VERSION;
$versionOk = version_compare($phpVersion, '8.3.0', '>=');
$missing = [];
foreach ($required as $extension => $label) {
    if (!extension_loaded($extension)) {
        $missing[] = $label;
    }
}

$projectRoot = dirname(__DIR__);
$runtimeDirs = [
    'storage' => $projectRoot . '/storage',
    'bootstrap/cache' => $projectRoot . '/bootstrap/cache',
];

$dirResults = [];
foreach ($runtimeDirs as $label => $path) {
    if (!is_dir($path)) {
        $dirResults[$label] = 'NOT CREATED';
    } else {
        $dirResults[$label] = is_writable($path) ? 'WRITABLE' : 'NOT WRITABLE';
    }
}

$pdoMysqlOk = extension_loaded('pdo')
    && extension_loaded('pdo_mysql')
    && in_array('mysql', PDO::getAvailableDrivers(), true);

$ok = $versionOk && $missing === [] && $pdoMysqlOk;
foreach ($dirResults as $status) {
    if ($status === 'NOT WRITABLE') {
        $ok = false;
    }
}

echo "DIT LARAVEL HOSTING PREFLIGHT\n";
echo "PHP version: " . $phpVersion . " — " . ($versionOk ? 'PASS (>= 8.3)' : 'FAIL (requires >= 8.3)') . "\n";
echo "Required extensions:\n";
foreach ($required as $extension => $label) {
    echo " - " . $label . ': ' . (extension_loaded($extension) ? 'PASS' : 'MISSING') . "\n";
}
echo "PDO MySQL driver: " . ($pdoMysqlOk ? 'PASS' : 'MISSING') . "\n";
echo "Runtime directories:\n";
foreach ($dirResults as $label => $status) {
    echo " - " . $label . ': ' . $status . "\n";
}
echo "Overall: " . ($ok ? 'PHP/extension checks passed; verify document root and Composer separately.' : 'FAIL — do not deploy Laravel yet.') . "\n";
echo "\nSecurity: this report intentionally omits server paths, environment values, and database credentials. Delete preflight.php after testing.\n";
