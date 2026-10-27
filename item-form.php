<?php
require __DIR__ . '/includes/bootstrap.php';
$editing = isset($_GET['id']); $id = $editing ? item_id() : null;
$data = $editing ? find_item($id) : array_fill_keys(['name','type','category_id','description','location','event_date','reporter_name','contact'], '');
// Homepage shortcuts preselect the form only; POST validation is unchanged.
if (!$editing && $_SERVER['REQUEST_METHOD'] === 'GET' && in_array(input($_GET, 'type'), ['lost', 'found'], true)) $data['type'] = input($_GET, 'type');
$categories = db()->query('SELECT * FROM categories ORDER BY name')->fetchAll(); $errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf();
    [$data, $errors] = validate_item($_POST, array_column($categories, 'id'));
    if (!$errors) {
        $values = array_map(fn($key) => $data[$key], ['category_id','name','type','description','location','event_date','reporter_name','contact']);
        if ($editing) {
            $q = db()->prepare('UPDATE items SET category_id=?, name=?, type=?, description=?, location=?, event_date=?, reporter_name=?, contact=? WHERE id=?');
            $values[] = $id; $q->execute($values);
        } else {
            $q = db()->prepare('INSERT INTO items (category_id,name,type,description,location,event_date,reporter_name,contact) VALUES (?,?,?,?,?,?,?,?)');
            $q->execute($values); $id = (int) db()->lastInsertId();
        }
        $_SESSION['flash'] = $editing ? 'Report updated.' : 'Report created.'; redirect('item.php?id=' . $id);
    }
    http_response_code(422);
}
$title = $editing ? 'Edit report' : 'Report an item'; require __DIR__ . '/includes/header.php';
?>
<div class="page-heading"><a class="back-link" href="index.php">&larr; Browse reports</a><p class="eyebrow"><?= $editing ? 'Keep your community informed' : 'A small step. A helpful connection.' ?></p><h1><?= e($title) ?></h1><p>Share the details. Help a familiar belonging find its way home.</p></div><div class="form-layout"><div>
<?php if ($errors): ?><div class="error-summary" role="alert"><strong>Please correct the form.</strong><ul><?php foreach ($errors as $key => $message): ?><li><a href="#<?= e($key) ?>"><?= e(ucwords(str_replace('_', ' ', $key)) . ': ' . $message) ?></a></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" class="panel item-form"><input type="hidden" name="csrf" value="<?= e(token()) ?>">
<?php foreach (['name' => ['Item name',120], 'type' => ['Report type',0], 'category_id' => ['Category',0], 'description' => ['Description',3000], 'location' => ['Campus location',160], 'event_date' => ['Date lost or found',0], 'reporter_name' => ['Reporter name',100], 'contact' => ['Contact email or phone',160]] as $key => [$label, $max]): ?>
<div class="form-field <?= in_array($key, ['name', 'description', 'contact'], true) ? 'field-wide' : '' ?>"><label for="<?= $key ?>"><?= $label ?><?php if ($key === 'description'): ?> (Optional)<?php else: ?> <span aria-hidden="true">*</span><span class="sr-only"> (required)</span><?php endif; ?></label>
<?php $attrs = ' id="'.$key.'" name="'.$key.'"' . ($key === 'description' ? '' : ' required') . (isset($errors[$key]) ? ' aria-invalid="true" aria-describedby="'.$key.'-error"' : ''); ?>
<?php if ($key === 'type' || $key === 'category_id'): ?><select<?= $attrs ?>><option value="">Select…</option><?php $options = $key === 'type' ? ['lost' => 'Lost', 'found' => 'Found'] : array_column($categories, 'name', 'id'); foreach ($options as $value => $text): ?><option value="<?= e($value) ?>" <?= (string)$data[$key] === (string)$value ? 'selected' : '' ?>><?= e($text) ?></option><?php endforeach; ?></select>
<?php elseif ($key === 'description'): ?><textarea<?= $attrs ?> maxlength="3000" rows="5"><?= e($data[$key]) ?></textarea>
<?php elseif ($key === 'event_date'): ?><input<?= $attrs ?> type="date" min="1900-01-01" max="<?= date('Y-m-d') ?>" value="<?= e($data[$key]) ?>">
<?php else: ?><input<?= $attrs ?> type="text" maxlength="<?= $max ?>" value="<?= e($data[$key]) ?>"><?php endif; ?>
<?php if (isset($errors[$key])): ?><p class="field-error" id="<?= $key ?>-error"><?= e($errors[$key]) ?></p><?php endif; ?></div>
<?php endforeach; ?><div class="form-bottom field-wide"><p>All fields except Description are required. Contact details are visible to anyone who opens the report&rsquo;s contact section.</p><div class="actions"><button><?= ui_icon($editing ? 'check' : 'plus') ?><?= $editing ? 'Save changes' : 'Create report' ?></button><a class="cancel-link" href="<?= $editing ? 'item.php?id=' . $id : 'index.php' ?>">Cancel</a></div></div></form></div><aside class="form-aside"><div class="aside-icon"><?= ui_icon('book') ?></div><h2>A helpful report starts with the details.</h2><p>Mention the item&rsquo;s colour, its distinguishing features, and where it was lost or found.</p><hr><h3><?= ui_icon('shield') ?> Share thoughtfully</h3><p>Use only contact information you are comfortable sharing publicly. Never include passwords, identity numbers, or other sensitive information.</p><p class="aside-note">This is a shared student project. Visitors can edit or delete reports.</p></aside></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
