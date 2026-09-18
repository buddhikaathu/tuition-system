<?php
/**
 * Central configuration file.
 * Edit DB_HOST / DB_NAME / DB_USER / DB_PASS to match your hosting/XAMPP setup.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'tuition_system');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');

define('APP_NAME', 'Tuition Class Manager');

// Base URL of the app WITHOUT trailing slash.
// Example (XAMPP):        http://localhost/tuition-system
// Example (live hosting): https://yourdomain.com/tuition-system
define('BASE_URL', 'http://localhost/tuition-system');

date_default_timezone_set('Asia/Colombo');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed. Please check config.php. (' . $e->getMessage() . ')');
}
