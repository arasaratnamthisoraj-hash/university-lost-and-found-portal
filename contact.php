<?php
require __DIR__ . '/includes/bootstrap.php';
allow_methods(['POST']); $user = require_login(); csrf();
$item = find_item(item_id());
if (!owns_item($item)) fail(403, 'Contact information is private.');
rate_limit('contact-user', (string)$user['id'], 20, 3600);
$title = 'Reporter contact'; require __DIR__ . '/includes/header.php';
?>
<section class="narrow panel"><a class="back-link" href="item.php?id=<?= e($item['id']) ?>">&larr; Back to report</a><h1>Reporter contact</h1><p>For <?= e($item['name']) ?></p><dl><dt>Reported by</dt><dd><?= e($item['reporter_name']) ?></dd><dt>Contact</dt><dd><?= e($item['contact']) ?></dd></dl><p class="small-note">Use these details only to discuss this item. Information is reporter-provided and is not verified.</p></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
