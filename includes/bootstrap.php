<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Colombo');
ini_set('session.use_strict_mode', '1');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
session_start();
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");

function e(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function db(): PDO {
    static $pdo;
    if (!$pdo) {
        $c = require __DIR__ . '/../config/database.php';
        $pdo = new PDO("mysql:host={$c['host']};port={$c['port']};dbname={$c['name']};charset=utf8mb4", $c['user'], $c['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
    }
    return $pdo;
}
function token(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function csrf(): void {
    if (!is_string($_POST['csrf'] ?? null) || !hash_equals(token(), $_POST['csrf'])) { http_response_code(403); exit('Invalid form token. Return to the form and try again.'); }
}
function redirect(string $url): never { header('Location: ' . $url, true, 303); exit; }
function input(array $source, string $key): string { return is_string($source[$key] ?? null) ? trim($source[$key]) : ''; }
function item_id(): int {
    $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$id) { http_response_code(404); exit('Item not found.'); }
    return $id;
}
function find_item(int $id): array {
    $q = db()->prepare('SELECT items.*, categories.name AS category_name FROM items JOIN categories ON categories.id = items.category_id WHERE items.id = ?');
    $q->execute([$id]);
    $item = $q->fetch();
    if (!$item) { http_response_code(404); exit('Item not found.'); }
    return $item;
}
function validate_item(array $source, array $categoryIds): array {
    $data = []; $errors = [];
    foreach (['name' => 120, 'location' => 160, 'reporter_name' => 100, 'contact' => 160] as $key => $max) {
        $data[$key] = input($source, $key);
        if ($data[$key] === '' || mb_strlen($data[$key]) > $max) $errors[$key] = 'Required; maximum ' . $max . ' characters.';
    }
    $data['description'] = input($source, 'description');
    if (mb_strlen($data['description']) > 3000) $errors['description'] = 'Maximum 3000 characters.';
    $data['type'] = input($source, 'type');
    if (!in_array($data['type'], ['lost', 'found'], true)) $errors['type'] = 'Select lost or found.';
    $data['category_id'] = input($source, 'category_id');
    if (!in_array($data['category_id'], array_map('strval', $categoryIds), true)) $errors['category_id'] = 'Select a valid category.';
    $data['event_date'] = is_string($source['event_date'] ?? null) ? $source['event_date'] : '';
    $date = preg_match('/^\d{4}-\d{2}-\d{2}$/D', $data['event_date']) ? DateTimeImmutable::createFromFormat('!Y-m-d', $data['event_date']) : false;
    if (!$date || $date->format('Y-m-d') !== $data['event_date'] || $data['event_date'] > date('Y-m-d') || $data['event_date'] < '1900-01-01') $errors['event_date'] = 'Enter a valid date between 1900-01-01 and today.';
    return [$data, $errors];
}
set_exception_handler(function (Throwable $error): void {
    error_log((string) $error);
    http_response_code(500);
    echo '<h1>Unable to complete this request</h1><p>Please try again later. If this continues, check the database setup.</p><a href="index.php">Return to reports</a>';
});
