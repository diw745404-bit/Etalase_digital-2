<?php
declare(strict_types=1);

$databaseConfig = require __DIR__ . '/../config/database.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$connect = static function (string $database) use ($databaseConfig): mysqli {
    return new mysqli(
        $databaseConfig['host'],
        $databaseConfig['user'],
        $databaseConfig['password'],
        $database,
        $databaseConfig['port']
    );
};

try {
    $connection = $connect($databaseConfig['name']);
} catch (mysqli_sql_exception $exception) {
    if ($exception->getCode() !== 1049) {
        throw $exception;
    }

    $connection = $connect('');
    $connection->query(
        "CREATE DATABASE `{$databaseConfig['name']}`
         CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
    );
    $connection->select_db($databaseConfig['name']);
}

$connection->set_charset('utf8mb4');
