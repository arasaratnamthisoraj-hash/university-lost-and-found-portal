<?php
require __DIR__ . '/includes/bootstrap.php';
$title = 'Browse reports';
$categories = db()->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$search = mb_substr(input($_GET, 'q'), 0, 120);
$type = input($_GET, 'type'); $category = input($_GET, 'category');
$sorts = ['newest' => 'items.event_date DESC, items.id DESC', 'oldest' => 'items.event_date ASC, items.id ASC', 'name' => 'items.name ASC, items.id ASC'];
$sort = input($_GET, 'sort'); if (!isset($sorts[$sort])) $sort = 'newest';
$where = []; $params = [];
if ($search !== '') {
    $where[] = "(items.name LIKE ? ESCAPE '!' OR items.description LIKE ? ESCAPE '!')";
    $term = '%' . strtr($search, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
    array_push($params, $term, $term);
}
if (in_array($type, ['lost', 'found'], true)) { $where[] = 'items.type = ?'; $params[] = $type; }
if (in_array($category, array_map('strval', array_column($categories, 'id')), true)) { $where[] = 'items.category_id = ?'; $params[] = $category; }
$sql = 'SELECT items.*, categories.name AS category_name FROM items JOIN categories ON categories.id = items.category_id';
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$q = db()->prepare($sql . ' ORDER BY ' . $sorts[$sort]); $q->execute($params); $items = $q->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<section class="hero" aria-labelledby="hero-title">
    <p class="eyebrow">For our campus community</p>
    <h1 id="hero-title">University of Ruhuna Lost &amp; Found Portal</h1>
    <p class="hero-description">Lost something on campus? We're here to help. Report missing belongings, browse found items, and help fellow students reconnect with what they've lost.</p>
    <div class="actions hero-actions">
        <a class="button" href="item-form.php?type=lost"><?= ui_icon('search') ?> Report Lost Item</a>
        <a class="button button-outline" href="item-form.php?type=found"><?= ui_icon('plus') ?> Report Found Item</a>
    </div>
    <p class="hero-footnote">Independent student project &mdash; not an official university service or endorsed by the University of Ruhuna.</p>
</section>
<section class="how-strip" aria-label="How the portal works"><div><span class="step-number">01</span><p><strong>Look around</strong><span>Search campus reports</span></p></div><div><span class="step-number">02</span><p><strong>Share the details</strong><span>Report a lost or found item</span></p></div><div><span class="step-number">03</span><p><strong>Make a connection</strong><span>Contact the reporter directly</span></p></div></section>
<section class="browse-section" aria-labelledby="reports-heading"><div class="section-heading"><div><p class="eyebrow">The campus noticeboard</p><h2 id="reports-heading">Find what matters.</h2></div><p>Browse lost and found reports from your community.</p></div>
<form class="filters panel" method="get" aria-label="Search and filter item reports">
<div class="search-field"><label for="q">Search reports</label><div class="input-icon"><?= ui_icon('search') ?><input id="q" name="q" maxlength="120" value="<?= e($search) ?>" placeholder="What are you looking for?"></div></div>
<div><label for="type">Report type</label><select id="type" name="type"><option value="">All types</option><?php foreach (['lost', 'found'] as $v): ?><option value="<?= $v ?>" <?= $type === $v ? 'selected' : '' ?>><?= ucfirst($v) ?></option><?php endforeach; ?></select></div>
<div><label for="category">Category</label><select id="category" name="category"><option value="">All categories</option><?php foreach ($categories as $c): ?><option value="<?= e($c['id']) ?>" <?= $category === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
<div><label for="sort">Sort by</label><select id="sort" name="sort"><?php foreach (['newest' => 'Newest event first', 'oldest' => 'Oldest event first', 'name' => 'Item name A–Z'] as $key => $label): ?><option value="<?= $key ?>" <?= $sort === $key ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>
<div class="actions filter-actions"><button><?= ui_icon('search') ?> Apply filters</button><a class="reset-link" href="index.php">Reset</a></div></form>
<div class="results-heading"><h3>Item reports <span class="count"><?= count($items) ?></span></h3><span>Sorted by <?= e(['newest' => 'newest event', 'oldest' => 'oldest event', 'name' => 'item name'][$sort]) ?></span></div>
<?php if (!$items): ?><div class="panel empty-state"><span class="empty-icon"><?= ui_icon('search') ?></span><h3>A little quiet here.</h3><p>No reports match your selection.</p><p>Try a different search, or help someone by sharing an item report.</p><a class="button" href="item-form.php"><?= ui_icon('plus') ?> Report an item</a></div><?php endif; ?>
<div class="cards"><?php foreach ($items as $item): ?><article class="report-card"><div class="card-visual <?= e($item['type']) ?>-visual"><span class="category-art"><?= category_icon($item['category_name']) ?></span><span class="badge <?= e($item['type']) ?>"><span class="status-dot"></span><?= e(ucfirst($item['type'])) ?></span><span class="visual-category"><?= e($item['category_name']) ?></span></div><div class="card-content"><p class="card-category"><?= e($item['category_name']) ?></p><h3><a href="item.php?id=<?= e($item['id']) ?>"><?= e($item['name']) ?></a></h3><p class="card-meta"><?= ui_icon('pin') ?><span><?= e($item['location']) ?></span></p><p class="card-meta"><?= ui_icon('calendar') ?><span><?= e($item['event_date']) ?></span></p><a class="card-link" href="item.php?id=<?= e($item['id']) ?>">View details <?= ui_icon('arrow') ?></a></div></article><?php endforeach; ?></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
