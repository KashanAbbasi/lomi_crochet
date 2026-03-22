<?php
require_once __DIR__ . '/../config.php';

$sql = file_get_contents(__DIR__ . '/sql_schema.sql');
$conn->exec($sql);
echo "Database initialized successfully.";
?>
