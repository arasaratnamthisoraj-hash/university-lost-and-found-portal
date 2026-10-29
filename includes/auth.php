<?php
declare(strict_types=1);

function fail(int $status, string $message): never {
    http_response_code($status);
    echo '<h1>' . e($message) . '</h1><p><a href="index.php">Browse reports</a></p>';
    exit;
}
function allow_methods(array $methods): void {
    if (!in_array($_SERVER['REQUEST_METHOD'], $methods, true)) {
        header('Allow: ' . implode(', ', $methods)); fail(405, 'Method not allowed.');
    }
}
function current_user(): ?array {
    static $resolved = false, $user = null;
    if ($resolved) return $user;
    $resolved = true;
    if (!isset($_SESSION['user_id'])) return null;
    if (time() - ($_SESSION['last_active'] ?? 0) > 1800 || time() - ($_SESSION['signed_in'] ?? 0) > 43200) {
        $_SESSION = []; session_regenerate_id(true); return null;
    }
    $q = db()->prepare('SELECT id, username FROM users WHERE id=?'); $q->execute([$_SESSION['user_id']]);
    $user = $q->fetch() ?: null;
    if ($user) $_SESSION['last_active'] = time();
    else { $_SESSION = []; session_regenerate_id(true); }
    return $user;
}
function require_login(): array {
    $user = current_user();
    if (!$user) {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') fail(401, 'Please sign in.');
        // Fixed local route; never redirect to a user-supplied external URL.
        $type = input($_GET, 'type');
        $next = basename($_SERVER['SCRIPT_NAME']) === 'item-form.php' && !isset($_GET['id']) ? 'create' : 'browse';
        redirect('login.php?next=' . $next . (in_array($type, ['lost','found'], true) ? '&type=' . $type : ''));
    }
    return $user;
}
function owns_item(array $item): bool {
    $user = current_user();
    return $user !== null && $item['owner_id'] !== null && (int)$item['owner_id'] === (int)$user['id'];
}
function require_owner(array $item): array {
    $user = require_login();
    if (!owns_item($item)) fail(403, 'You cannot manage this report.');
    return $user;
}
function sign_in(array $user): void {
    $_SESSION = []; session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id']; $_SESSION['signed_in'] = time(); $_SESSION['last_active'] = time();
}
function client_address(): string { return $_SERVER['REMOTE_ADDR'] ?? 'unknown'; }
function rate_limit(string $scope, string $subject, int $limit, int $seconds): void {
    $key = hash('sha256', $scope . ':' . $subject);
    $q = db()->prepare('INSERT INTO rate_limits (bucket_key, attempts, expires_at) VALUES (?,1,DATE_ADD(UTC_TIMESTAMP(), INTERVAL ? SECOND)) ON DUPLICATE KEY UPDATE attempts=IF(expires_at<=UTC_TIMESTAMP(),1,attempts+1), expires_at=IF(expires_at<=UTC_TIMESTAMP(),VALUES(expires_at),expires_at)');
    $q->execute([$key, $seconds]);
    $q = db()->prepare('SELECT attempts, GREATEST(1,TIMESTAMPDIFF(SECOND,UTC_TIMESTAMP(),expires_at)) AS retry_after FROM rate_limits WHERE bucket_key=?');
    $q->execute([$key]); $bucket = $q->fetch();
    if ((int)$bucket['attempts'] > $limit) { header('Retry-After: ' . (int)$bucket['retry_after']); fail(429, 'Too many requests. Please try again later.'); }
    db()->exec('DELETE FROM rate_limits WHERE expires_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 DAY)');
}
function after_login_url(): string {
    if (input($_GET, 'next') !== 'create') return 'index.php';
    $type = input($_GET, 'type');
    return 'item-form.php' . (in_array($type, ['lost','found'], true) ? '?type=' . $type : '');
}
