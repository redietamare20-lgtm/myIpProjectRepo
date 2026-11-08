<?php
// Database settings (XAMPP default: user = root, no password)
define('DB_HOST', 'localhost');
define('DB_NAME', 'online_bookstore');
define('DB_USER', 'root');
define('DB_PASS', '');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER, DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            error_log($e->getMessage());
            http_response_code(500);
            die('Database connection failed. Is MySQL running and is the database imported?');
        }
    }
    return $pdo;
}
