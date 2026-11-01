<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
// Requires a local setup account with CREATE/DROP DATABASE privileges.
// Only the random database created by this script is removed; the app DB is untouched.
$config = require __DIR__ . '/../config/database.php';
$pdo = new PDO("mysql:host={$config['host']};port={$config['port']};charset=utf8mb4", $config['user'], $config['password'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$testDb = 'ulf_schema_test_' . bin2hex(random_bytes(6));
$created = false; $passed = 0;
function assert_schema(bool $condition, string $label): void {
    global $passed;
    if (!$condition) throw new RuntimeException('FAIL: '.$label);
    $passed++; echo 'PASS: '.$label."\n";
}
try {
    $pdo->exec('CREATE DATABASE `'.$testDb.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'); $created=true;
    $sql = file_get_contents(__DIR__.'/../database/schema.sql');
    $pdo->exec(str_replace('university_lost_found',$testDb,$sql));
    $pdo->exec(str_replace('university_lost_found',$testDb,$sql));
    assert_schema((int)$pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn()===6,'Fresh schema imports and reimports without duplicating categories');
    $names=$pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN); sort($names);
    assert_schema($names===['categories','conversations','items','message_reads','messages','rate_limits','users'],'Fresh schema contains all seven expected tables');
    $pdo->exec("INSERT INTO items (category_id,name,type,description,location,event_date,reporter_name,contact) SELECT id,'Schema fixture','lost','','Campus','2026-01-01','Fixture','fixture@example.invalid' FROM categories LIMIT 1");
    $row=$pdo->query('SELECT owner_id,contact_visible,description FROM items')->fetch(PDO::FETCH_ASSOC);
    assert_schema($row['owner_id']===null && (int)$row['contact_visible']===0 && $row['description']==='','Fresh schema defaults are locked/private and accept empty description');
    // Compare structural column/FK metadata to the live, migrated application DB.
    $query='SELECT TABLE_NAME,COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,COLUMN_DEFAULT,EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? ORDER BY TABLE_NAME,COLUMN_NAME';
    $q=$pdo->prepare($query); $q->execute([$testDb]); $fresh=$q->fetchAll(PDO::FETCH_ASSOC); $q->execute([$config['name']]); $migrated=$q->fetchAll(PDO::FETCH_ASSOC);
    assert_schema($fresh===$migrated,'Fresh and migrated database column definitions match');
    $q=$pdo->prepare('SELECT TABLE_NAME,COLUMN_NAME,REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=? AND REFERENCED_TABLE_NAME IS NOT NULL ORDER BY TABLE_NAME,COLUMN_NAME');
    $q->execute([$testDb]); $fresh=$q->fetchAll(PDO::FETCH_ASSOC); $q->execute([$config['name']]); $migrated=$q->fetchAll(PDO::FETCH_ASSOC);
    assert_schema($fresh===$migrated,'Fresh and migrated foreign keys match');
    echo "\n$passed schema checks passed.\n";
} finally {
    if ($created && preg_match('/^ulf_schema_test_[a-f0-9]{12}$/D',$testDb)) $pdo->exec('DROP DATABASE `'.$testDb.'`');
}
