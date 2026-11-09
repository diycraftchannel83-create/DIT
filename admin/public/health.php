<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Content-Type-Options: nosniff');

$backend = dirname(__DIR__, 2) . '/backend';
$configFile = $backend . '/config/database.local.php';
$result = [
    'ok' => false,
    'php' => PHP_VERSION,
    'database_config' => is_file($configFile) ? 'found' : 'missing',
    'database' => 'not_checked',
    'message' => 'Pemeriksaan server STIT Darul Ilmi'
];

if (is_file($configFile)) {
    try {
        $cfg = require $configFile;
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $cfg['host'], $cfg['database'], $cfg['charset'] ?? 'utf8mb4');
        $pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->query('SELECT 1');
        $result['database'] = 'connected';
        $result['ok'] = true;
        $result['message'] = 'Server dan koneksi database siap.';
    } catch (Throwable $e) {
        error_log('[STIT HEALTH] ' . $e->getMessage());
        $result['database'] = 'failed';
        $result['message'] = 'Konfigurasi ditemukan, tetapi koneksi database gagal. Periksa host, nama database, username, password, dan izin MySQL.';
    }
} else {
    $result['message'] = 'File backend/config/database.local.php tidak ditemukan pada server. Upload file konfigurasi database ke folder backend/config/.';
}

http_response_code($result['ok'] ? 200 : 503);
echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
