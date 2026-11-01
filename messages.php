<?php
require __DIR__ . '/includes/bootstrap.php';
allow_methods(['GET']); $user = require_login();
$before = filter_var($_GET['before'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]) ?: PHP_INT_MAX;
$q = db()->prepare('SELECT c.id,c.item_id,i.name AS item_name,u.username FROM conversations c LEFT JOIN items i ON i.id=c.item_id JOIN users u ON u.id=CASE WHEN c.sender_id=? THEN c.reporter_id ELSE c.sender_id END WHERE (c.sender_id=? OR c.reporter_id=?) AND c.id<? ORDER BY c.id DESC LIMIT 31');
$q->execute([$user['id'],$user['id'],$user['id'],$before]); $rows=$q->fetchAll();
$more=count($rows)>30; $rows=array_slice($rows,0,30);
$title='Private inbox'; require __DIR__.'/includes/header.php';
?>
<section class="narrow panel"><h1>Private inbox</h1><p>Only you and the other participant can read each conversation. Phone numbers and email addresses are not shared automatically.</p>
<?php if (!$rows): ?><p>No conversations yet.</p><?php endif; ?>
<?php foreach($rows as $row): ?><p><a href="conversation.php?id=<?= e($row['id']) ?>"><?= e($row['item_name'] ?? 'Deleted report') ?> — <?= e($row['username']) ?></a></p><?php endforeach; ?>
<?php if($more): ?><a href="messages.php?before=<?= e(end($rows)['id']) ?>">Older conversations</a><?php endif; ?>
</section>
<?php require __DIR__.'/includes/footer.php'; ?>
