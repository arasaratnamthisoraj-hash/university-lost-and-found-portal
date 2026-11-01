<?php
declare(strict_types=1);
// Run from CLI only. Test records are uniquely named and removed in finally.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
$base = rtrim($argv[1] ?? 'http://localhost/university-lost-and-found-portal', '/');
$config = require __DIR__ . '/../config/database.php';
$pdo = new PDO("mysql:host={$config['host']};port={$config['port']};dbname={$config['name']};charset=utf8mb4", $config['user'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$cookie = tempnam(sys_get_temp_dir(), 'lost-found-test-');
$ids = []; $userIds = []; $passed = 0;
$ownerCookie = $cookie; $guestCookie = tempnam(sys_get_temp_dir(), 'lost-found-guest-'); $otherCookie = tempnam(sys_get_temp_dir(), 'lost-found-other-');
$allCookies = [$ownerCookie, $guestCookie, $otherCookie];
function check(bool $condition, string $label): void {
    global $passed;
    if (!$condition) throw new RuntimeException('FAIL: ' . $label);
    $passed++; echo "PASS: $label\n";
}
function request(string $path, ?array $data = null, string $method = 'GET'): array {
    global $base, $cookie;
    $ch = curl_init($base . '/' . $path); $headers = [];
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEJAR => $cookie, CURLOPT_COOKIEFILE => $cookie, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 15]);
    curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($handle, $line) use (&$headers) { $parts = explode(':', $line, 2); if (count($parts) === 2) $headers[strtolower(trim($parts[0]))] = trim($parts[1]); return strlen($line); });
    if ($data !== null) curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($data)]);
    elseif ($method !== 'GET') curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    $body = curl_exec($ch);
    if ($body === false) throw new RuntimeException(curl_error($ch));
    $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE); curl_close($ch);
    return [$code, $body, $headers];
}
function csrfToken(string $body): string {
    if (!preg_match('/name="csrf" value="([a-f0-9]+)"/', $body, $match)) throw new RuntimeException('Missing CSRF token');
    return $match[1];
}
try {
    $accountPrefix = 'test_' . bin2hex(random_bytes(6));
    $password = bin2hex(random_bytes(16)) . ' Aa1!';
    [$status] = request('item-form.php'); check($status === 303, 'Anonymous create redirects to sign in');
    [$status] = request('item-form.php', ['name' => 'attempt']); check($status === 401, 'Anonymous create POST rejected');
    [$status, $form] = request('register.php'); $registerToken = csrfToken($form);
    [$status] = request('register.php', ['csrf' => 'bad']); check($status === 403, 'Registration requires CSRF');
    [$status] = request('register.php', ['csrf'=>$registerToken,'username'=>$accountPrefix,'password'=>'short','confirm_password'=>'short']); check($status === 422, 'Weak registration password rejected');
    foreach ([$ownerCookie => $accountPrefix . '_a', $otherCookie => $accountPrefix . '_b'] as $jar => $username) {
        $cookie = $jar; [$status, $form] = request('register.php');
        [$status] = request('register.php', ['csrf'=>csrfToken($form),'username'=>$username,'password'=>$password,'confirm_password'=>$password]);
        check($status === 303, 'Valid registration accepted');
        $q = $pdo->prepare('SELECT id,password_hash FROM users WHERE username=?'); $q->execute([$username]); $account = $q->fetch(PDO::FETCH_ASSOC);
        if ($account) $userIds[] = (int)$account['id'];
        check($account && $account['password_hash'] !== $password && password_verify($password,$account['password_hash']), 'Password stored as a verifiable hash');
        [$status, $form, $beforeHeaders] = request('login.php'); $loginToken = csrfToken($form);
        [$status] = request('login.php', ['csrf'=>'bad','username'=>$username,'password'=>$password]); check($status === 403, 'Login requires CSRF');
        [$status, $body] = request('login.php', ['csrf'=>$loginToken,'username'=>$username,'password'=>'incorrect']); check($status === 422 && str_contains($body,'Invalid username or password.'), 'Wrong password rejected with generic error');
        [$status, $body, $afterHeaders] = request('login.php', ['csrf'=>$loginToken,'username'=>$username,'password'=>$password]);
        check($status === 303 && isset($afterHeaders['set-cookie']), 'Valid login succeeds and regenerates session cookie');
        [$status] = request('item-form.php', ['csrf'=>$loginToken]); check($status === 403, 'Login rotates the CSRF token');
    }
    $ownerId = $userIds[0]; $otherId = $userIds[1];
    $cookie = $guestCookie; [$status, $form] = request('register.php');
    [$status] = request('register.php', ['csrf'=>csrfToken($form),'username'=>$accountPrefix . '_a','password'=>$password,'confirm_password'=>$password]); check($status === 422, 'Duplicate username rejected');
    $cookie = $ownerCookie;
    $category = (string)$pdo->query('SELECT id FROM categories ORDER BY id LIMIT 1')->fetchColumn();
    [$status, $form] = request('item-form.php'); check($status === 200, 'Create form loads');
    $token = csrfToken($form); $prefix = 'TEST-' . bin2hex(random_bytes(6));
    $data = ['csrf' => $token, 'name' => $prefix . ' Z wallet <script>alert(1)</script>', 'category_id' => $category, 'type' => 'lost', 'description' => "Owner's wallet 100%_", 'location' => 'Library', 'event_date' => '2026-01-02', 'reporter_name' => 'Test reporter', 'contact' => 'test@example.invalid'];
    [$status] = request('item-form.php', array_replace($data, ['csrf' => 'wrong'])); check($status === 403, 'Invalid CSRF rejected');
    [$status] = request('item-form.php', array_replace($data, ['name' => ' ', 'event_date' => '2026-02-30', 'category_id' => '999999', 'type' => 'other'])); check($status === 422, 'Server rejects invalid fields without JavaScript');
    [$status] = request('item-form.php', array_replace($data, ['event_date' => "2026-01-02\0"])); check($status === 422, 'Malformed date rejected without server failure');
    [$status] = request('item-form.php', array_replace($data, ['name' => str_repeat('a', 121)])); check($status === 422, 'Length limit enforced');
    [$status] = request('item-form.php', array_replace($data, ['event_date' => '2999-01-01'])); check($status === 422, 'Future date rejected');
    [$status] = request('item-form.php', array_replace($data,['owner_id'=>$otherId])); check($status === 303, 'Create redirects after successful POST');
    $q = $pdo->prepare('SELECT * FROM items WHERE name=?'); $q->execute([$data['name']]); $row = $q->fetch(PDO::FETCH_ASSOC); check((bool)$row, 'Created record persists in MariaDB'); $id = (int)$row['id']; $ids[] = $id;
    check((int)$row['owner_id'] === $ownerId && (int)$row['contact_visible'] === 0, 'Server assigns creator ownership and private contact default');
    [$status, $body] = request('item.php?id=' . $id); check($status === 200 && str_contains($body, '&lt;script&gt;') && !str_contains($body, '<script>alert(1)</script>'), 'Details render stored text with HTML escaping');
    [$status, $form] = request('item-form.php?id=' . $id); check($status === 200 && str_contains($form, 'Library'), 'Edit form contains persisted values');
    $data['csrf'] = csrfToken($form); $data['location'] = 'Main hall';
    [$status] = request('item-form.php?id=' . $id, array_replace($data, ['location' => ''])); check($status === 422, 'Invalid update rejected');
    [$status] = request('item-form.php?id=' . $id, $data); check($status === 303, 'Valid update accepted');
    $q = $pdo->prepare('SELECT location FROM items WHERE id=?'); $q->execute([$id]); check($q->fetchColumn() === 'Main hall', 'Updated value persists in database');
    $second = array_replace($data, ['name' => $prefix . ' A keys', 'type' => 'found', 'event_date' => '2026-01-01']);
    [$status] = request('item-form.php', $second); check($status === 303, 'Second report created for filtering tests');
    $q = $pdo->prepare('SELECT id FROM items WHERE name=?'); $q->execute([$second['name']]); $id2 = (int)$q->fetchColumn(); $ids[] = $id2;
    // Test real authorization boundaries with separate cookie jars and valid CSRF tokens.
    $cookie = $otherCookie; [$status, $body] = request('index.php'); $otherToken = csrfToken($body);
    foreach (['item-form.php', 'delete.php'] as $path) {
        [$status] = request($path . '?id=' . $id); check($status === 403, 'Non-owner GET denied: ' . $path);
        [$status] = request($path . '?id=' . $id, array_replace($data,['csrf'=>$otherToken,'name'=>'hijacked','owner_id'=>$otherId])); check($status === 403, 'Non-owner POST denied with valid CSRF: ' . $path);
    }
    [$status, $body] = request('item.php?id=' . $id);
    check($status === 200 && !str_contains($body,$data['contact']) && !str_contains($body,$data['reporter_name']) && !str_contains($body,'item-form.php?id=' . $id), 'Public details omit private contact and non-owner management links');
    [$status] = request('contact.php?id=' . $id,['csrf'=>$otherToken]); check($status === 403, 'Non-owner cannot request private contact');
    $cookie = $guestCookie;
    [$status,$body]=request('index.php'); check($status===200 && !str_contains($body,$data['contact']) && !str_contains($body,$data['reporter_name']),'Anonymous browsing omits contact and reporter name');
    [$status,$body]=request('index.php?'.http_build_query(['q'=>$prefix,'type'=>'lost','category'=>$category,'sort'=>'oldest'])); check($status===200 && str_contains($body,'item.php?id='.$id.'"') && !str_contains($body,'item.php?id='.$id2.'"'),'Anonymous search, filter and sort remain available');
    [$status, $body] = request('item.php?id=' . $id); check($status === 200 && !str_contains($body,$data['contact']), 'Anonymous details remain public without contact data');
    [$status] = request('contact.php?id=' . $id,['csrf'=>$otherToken]); check($status === 401, 'Anonymous contact request denied');
    [$status] = request('delete.php?id=' . $id,['csrf'=>$token]); check($status === 401, 'Anonymous delete POST denied');
    [$status] = request('item-form.php?id=' . $id,['csrf'=>$token]); check($status === 401, 'Anonymous edit POST denied');
    $cookie = $ownerCookie;
    [$status, $body, $headers] = request('contact.php?id=' . $id,['csrf'=>$token]);
    check($status === 200 && str_contains($body,$data['contact']) && ($headers['cache-control'] ?? '') === 'no-store', 'Owner may request own contact with no-store response');
    [$status] = request('contact.php?id=' . $id); check($status === 405, 'Contact disclosure rejects GET');
    [$status] = request('contact.php?id=' . $id,['csrf'=>'wrong']); check($status === 403, 'Contact disclosure requires CSRF');
    [$status] = request('item-form.php?id=' . $id,array_replace($data,['contact_visible'=>'1','owner_id'=>$otherId])); check($status === 303, 'Legacy sharing field does not prevent report update');
    $q = $pdo->prepare('SELECT owner_id FROM items WHERE id=?'); $q->execute([$id]); check((int)$q->fetchColumn() === $ownerId, 'Forged owner field cannot transfer ownership');
    $cookie = $otherCookie;
    [$status, $body] = request('item.php?id=' . $id); check($status === 200 && !str_contains($body,$data['contact']), 'Opted-in contact is absent from ordinary detail HTML');
    [$status, $body] = request('contact.php?id=' . $id,['csrf'=>$otherToken]); check($status === 403 && !str_contains($body,$data['contact']), 'Contact remains private even when the legacy sharing flag is set');
    $cookie = $guestCookie; [$status, $body] = request('item.php?id=' . $id); check($status === 200 && !str_contains($body,$data['contact']), 'Opted-in contact is still absent from anonymous HTML');
    $cookie = $ownerCookie;
    [$status] = request('item-form.php?id=' . $id,$data); check($status === 303, 'Report update remains functional with private contacts');
    $cookie = $otherCookie; [$status] = request('contact.php?id=' . $id,['csrf'=>$otherToken]); check($status === 403, 'Revoked sharing takes effect on next request');
    // A synthetic legacy fixture avoids altering the user's real legacy reports.
    $legacyName = $prefix . ' legacy';
    $q = $pdo->prepare('INSERT INTO items (category_id,name,type,description,location,event_date,reporter_name,contact) VALUES (?,?,?,?,?,?,?,?)');
    $q->execute([$category,$legacyName,'lost','','Library','2026-01-01','Legacy test','legacy@example.invalid']); $legacyId=(int)$pdo->lastInsertId(); $ids[]=$legacyId;
    foreach ([$ownerCookie,$otherCookie] as $jar) {
        $cookie=$jar; [$status,$body]=request('index.php'); $csrf=csrfToken($body);
        [$status]=request('item-form.php?id='.$legacyId); check($status===403,'Legacy report edit form stays locked');
        [$status]=request('item-form.php?id='.$legacyId,array_replace($data,['csrf'=>$csrf])); check($status===403,'Legacy report edit POST stays locked');
        [$status]=request('delete.php?id='.$legacyId,['csrf'=>$csrf]); check($status===403,'Legacy report deletion stays locked');
        [$status]=request('contact.php?id='.$legacyId,['csrf'=>$csrf]); check($status===403,'Legacy report contact stays private');
    }
    $cookie=$ownerCookie;
    $cookie=$guestCookie; [$status,$body]=request('item.php?id='.$legacyId); check($status===200 && !str_contains($body,'legacy@example.invalid'),'Legacy details remain public without reporter data'); $cookie=$ownerCookie;
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
    foreach (['config/database.php','includes/bootstrap.php','database/schema.sql','database/migrations/001_report_ownership.sql','database/backups/before-ownership-2026-10-28.sql','tests/integration.php'] as $path) { [$status] = request($path); check($status === 403, 'Apache denies direct access: ' . $path); }
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
    // Fixed-window throttling is exercised using this test's unique user bucket.
    $cookie=$otherCookie;
    $bucketKey=hash('sha256','contact-user:'.$ownerId);
    $q=$pdo->prepare('INSERT INTO rate_limits (bucket_key,attempts,expires_at) VALUES (?,20,DATE_ADD(UTC_TIMESTAMP(), INTERVAL 1 HOUR)) ON DUPLICATE KEY UPDATE attempts=20,expires_at=VALUES(expires_at)'); $q->execute([$bucketKey]);
    // The second report belongs to owner A; opt in so the request reaches the limiter.
    $cookie=$ownerCookie; [$status]=request('item-form.php?id='.$id2,array_replace($second,['contact_visible'=>'1'])); check($status===303,'Owner can update report before contact rate-limit test');
    $cookie=$ownerCookie; [$status,$body,$headers]=request('contact.php?id='.$id2,['csrf'=>$token]); check($status===429 && isset($headers['retry-after']),'Contact requests are persistently rate limited');
    $cookie=$otherCookie;
    [$status]=request('logout.php'); check($status===405,'Logout rejects GET');
    [$status]=request('logout.php',['csrf'=>'bad']); check($status===403,'Logout requires CSRF');
    [$status]=request('logout.php',['csrf'=>$otherToken]); check($status===303,'Valid logout succeeds');
    [$status]=request('item-form.php',array_replace($data,['csrf'=>$otherToken])); check($status===401,'Logged-out session cannot create reports');
    $cookie=$guestCookie; [$status,$body]=request('login.php'); $guestToken=csrfToken($body);
    $q=$pdo->prepare('INSERT INTO rate_limits (bucket_key,attempts,expires_at) VALUES (?,15,DATE_ADD(UTC_TIMESTAMP(),INTERVAL 15 MINUTE)) ON DUPLICATE KEY UPDATE attempts=15,expires_at=VALUES(expires_at)'); $q->execute([hash('sha256','login-username:'.$accountPrefix.'_a')]);
    [$status]=request('login.php',['csrf'=>$guestToken,'username'=>$accountPrefix.'_a','password'=>$password]); check($status===429,'Login throttling blocks further attempts');
    echo "\n$passed checks passed.\n";
} finally {
    $delete = $pdo->prepare('DELETE FROM items WHERE id=?'); foreach ($ids as $id) $delete->execute([$id]);
    $deleteUser=$pdo->prepare('DELETE FROM users WHERE id=?'); foreach($userIds as $userId) $deleteUser->execute([$userId]);
    $deleteBucket=$pdo->prepare('DELETE FROM rate_limits WHERE bucket_key=?');
    foreach($userIds as $userId) foreach(['contact-user','create-user'] as $scope) $deleteBucket->execute([hash('sha256',$scope.':'.$userId)]);
    if(isset($accountPrefix)) foreach(['_a','_b'] as $suffix) $deleteBucket->execute([hash('sha256','login-username:'.$accountPrefix.$suffix)]);
    foreach($allCookies as $jar) if(is_file($jar)) unlink($jar);
}
