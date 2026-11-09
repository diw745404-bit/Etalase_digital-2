<?php
declare(strict_types=1);

$schema = file_get_contents(__DIR__ . '/schema.sql');
if ($schema === false) {
    throw new RuntimeException('File database/schema.sql tidak dapat dibaca.');
}

$statements = preg_split('/;\s*(?:\R|$)/', trim($schema));
if ($statements === false) {
    throw new RuntimeException('Skema database tidak dapat diproses.');
}

foreach ($statements as $statement) {
    $statement = trim($statement);
    if ($statement !== '') {
        $connection->query($statement);
    }
}
