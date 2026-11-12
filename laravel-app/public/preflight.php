<?php
declare(strict_types=1);

/**
 * Temporary, read-only compatibility check for the DIT Laravel staging host.
 * Does not read credentials, environment variables, phpinfo(), or database data.
 * Delete this file after the hosting checks are complete.
 */
header('Content-Type: text/plain; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

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

echo "STIT DARUL ILMI — LARAVEL 13 PREFLIGHT\n";
echo "PHP version: {$phpVersion} — " . ($versionOk ? 'PASS (>= 8.3)' : 'FAIL (requires >= 8.3)') . "\n";
echo "\nRequired PHP extensions:\n";

foreach ($required as $extension => $label) {
    $loaded = extension_loaded($extension);
    if (!$loaded) {
        $missing[] = $label;
    }
    echo " - {$label}: " . ($loaded ? 'PASS' : 'MISSING') . "\n";
}

$pdoMysqlOk = extension_loaded('pdo')
    && extension_loaded('pdo_mysql')
    && in_array('mysql', PDO::getAvailableDrivers(), true);

echo "\nPDO MySQL driver: " . ($pdoMysqlOk ? 'PASS' : 'MISSING') . "\n";

$projectRoot = dirname(__DIR__);
$runtimeDirs = [
    'storage' => $projectRoot . '/storage',
    'bootstrap/cache' => $projectRoot . '/bootstrap/cache',
];

echo "\nLaravel runtime directory status:\n";
$unwritable = false;
foreach ($runtimeDirs as $label => $path) {
    if (!is_dir($path)) {
        echo " - {$label}: NOT CREATED (will be checked after Laravel files are installed)\n";
        continue;
    }

    $writable = is_writable($path);
    if (!$writable) {
        $unwritable = true;
    }
    echo " - {$label}: " . ($writable ? 'WRITABLE' : 'NOT WRITABLE') . "\n";
}

$checksOk = $versionOk && $missing === [] && $pdoMysqlOk && !$unwritable;
echo "\nPHP / extension result: " . ($checksOk ? 'PASS' : 'FAIL — do not install Laravel until issues are fixed') . "\n";
echo "Separate manual checks still required: subdomain document root, HTTPS, Composer/build availability, and FTP target isolation.\n";
echo "\nSecurity: no database connection or secrets were accessed. Delete this temporary file after testing.\n";
