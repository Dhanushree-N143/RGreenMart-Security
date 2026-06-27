<?php
// ==========================
// SECURITY HEADERS
// ==========================
if (!headers_sent()) {
    header("X-Frame-Options: SAMEORIGIN");
    header("X-Content-Type-Options: nosniff");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; font-src 'self' https://cdnjs.cloudflare.com; img-src 'self' data: https:; connect-src 'self'; frame-ancestors 'self';");
}

// ==========================
// LOAD .env VARIABLES
// ==========================
function loadEnv()
{
    $path = __DIR__ . '/.env';

    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

loadEnv();


// ==========================
// PHP ERROR SETTINGS
// ==========================

// Don't display errors to users
ini_set('display_errors', 0);

// Enable logging
ini_set('log_errors', 1);

// Log file
ini_set('error_log', __DIR__ . '/logs/error.log');

// Report all errors
error_reporting(E_ALL);

// ==========================
// DATABASE CONNECTION (PDO)
// ==========================
try {

    $conn = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4",
        $_ENV['DB_USER'],
        $_ENV['DB_PASS']
    );

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {

    error_log("PDO Connection Error: " . $e->getMessage());

    exit("Something went wrong. Please try again later.");
}

// ==========================
// DATABASE CONNECTION (MySQLi)
// ==========================

$mysqli = @mysqli_connect(
    $_ENV['DB_HOST'],
    $_ENV['DB_USER'],
    $_ENV['DB_PASS'],
    $_ENV['DB_NAME']
);

if (!$mysqli) {

    error_log("MySQLi Connection Error: " . mysqli_connect_error());

    exit("Something went wrong. Please try again later.");
}

// ==========================
// SMTP CONSTANTS
// ==========================
define('SMTP_MAIL', $_ENV['SMTP_MAIL']);
define('SMTP_PASSWORD', $_ENV['SMTP_PASSWORD']);
define('MAIL_HOST', $_ENV['MAIL_HOST']);

define(
    'WHATSAPP_DEFAULT_PHONE',
    $_ENV['WHATSAPP_DEFAULT_PHONE'] ?? '919655562772'
);

define(
    'WHATSAPP_DEFAULT_MESSAGE',
    $_ENV['WHATSAPP_DEFAULT_MESSAGE'] ?? 'Hello RGreenmart, I would like more information.'
);
?>