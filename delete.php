<?php
require __DIR__ . '/includes/bootstrap.php';
$id = item_id(); $item = find_item($id);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf(); $q = db()->prepare('DELETE FROM items WHERE id = ?'); $q->execute([$id]);
    $_SESSION['flash'] = 'Report deleted.'; redirect('index.php');
}
$title = 'Delete report'; require __DIR__ . '/includes/header.php';
?>
<section class="narrow panel delete-panel"><span class="delete-icon"><?= ui_icon('trash') ?></span><p class="eyebrow">Manage report</p><h1>Delete this report?</h1><p>You are about to delete <strong><?= e($item['name']) ?></strong>. This cannot be undone.</p><p class="small-note">If you only need to correct a detail, you can edit the report instead.</p><form method="post" data-delete-form><input type="hidden" name="csrf" value="<?= e(token()) ?>"><div class="actions"><button class="danger"><?= ui_icon('trash') ?>Delete report</button><a class="cancel-link" href="item.php?id=<?= $id ?>">Cancel</a></div></form></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
