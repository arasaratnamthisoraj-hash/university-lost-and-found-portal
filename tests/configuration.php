<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
// Process-local environment only; never connects to or changes a database.
$names=['APP_ENV','DB_USER','DB_PASSWORD']; $original=[]; foreach($names as $name) $original[$name]=getenv($name);
$passed=0;
try {
    putenv('APP_ENV=production'); putenv('DB_USER'); putenv('DB_PASSWORD');
    try { require __DIR__.'/../config/database.php'; throw new RuntimeException('Missing production credentials were accepted'); }
    catch (RuntimeException $error) { if ($error->getMessage()==='Missing production credentials were accepted') throw $error; $passed++; echo "PASS: Production rejects missing database credentials\n"; }
    putenv('DB_USER=root'); putenv('DB_PASSWORD=process-only-test-value');
    try { require __DIR__.'/../config/database.php'; throw new RuntimeException('Administrative production account was accepted'); }
    catch (RuntimeException $error) { if ($error->getMessage()==='Administrative production account was accepted') throw $error; $passed++; echo "PASS: Production rejects the default administrative account\n"; }
    putenv('DB_USER=restricted_test_user');
    $config=require __DIR__.'/../config/database.php';
    if($config['user']!=='restricted_test_user') throw new RuntimeException('Explicit configuration was not accepted');
    $passed++; echo "PASS: Production accepts explicit non-default configuration (no connection attempted)\n";
    echo "\n$passed configuration checks passed.\n";
} finally {
    foreach($original as $name=>$value) putenv($value===false ? $name : $name.'='.$value);
}
