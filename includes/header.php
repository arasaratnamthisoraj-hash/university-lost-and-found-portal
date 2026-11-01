<?php
require_once __DIR__ . '/icons.php';
require_once __DIR__ . '/messaging.php';
$currentPage = basename($_SERVER['SCRIPT_NAME']);
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= e($title) ?> | University Lost &amp; Found Portal | Independent Student Project</title><link rel="stylesheet" href="assets/style.css"><script src="assets/app.js" defer></script></head>
<body><a class="skip-link" href="#main-content">Skip to content</a>
<div class="utility-bar"><div class="shell"><strong>Independent Student Project</strong><span>Student-built lost &amp; found noticeboard</span></div></div>
<header class="site-header"><div class="shell nav-layout"><a class="brand" href="index.php"><span class="project-mark" aria-hidden="true"><?= ui_icon('lost-found') ?></span><span><span class="brand-name">Lost &amp; Found</span><span class="brand-subtitle student-project-label">Independent Student Project</span></span></a><nav aria-label="Main navigation"><?php if ($signedIn = current_user()): ?><a class="nav-link inbox-link" href="messages.php">Inbox<?php if ($unread = unread_message_count((int)$signedIn['id'])): ?> <span class="unread-badge" aria-label="<?= e($unread) ?> unread messages"><?= e($unread) ?></span><?php endif; ?></a><form action="logout.php" method="post" class="logout-form"><input type="hidden" name="csrf" value="<?= e(token()) ?>"><button class="nav-link logout-button">Sign out</button></form><?php else: ?><a class="nav-link" href="login.php">Sign in</a><?php endif; ?><a class="nav-link" href="index.php" <?= $currentPage === 'index.php' ? 'aria-current="page"' : '' ?>>Browse reports</a><a class="button button-small" href="item-form.php" <?= $currentPage === 'item-form.php' ? 'aria-current="page"' : '' ?>><?= ui_icon('plus') ?> Report an item</a></nav></div></header><main id="main-content" tabindex="-1">
<?php if (isset($_SESSION['flash'])): ?><p class="notice" role="status"><?= e($_SESSION['flash']) ?></p><?php unset($_SESSION['flash']); endif; ?>
