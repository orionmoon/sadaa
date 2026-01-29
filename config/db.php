<?php
/**
 * Sadaa (صدى) - Database Configuration
 *
 * Update these settings to match your MySQL server configuration.
 */

// Include internationalization system
require_once __DIR__ . '/i18n.php';

// Database connection settings
define('DB_HOST', 'localhost');
define('DB_NAME', 'sadaa');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Create PDO connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
} catch (PDOException $e) {
    // In production, log this error instead of displaying it
    die("Database connection failed: " . $e->getMessage());
}

/**
 * Get a database connection (singleton pattern)
 */
function getDatabase(): PDO {
    global $pdo;
    return $pdo;
}

/**
 * Helper function to get a setting value
 */
function getSetting(string $key, $default = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

/**
 * Helper function to get active languages
 */
function getActiveLanguages(): array {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM languages WHERE is_active = 1 ORDER BY sort_order ASC");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Helper function to get JSON value in specified language
 */
function getLocalizedValue($json, string $lang = 'fr', string $fallback = 'ar') {
    if (is_string($json)) {
        $json = json_decode($json, true);
    }
    if (!is_array($json)) {
        return '';
    }
    return $json[$lang] ?? $json[$fallback] ?? $json['en'] ?? reset($json) ?: '';
}
