<?php
// Centralized Database Connection and Helper Functions for Portalia
// Database name: portaliadb

$sessionPath = __DIR__ . '/sessions';
if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0777, true);
}
session_save_path($sessionPath);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simple .env parser
function loadEnv($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}
loadEnv(__DIR__ . '/.env');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        $required = ['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'];
        $missing = [];
        foreach ($required as $var) {
            if (getenv($var) === false || getenv($var) === '') {
                $missing[] = $var;
            }
        }
        if (!empty($missing)) {
            die("Database configuration error: missing required environment variable(s): " . implode(', ', $missing));
        }

        try {
            $host     = getenv('DB_HOST');
            $port     = getenv('DB_PORT');
            $dbname   = getenv('DB_NAME');
            $user     = getenv('DB_USER');
            $password = getenv('DB_PASSWORD');

            $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, $user, $password, $options);
        } catch (PDOException $e) {
            die("Database Connection Failed: " . $e->getMessage());
        }
    }
    return $pdo;
}

// Authentication Helpers
function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function requireAuth() {
    if (!isAuthenticated()) {
        header("Location: welcome.php");
        exit;
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header("Location: ../marketplace/login.php");
        exit;
    }
}

function getCurrentUser() {
    if (!isAuthenticated()) return null;
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Spacing & Security Helpers
function sanitize($value) {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

// Currency Formatter
function formatRupiah($value) {
    return "Rp " . number_format($value, 0, ',', '.');
}

// Unread Chat Counter
function getUnreadChatCount($userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM chat_messages WHERE receiver_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    $res = $stmt->fetch();
    return $res ? $res['count'] : 0;
}

// Safe Image Upload File
function uploadProductImage($file) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        return null;
    }

    // Check size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        return null;
    }

    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('prod_', true) . '.' . $ext;
    $targetFilePath = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
        return 'uploads/' . $filename;
    }

    return null;
}
?>
