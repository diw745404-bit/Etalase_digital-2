<?php
declare(strict_types=1);

$databaseName = getenv('DB_NAME') ?: 'db_stella';
if (!preg_match('/\A[a-zA-Z0-9_]+\z/', $databaseName)) {
    throw new RuntimeException('Nama database hanya boleh berisi huruf, angka, dan garis bawah.');
}

return [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => (int)(getenv('DB_PORT') ?: 3306),
    'user' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: '',
    'name' => $databaseName,
];
