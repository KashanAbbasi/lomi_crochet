<?php
session_start();

// Editable placeholders
const WEBSITE_NAME = '[WEBSITE_NAME]';
const PRODUCT_TYPE = '[PRODUCT_TYPE]';
const CURRENCY = '[CURRENCY]';
const SUPABASE_URL = '[SUPABASE_URL]';
const SUPABASE_API_KEY = '[SUPABASE_API_KEY]';

// Supabase Postgres connection
$host = 'db.gffplywffwpllpbzchnm.supabase.co';
$dbname = 'postgres';
$user = 'postgres';
$password = 'Kashanabbasi1_2';
$port = '5432';

try {
    $conn = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $exception) {
    http_response_code(500);
    die('Database connection failed: ' . $exception->getMessage());
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool {
    return isset($_SESSION['admin_id']);
}

function jsonResponse(array $payload, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

function sanitizeString(?string $value): string {
    return trim(filter_var((string) $value, FILTER_SANITIZE_SPECIAL_CHARS));
}
?>
