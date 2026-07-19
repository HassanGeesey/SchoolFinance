<?php
session_start();

define('DB_HOST', 'localhost');
define('DB_NAME', 'english_institute');
define('DB_USER', 'root');
define('DB_PASS', '');

function getDB() {
    static $db = null;
    if ($db === null) {
        try {
            $db = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }
    return $db;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return true;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function getSetting($key, $default = '') {
    static $cache = null;
    if ($cache === null) {
        $db = getDB();
        $cache = [];
        foreach ($db->query("SELECT setting_key, setting_value FROM settings")->fetchAll() as $row) {
            $cache[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $cache[$key] ?? $default;
}

function getAppName() {
    return getSetting('institute_name', 'English Institute');
}

function getAppLogo() {
    $logo = getSetting('logo_filename', '');
    if ($logo && file_exists(__DIR__ . '/uploads/' . $logo)) {
        return 'uploads/' . $logo;
    }
    return null;
}