<?php
require __DIR__ . '/includes/bootstrap.php';
allow_methods(['GET','POST']);
if (current_user()) redirect(after_login_url());
$username = ''; $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf(); $username = strtolower(input($_POST, 'username'));
    rate_limit('login-ip', client_address(), 60, 900);
    rate_limit('login-username', mb_substr($username,0,100), 15, 900);
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $q = db()->prepare('SELECT id,username,password_hash FROM users WHERE username=?'); $q->execute([$username]); $user = $q->fetch();
    // A fixed dummy hash makes unknown-user checks perform password verification too.
    $hash = $user['password_hash'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
    $valid = strlen($password) <= 72 && !str_contains($password, "\0") && password_verify($password, $hash);
    if ($valid && $user) {
        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) { $q = db()->prepare('UPDATE users SET password_hash=? WHERE id=?'); $q->execute([password_hash($password,PASSWORD_DEFAULT),$user['id']]); }
        sign_in($user); redirect(after_login_url());
    }
    http_response_code(422); $error = 'Invalid username or password.';
}
$title = 'Sign in'; require __DIR__ . '/includes/header.php';
?>
<section class="narrow panel auth-panel"><p class="eyebrow">Welcome back</p><h1>Sign in</h1><p>Browse freely. Sign in to create and manage your reports.</p><?php if ($error): ?><div class="error-summary" role="alert"><?= e($error) ?></div><?php endif; ?><form method="post" class="auth-form"><input type="hidden" name="csrf" value="<?= e(token()) ?>"><div><label for="username">Username</label><input id="username" name="username" autocomplete="username" maxlength="40" required value="<?= e($username) ?>"></div><div><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" maxlength="72" required></div><div class="actions"><button>Sign in</button><a href="register.php">Create an account</a></div></form></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
