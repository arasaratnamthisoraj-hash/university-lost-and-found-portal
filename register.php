<?php
require __DIR__ . '/includes/bootstrap.php';
allow_methods(['GET','POST']);
if (current_user()) redirect('index.php');
$username = ''; $errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf(); rate_limit('register-ip', client_address(), 10, 3600);
    $username = strtolower(input($_POST, 'username'));
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $confirm = is_string($_POST['confirm_password'] ?? null) ? $_POST['confirm_password'] : '';
    if (!preg_match('/^[a-z0-9_]{3,40}$/D', $username)) $errors[] = 'Use a username of 3–40 letters, numbers or underscores.';
    if (mb_strlen($password) < 15 || strlen($password) > 72 || str_contains($password, "\0")) $errors[] = 'Use a password of at least 15 characters and at most 72 bytes.';
    if ($password !== $confirm) $errors[] = 'Passwords must match.';
    if (!$errors) {
        try {
            $q = db()->prepare('INSERT INTO users (username,password_hash) VALUES (?,?)'); $q->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
            $_SESSION['flash'] = 'Account created. Please sign in.'; redirect('login.php');
        } catch (PDOException $error) {
            if ($error->getCode() !== '23000') throw $error;
            $errors[] = 'That username is unavailable.';
        }
    }
    http_response_code(422);
}
$title = 'Create an account'; require __DIR__ . '/includes/header.php';
?>
<section class="narrow panel auth-panel"><p class="eyebrow">Campus community</p><h1>Create an account</h1><p>Create reports and manage only the reports you own. Accounts do not verify university enrollment.</p>
<?php if ($errors): ?><div class="error-summary" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" class="auth-form"><input type="hidden" name="csrf" value="<?= e(token()) ?>"><div><label for="username">Username</label><input id="username" name="username" autocomplete="username" minlength="3" maxlength="40" pattern="[A-Za-z0-9_]{3,40}" required value="<?= e($username) ?>"><p class="small-note">Choose a username you can remember. Your username is not displayed on public reports.</p></div><div><label for="password">Password</label><input id="password" type="password" name="password" autocomplete="new-password" minlength="15" maxlength="72" required aria-describedby="password-help"><p class="small-note" id="password-help">At least 15 characters; at most 72 bytes. Use a unique passphrase. There is no self-service password recovery.</p></div><div><label for="confirm_password">Confirm password</label><input id="confirm_password" type="password" name="confirm_password" autocomplete="new-password" minlength="15" maxlength="72" required></div><div class="actions"><button>Create account</button><a href="login.php">Sign in instead</a></div></form></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
