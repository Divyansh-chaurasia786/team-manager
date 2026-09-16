<?php

if (isset($_GET['health'])) {
    header('Content-Type: text/plain');
    echo "PHP_SERVERLESS_OK: " . PHP_VERSION;
    exit;
}

// Ensure /tmp directory structure exists for serverless runtime
$dirs = [
    '/tmp/storage/framework/views',
    '/tmp/storage/framework/cache',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/logs',
    '/tmp/database',
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Copy initial database.sqlite to /tmp if it doesn't exist
$tmpDb = '/tmp/database/database.sqlite';
$srcDb = __DIR__ . '/../database/database.sqlite';
if (!file_exists($tmpDb)) {
    if (file_exists($srcDb)) {
        copy($srcDb, $tmpDb);
    } else {
        touch($tmpDb);
    }
}
putenv("DB_DATABASE={$tmpDb}");
$_ENV['DB_DATABASE'] = $tmpDb;
$_SERVER['DB_DATABASE'] = $tmpDb;

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

try {
    // Forward to public index.php
    require __DIR__ . '/../public/index.php';
} catch (\Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain');
    echo "SERVERLESS BOOTSTRAP EXCEPTION:\n";
    echo $e->getMessage() . "\n";
    echo $e->getFile() . " on line " . $e->getLine() . "\n\n";
    echo $e->getTraceAsString();
}
