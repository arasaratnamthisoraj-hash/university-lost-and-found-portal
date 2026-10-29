<?php
require_once __DIR__ . '/icons.php';
$currentPage = basename($_SERVER['SCRIPT_NAME']);
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= e($title) ?> | University of Ruhuna Lost &amp; Found Portal</title><link rel="stylesheet" href="assets/style.css"><script src="assets/app.js" defer></script></head>
<body><a class="skip-link" href="#main-content">Skip to content</a>
<div class="utility-bar"><div class="shell"><span>University of Ruhuna <span class="utility-divider">/</span> Student project</span><a href="https://ruh.ac.lk/">University website <?= ui_icon('arrow') ?></a></div></div>
<header class="site-header"><div class="shell nav-layout"><a class="brand" href="index.php"><img class="university-logo" src="assets/images/ruhuna-logo.png.png" alt="University of Ruhuna logo" width="190" height="265"><span><span class="brand-name">Lost &amp; Found</span><span class="brand-subtitle">University of Ruhuna</span></span></a><nav aria-label="Main navigation"><?php if ($signedIn = current_user()): ?><form action="logout.php" method="post" class="logout-form"><input type="hidden" name="csrf" value="<?= e(token()) ?>"><button class="nav-link logout-button">Sign out</button></form><?php else: ?><a class="nav-link" href="login.php">Sign in</a><?php endif; ?><a class="nav-link" href="index.php" <?= $currentPage === 'index.php' ? 'aria-current="page"' : '' ?>>Browse reports</a><a class="button button-small" href="item-form.php" <?= $currentPage === 'item-form.php' ? 'aria-current="page"' : '' ?>><?= ui_icon('plus') ?> Report an item</a></nav></div></header><main id="main-content" tabindex="-1">
<?php if (isset($_SESSION['flash'])): ?><p class="notice" role="status"><?= e($_SESSION['flash']) ?></p><?php unset($_SESSION['flash']); endif; ?>
