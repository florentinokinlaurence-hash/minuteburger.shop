<?php
// Start session only if it hasn't been started yet
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
 * Configuration - DB connection
 * Edit these constants if you use a different DB user/pass.
 */
define('DB_HOST','127.0.0.1');
define('DB_NAME','minuteburger');
define('DB_USER','root');
define('DB_PASS','toor'); // xampp default empty

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    die("DB connection failed: " . $e->getMessage());
}

/* Helpers */
function is_logged_in() {
    return isset($_SESSION['user']);
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: index.php');
        exit;
    }
}
