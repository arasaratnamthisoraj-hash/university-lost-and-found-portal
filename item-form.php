<?php
require __DIR__ . '/includes/bootstrap.php';
allow_methods(['GET','POST']);
$user = require_login();
$editing = isset($_GET['id']); $id = $editing ? item_id() : null;
$data = $editing ? find_item($id) : array_fill_keys(['name','type','category_id','description','location','event_date','reporter_name','contact'], '');
if ($editing) require_owner($data);
$data['contact_visible'] = $editing ? $data['contact_visible'] : 0;
// Homepage shortcuts preselect the form only; POST validation is unchanged.
if (!$editing && $_SERVER['REQUEST_METHOD'] === 'GET' && in_array(input($_GET, 'type'), ['lost', 'found'], true)) $data['type'] = input($_GET, 'type');
$categories = db()->query('SELECT * FROM categories ORDER BY name')->fetchAll(); $errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf();
    [$data, $errors] = validate_item($_POST, array_column($categories, 'id'));
    $data['contact_visible'] = input($_POST, 'contact_visible') === '1' ? 1 : 0;
    if (!$errors) {
        $values = array_map(fn($key) => $data[$key], ['category_id','name','type','description','location','event_date','reporter_name','contact']);
        if ($editing) {
            $q = db()->prepare('UPDATE items SET category_id=?, name=?, type=?, description=?, location=?, event_date=?, reporter_name=?, contact=?, contact_visible=? WHERE id=? AND owner_id=?');
            array_push($values, $data['contact_visible'], $id, $user['id']); $q->execute($values);
        } else {
            rate_limit('create-user', (string)$user['id'], 30, 3600);
            $q = db()->prepare('INSERT INTO items (category_id,name,type,description,location,event_date,reporter_name,contact,contact_visible,owner_id) VALUES (?,?,?,?,?,?,?,?,?,?)');
            array_push($values, $data['contact_visible'], $user['id']);
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
<?php endforeach; ?><div class="field-wide"><label class="checkbox-label"><input type="checkbox" name="contact_visible" value="1" <?= $data['contact_visible'] ? 'checked' : '' ?>> Allow signed-in users to request my reporter name and contact details for this item.</label><p class="small-note">Optional. Contact information stays private unless you choose to share it. Never include private contact details in the public description or title.</p></div><div class="form-bottom field-wide"><p>All report fields except Description are required. Contact sharing is optional.</p><div class="actions"><button><?= ui_icon($editing ? 'check' : 'plus') ?><?= $editing ? 'Save changes' : 'Create report' ?></button><a class="cancel-link" href="<?= $editing ? 'item.php?id=' . $id : 'index.php' ?>">Cancel</a></div></div></form></div><aside class="form-aside"><div class="aside-icon"><?= ui_icon('book') ?></div><h2>A helpful report starts with the details.</h2><p>Mention the item&rsquo;s colour, its distinguishing features, and where it was lost or found.</p><hr><h3><?= ui_icon('shield') ?> Share thoughtfully</h3><p>Only share contact information you are comfortable disclosing to other account holders. Never include passwords, identity numbers, or other sensitive information.</p><p class="aside-note">Only your account can edit or delete reports you create. Accounts do not verify university enrollment.</p></aside></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
