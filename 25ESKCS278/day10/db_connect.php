<?php
declare(strict_types=1);

/**
 * db_connect.php
 * ------------------------------------------------------------
 * Establishes a secure PDO connection to the MySQL database and
 * defines the admin credentials used for the dashboard login.
 *
 * SECURITY NOTES:
 * - PDO::ATTR_EMULATE_PREPARES is disabled so real prepared
 *   statements are used (best protection against SQL injection).
 * - PDO::ERRMODE_EXCEPTION ensures failures surface as catchable
 *   exceptions instead of silent failures.
 * - Never expose $e->getMessage() to end users in production.
 * ------------------------------------------------------------
 */

// ---------------------------------------------
// Database configuration — update for your environment
// ---------------------------------------------
$DB_HOST    = 'localhost';
$DB_NAME    = 'student_management';
$DB_USER    = 'root';
$DB_PASS    = '';
$DB_CHARSET = 'utf8mb4';

// ---------------------------------------------
// Admin credentials
// ---------------------------------------------
// In production, generate this hash ONCE offline with:
//   php -r "echo password_hash('YourStrongPassword', PASSWORD_DEFAULT);"
// then paste the resulting string below and remove the fallback call.
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD_HASH', password_hash('Admin@123', PASSWORD_DEFAULT));
// Default demo login -> username: admin | password: Admin@123

$dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset={$DB_CHARSET}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
    error_log('Database Connection Error: ' . $e->getMessage());
    http_response_code(500);
    die('Unable to connect to the database. Please try again later.');
}