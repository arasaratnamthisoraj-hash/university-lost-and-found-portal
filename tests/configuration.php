<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
// Process-local environment and disposable PHP fixtures; no database connection.
require_once __DIR__ . '/../config/settings.php';
$names = ['APP_ENV','DB_HOST','DB_PORT','DB_NAME','DB_USER','DB_PASSWORD'];
$original=[]; foreach($names as $name) { $original[$name]=getenv($name); putenv($name); }
$directory=sys_get_temp_dir().'/ulf-config-'.bin2hex(random_bytes(6)); mkdir($directory);
$passed=0;
function config_check(bool $ok,string $label):void { global $passed; if(!$ok)throw new RuntimeException('FAIL: '.$label); $passed++; echo 'PASS: '.$label.PHP_EOL; }
function config_rejected(callable $action,string $label):void { $rejected=false;try{$action();}catch(RuntimeException $e){$rejected=true;}config_check($rejected,$label); }
function config_fixture(string $path,array $value):void { file_put_contents($path,"<?php\nif (!defined('PORTAL_CONFIG_LOADING')) { http_response_code(404); exit; }\nreturn ".var_export($value,true).";\n"); }
try {
    $local=portal_settings($directory);$db=portal_database_config($local);
    config_check(!$local['production']&&$db['name']==='university_lost_found'&&$db['host']==='127.0.0.1','XAMPP defaults still work without private configuration');
    putenv('DB_NAME=isolated_test');config_check(portal_settings($directory)['database']['name']==='isolated_test','Environment override remains supported');putenv('DB_NAME');
    config_fixture($directory.'/deployment.php',['environment'=>'production','canonical_origin'=>'https://university-lost-and-found.infinityfreeapp.com']);
    putenv('APP_ENV=local');config_check(portal_settings($directory)['production'],'Production marker cannot be downgraded through APP_ENV');putenv('APP_ENV');
    config_rejected(fn()=>portal_database_config(portal_settings($directory)),'Production rejects missing credentials rather than using XAMPP defaults');
    $private=['environment'=>'local','database'=>['host'=>'sql303.infinityfree.com','port'=>'3306','name'=>'if0_43303703_university_lost_and_found','user'=>'if0_43303703','password'=>bin2hex(random_bytes(20))]];
    config_fixture($directory.'/local.php',$private);
    $settings=portal_settings($directory);$db=portal_database_config($settings);
    config_check($settings['production']&&$db['host']===$private['database']['host']&&$db['password']===$private['database']['password'],'Private PHP configuration works without environment variables');
    putenv('DB_NAME=isolated_override');config_check(portal_settings($directory)['database']['name']==='isolated_override','Explicit environment variables take precedence over private defaults');putenv('DB_NAME');
    $bad=$settings;$bad['database']['user']='root';config_rejected(fn()=>portal_database_config($bad),'Production rejects administrative default account');
    $bad=$settings;$bad['database']['password']='';config_rejected(fn()=>portal_database_config($bad),'Production rejects empty password');
    $bad=$settings;$bad['database']['port']='70000';config_rejected(fn()=>portal_database_config($bad),'Invalid database port rejected');
    $bad=$settings;$bad['database']['host']='localhost;dbname=other';config_rejected(fn()=>portal_database_config($bad),'DSN-shaped host rejected');
    config_check(portal_is_https(['HTTPS'=>'on'])&&portal_is_https(['HTTPS'=>'1']),'Native HTTPS indicators recognized');
    config_check(!portal_is_https(['HTTPS'=>'off']),'Plain HTTP does not become HTTPS');
    config_check(!portal_is_https(['HTTP_X_FORWARDED_PROTO'=>'https']),'Untrusted forwarded header cannot bypass HTTPS check');
    config_check(!portal_is_https(['SERVER_PORT'=>'443']),'Port number alone cannot bypass HTTPS check');
    config_fixture($directory.'/deployment.php',['environment'=>'production','canonical_origin'=>'https://example.test/unsafe-path']);
    config_rejected(fn()=>portal_settings($directory),'Unsafe canonical origin rejected');
    unlink($directory.'/deployment.php');putenv('APP_ENV=production');config_check(portal_settings($directory)['production'],'Process-level production selection remains supported');
    echo "\n$passed configuration checks passed.\n";
} finally {
    foreach($original as $name=>$value)putenv($value===false?$name:$name.'='.$value);
    foreach(['local.php','deployment.php'] as $file)if(is_file($directory.'/'.$file))unlink($directory.'/'.$file);
    rmdir($directory);
}
