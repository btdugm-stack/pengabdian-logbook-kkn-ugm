<?php
require_once __DIR__ . '/config.php';

function db() {
    static $pdo;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            die('<div style="font-family:Arial;padding:24px"><h2>Koneksi database gagal</h2><p>' . htmlspecialchars($e->getMessage()) . '</p><p>Pastikan database <b>' . DB_NAME . '</b> sudah dibuat dan file <b>database.sql</b> sudah diimport.</p></div>');
        }
    }
    return $pdo;
}

function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function current_user() {
    if (!isset($_SESSION['user_id'])) return null;
    $stmt = db()->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function require_login() {
    if (!current_user()) redirect('index.php?page=login');
}

function flash($message = null, $type = 'success') {
    if ($message !== null) {
        $_SESSION['flash'] = ['message' => $message, 'type' => $type];
        return;
    }
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        $class = $f['type'] === 'error' ? 'alert-error' : 'alert-success';
        echo '<div class="alert ' . $class . '">' . e($f['message']) . '</div>';
    }
}

function get_or_create_master($table, $name, $extra = []) {
    $name = trim((string)$name);
    if ($name === '') return null;

    $allowed = ['themes', 'programs', 'activity_types', 'locations'];
    if (!in_array($table, $allowed, true)) throw new Exception('Invalid master table');

    $stmt = db()->prepare("SELECT id FROM `$table` WHERE name = ?");
    $stmt->execute([$name]);
    $row = $stmt->fetch();
    if ($row) return (int)$row['id'];

    if ($table === 'locations') {
        $stmt = db()->prepare("INSERT INTO locations(name, latitude, longitude, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$name, $extra['latitude'] ?? null, $extra['longitude'] ?? null]);
    } else {
        $stmt = db()->prepare("INSERT INTO `$table`(name, created_at) VALUES (?, NOW())");
        $stmt->execute([$name]);
    }
    return (int)db()->lastInsertId();
}

function master_options($table) {
    $allowed = ['themes', 'programs', 'activity_types', 'locations'];
    if (!in_array($table, $allowed, true)) return [];
    return db()->query("SELECT * FROM `$table` ORDER BY name ASC")->fetchAll();
}

function parse_coord($coord) {
    $coord = trim((string)$coord);
    if (!$coord) return [null, null];
    $parts = array_map('trim', explode(',', $coord));
    if (count($parts) !== 2) return [null, null];
    return [is_numeric($parts[0]) ? (float)$parts[0] : null, is_numeric($parts[1]) ? (float)$parts[1] : null];
}
?>
