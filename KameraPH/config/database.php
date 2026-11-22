<?php
if (php_sapi_name() !== 'cli') {
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    if (!ob_get_level()) {
        @ob_start();
    }
}

/**
 * Standardized Database Class for Azure & Cross-module integration
 */
class Database {
    private static ?PDO $pdo = null;

    public static function connect(): PDO {
        if (self::$pdo === null) {
            $c = require __DIR__ . '/config.php';
            $port = $c['port'] ?? 3306;
            $charset = $c['charset'] ?? 'utf8mb4';
            $dsn = "mysql:host={$c['host']};port={$port};dbname={$c['dbname']};charset={$charset}";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT            => 10,
            ];
            if (!empty($c['ssl_ca']) && file_exists($c['ssl_ca'])) {
                $options[PDO::MYSQL_ATTR_SSL_CA] = $c['ssl_ca'];
            } elseif (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
            }
            self::$pdo = new PDO($dsn, $c['user'], $c['pass'], $options);
        }
        return self::$pdo;
    }
}

/**
 * Global mysqli connection ($conn) for existing KameraPH web app compatibility
 */
if (!isset($conn)) {
    $c = require __DIR__ . '/config.php';
    $host = $c['host'] ?? 'localhost';
    $port = (int)($c['port'] ?? 3306);
    $database = $c['dbname'] ?? 'inventory';
    $username = $c['user'] ?? 'root';
    $password = $c['pass'] ?? '';

    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = mysqli_init();
    $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 3);

    $connected = false;
    if ($host !== 'localhost' && $host !== '127.0.0.1') {
        if (defined('MYSQLI_CLIENT_SSL')) {
            $connected = @$conn->real_connect($host, $username, $password, $database, $port, null, MYSQLI_CLIENT_SSL);
        } else {
            $connected = @$conn->real_connect($host, $username, $password, $database, $port);
        }
    }

    if (!$connected) {
        $conn = @new mysqli("localhost", "root", "", "kph-database", 3306);
        if ($conn->connect_error) {
            $conn = @new mysqli("localhost", "root", "", "inventory", 3306);
        }
    }

    if ($conn && !$conn->connect_error) {
        $conn->set_charset("utf8mb4");
    }

    require_once __DIR__ . '/../includes/functions.php';
}
