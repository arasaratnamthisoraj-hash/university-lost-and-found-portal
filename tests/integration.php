<?php
declare(strict_types=1);
// Run from CLI only. Test records are uniquely named and removed in finally.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
$base = rtrim($argv[1] ?? 'http://localhost/university-lost-and-found-portal', '/');
$config = require __DIR__ . '/../config/database.php';
$pdo = new PDO("mysql:host={$config['host']};port={$config['port']};dbname={$config['name']};charset=utf8mb4", $config['user'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$cookie = tempnam(sys_get_temp_dir(), 'lost-found-test-');
$ids = []; $passed = 0;
function check(bool $condition, string $label): void {
    global $passed;
    if (!$condition) throw new RuntimeException('FAIL: ' . $label);
    $passed++; echo "PASS: $label\n";
}
function request(string $path, ?array $data = null, string $method = 'GET'): array {
    global $base, $cookie;
    $ch = curl_init($base . '/' . $path);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEJAR => $cookie, CURLOPT_COOKIEFILE => $cookie, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 15]);
    if ($data !== null) curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($data)]);
    elseif ($method !== 'GET') curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    $body = curl_exec($ch);
    if ($body === false) throw new RuntimeException(curl_error($ch));
    $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE); curl_close($ch);
    return [$code, $body];
}
function csrfToken(string $body): string {
    if (!preg_match('/name="csrf" value="([a-f0-9]+)"/', $body, $match)) throw new RuntimeException('Missing CSRF token');
    return $match[1];
}
try {
    $category = (string)$pdo->query('SELECT id FROM categories ORDER BY id LIMIT 1')->fetchColumn();
    [$status, $form] = request('item-form.php'); check($status === 200, 'Create form loads');
    $token = csrfToken($form); $prefix = 'TEST-' . bin2hex(random_bytes(6));
    $data = ['csrf' => $token, 'name' => $prefix . ' Z wallet <script>alert(1)</script>', 'category_id' => $category, 'type' => 'lost', 'description' => "Owner's wallet 100%_", 'location' => 'Library', 'event_date' => '2026-01-02', 'reporter_name' => 'Test reporter', 'contact' => 'test@example.invalid'];
    [$status] = request('item-form.php', array_replace($data, ['csrf' => 'wrong'])); check($status === 403, 'Invalid CSRF rejected');
    [$status] = request('item-form.php', array_replace($data, ['name' => ' ', 'event_date' => '2026-02-30', 'category_id' => '999999', 'type' => 'other'])); check($status === 422, 'Server rejects invalid fields without JavaScript');
    [$status] = request('item-form.php', array_replace($data, ['event_date' => "2026-01-02\0"])); check($status === 422, 'Malformed date rejected without server failure');
    [$status] = request('item-form.php', array_replace($data, ['name' => str_repeat('a', 121)])); check($status === 422, 'Length limit enforced');
    [$status] = request('item-form.php', array_replace($data, ['event_date' => '2999-01-01'])); check($status === 422, 'Future date rejected');
    [$status] = request('item-form.php', $data); check($status === 303, 'Create redirects after successful POST');
    $q = $pdo->prepare('SELECT * FROM items WHERE name=?'); $q->execute([$data['name']]); $row = $q->fetch(PDO::FETCH_ASSOC); check((bool)$row, 'Created record persists in MariaDB'); $id = (int)$row['id']; $ids[] = $id;
    [$status, $body] = request('item.php?id=' . $id); check($status === 200 && str_contains($body, '&lt;script&gt;') && !str_contains($body, '<script>alert(1)</script>'), 'Details render stored text with HTML escaping');
    [$status, $form] = request('item-form.php?id=' . $id); check($status === 200 && str_contains($form, 'Library'), 'Edit form contains persisted values');
    $data['csrf'] = csrfToken($form); $data['location'] = 'Main hall';
    [$status] = request('item-form.php?id=' . $id, array_replace($data, ['location' => ''])); check($status === 422, 'Invalid update rejected');
    [$status] = request('item-form.php?id=' . $id, $data); check($status === 303, 'Valid update accepted');
    $q = $pdo->prepare('SELECT location FROM items WHERE id=?'); $q->execute([$id]); check($q->fetchColumn() === 'Main hall', 'Updated value persists in database');
    $second = array_replace($data, ['name' => $prefix . ' A keys', 'type' => 'found', 'event_date' => '2026-01-01']);
    [$status] = request('item-form.php', $second); check($status === 303, 'Second report created for filtering tests');
    $q = $pdo->prepare('SELECT id FROM items WHERE name=?'); $q->execute([$second['name']]); $id2 = (int)$q->fetchColumn(); $ids[] = $id2;
    $one = 'item.php?id=' . $id . '"'; $two = 'item.php?id=' . $id2 . '"';
    [$status, $body] = request('index.php?' . http_build_query(['q' => $prefix, 'sort' => 'newest'])); check($status === 200 && str_contains($body, $one) && str_contains($body, $two) && strpos($body, $one) < strpos($body, $two), 'Search and newest sort');
    [$status, $body] = request('index.php?' . http_build_query(['q' => $prefix, 'sort' => 'oldest'])); check($status === 200 && str_contains($body, $one) && str_contains($body, $two) && strpos($body, $two) < strpos($body, $one), 'Oldest sort');
    [$status, $body] = request('index.php?' . http_build_query(['q' => $prefix, 'sort' => 'name'])); check($status === 200 && str_contains($body, $one) && str_contains($body, $two) && strpos($body, $two) < strpos($body, $one), 'Alphabetical sort');
    [$status, $body] = request('index.php?' . http_build_query(['q' => $prefix, 'type' => 'found', 'category' => $category])); check($status === 200 && str_contains($body, $two) && !str_contains($body, $one), 'Combined search, type and category filters');
    [$status, $body] = request('index.php?' . http_build_query(['q' => '100%_'])); check($status === 200 && str_contains($body, $one), 'Literal SQL wildcard characters are searchable');
    [$status, $body] = request('index.php?' . http_build_query(['q' => "' OR 1=1 --", 'sort' => 'name;DROP TABLE items'])); check($status === 200 && !str_contains($body, $one), 'SQL injection input treated as data and unsafe sort ignored');
    [$status, $body] = request('index.php?q=' . $prefix . '-absent'); check($status === 200 && str_contains($body, 'No reports match'), 'Empty search state');
    [$status] = request('item.php?id[]=1'); check($status === 404, 'Malformed ID handled');
    [$status] = request('item.php?id=2147483647'); check($status === 404, 'Missing record handled');
    [$status, $form] = request('delete.php?id=' . $id); check($status === 200 && str_contains($form, 'This cannot be undone'), 'GET displays deletion confirmation');
    $q = $pdo->prepare('SELECT COUNT(*) FROM items WHERE id=?'); $q->execute([$id]); check((int)$q->fetchColumn() === 1, 'GET confirmation does not delete');
    [$status] = request('delete.php?id=' . $id, ['csrf' => 'bad']); check($status === 403, 'Delete rejects invalid CSRF');
    [$status] = request('delete.php?id=' . $id, ['csrf' => csrfToken($form)]); check($status === 303, 'Confirmed POST deletion accepted');
    $q->execute([$id]); check((int)$q->fetchColumn() === 0, 'Deletion persists in database');
    [$status] = request('item.php?id=' . $id); check($status === 404, 'Deleted report is no longer readable');
    foreach (['config/database.php','includes/bootstrap.php','database/schema.sql','tests/integration.php'] as $path) { [$status] = request($path); check($status === 403, 'Apache denies direct access: ' . $path); }
    try { $pdo->exec('INSERT INTO items (category_id,name,type,description,location,event_date,reporter_name,contact) VALUES (4294967295,\'invalid\',\'lost\',\'test\',\'test\',\'2026-01-01\',\'test\',\'test\')'); throw new RuntimeException('Foreign key accepted invalid category'); }
    catch (PDOException $error) { check($error->getCode() === '23000', 'Foreign key rejects nonexistent category'); }
    foreach (['lost', 'found'] as $reportType) {
        $optional = array_replace($data, ['name' => $prefix . ' optional ' . $reportType, 'type' => $reportType, 'description' => '']);
        [$status, $form] = request('item-form.php?type=' . $reportType);
        $dom = new DOMDocument(); @$dom->loadHTML($form); $xpath = new DOMXPath($dom);
        check($status === 200 && $xpath->query('//textarea[@name="description" and not(@required) and @maxlength="3000"]')->length === 1 && trim($xpath->evaluate('string(//label[@for="description"])')) === 'Description (Optional)', "$reportType form marks description optional and keeps maximum length");
        [$status] = request('item-form.php', array_replace($optional, ['description' => str_repeat('a', 3001)]));
        check($status === 422, "$reportType create rejects oversized description");
        [$status] = request('item-form.php', $optional);
        check($status === 303, "$reportType create accepts empty description");
        $q = $pdo->prepare('SELECT id, description FROM items WHERE name=?'); $q->execute([$optional['name']]); $row = $q->fetch(PDO::FETCH_ASSOC);
        if ($row) $ids[] = (int)$row['id'];
        check($row !== false && $row['description'] === '', "$reportType empty description persists in database");
        $optionalId = (int)$row['id'];
        [$status, $body] = request('item.php?id=' . $optionalId);
        check($status === 200 && str_contains($body, 'No additional description provided.'), "$reportType details show empty-description fallback");
        [$status, $form] = request('item-form.php?id=' . $optionalId);
        $dom = new DOMDocument(); @$dom->loadHTML($form); $xpath = new DOMXPath($dom);
        check($status === 200 && $xpath->query('//textarea[@name="description" and not(@required)]')->length === 1, "$reportType edit textarea is optional");
        [$status] = request('item-form.php?id=' . $optionalId, array_replace($optional, ['description' => str_repeat('a', 3000)]));
        check($status === 303, "$reportType update accepts 3000-character description");
        [$status] = request('item-form.php?id=' . $optionalId, array_replace($optional, ['description' => str_repeat('a', 3001)]));
        check($status === 422, "$reportType update rejects 3001-character description");
        $q = $pdo->prepare('SELECT description FROM items WHERE id=?'); $q->execute([$optionalId]);
        check($q->fetchColumn() === str_repeat('a', 3000), "$reportType rejected update preserves description");
        [$status] = request('item-form.php?id=' . $optionalId, $optional);
        check($status === 303, "$reportType update accepts clearing description");
        $q->execute([$optionalId]); check($q->fetchColumn() === '', "$reportType cleared description persists");
        [$status, $body] = request('item.php?id=' . $optionalId);
        check($status === 200 && str_contains($body, 'No additional description provided.'), "$reportType cleared details show fallback");
    }
    echo "\n$passed checks passed.\n";
} finally {
    $delete = $pdo->prepare('DELETE FROM items WHERE id=?'); foreach ($ids as $id) $delete->execute([$id]);
    if (is_file($cookie)) unlink($cookie);
}
