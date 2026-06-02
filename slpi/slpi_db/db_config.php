<?php
// ============================================================
//  SLPI Workshop Hub — db_config.php
//  Shared database connection. Include this in every PHP file.
//  Place outside your web root for security, or restrict
//  access via .htaccess.
// ============================================================

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'slpi_db');
define('DB_USER', 'slpi_user');       // ← change to your MySQL user
define('DB_PASS', 'yourpassword');    // ← change to your MySQL password
define('DB_CHARSET', 'utf8mb4');

/**
 * Returns a singleton PDO connection.
 */
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['error' => 'Database connection failed.']));
        }
    }
    return $pdo;
}

/**
 * Helper: return JSON and exit.
 */
function respond(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Helper: get posted JSON body as array.
 */
function body(): array {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}
