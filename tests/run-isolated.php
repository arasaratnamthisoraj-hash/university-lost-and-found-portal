<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
// Local XAMPP only. Never loads the project's private configuration.
require __DIR__.'/../deployment/build-infinityfree.php';
$root=dirname(__DIR__);$nonce=bin2hex(random_bytes(6));$name='ulf_message_test_'.$nonce;
$directory=$root.'/tmp/message-test-'.$nonce;$server=null;$created=false;
$pdo=new PDO('mysql:host=127.0.0.1;port=3306;charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
function run_check(array $args,string $cwd,array $env):void {
    $process=proc_open($args,[0=>['pipe','r'],1=>STDOUT,2=>STDERR],$pipes,$cwd,$env);
    if(!is_resource($process))throw new RuntimeException('Cannot launch test');
    fclose($pipes[0]);if(proc_close($process)!==0)throw new RuntimeException('Test failed: '.basename($args[1]));
}
try {
    mkdir($directory,0775,true);
    foreach(infinityfree_runtime_files() as $file){if(!is_dir(dirname($directory.'/'.$file)))mkdir(dirname($directory.'/'.$file),0775,true);copy($root.'/'.$file,$directory.'/'.$file);}
    foreach(['database','database/migrations','deployment','tests'] as $sub)if(!is_dir($directory.'/'.$sub))mkdir($directory.'/'.$sub,0775,true);
    foreach(['database/.htaccess','tests/.htaccess','deployment/.htaccess','database/schema.sql','database/migrations/002_private_messages.sql','database/migrations/003_message_reads.sql','deployment/build-infinityfree.php','tests/integration.php','tests/messaging.php','tests/branding.php','tests/schema.php','tests/configuration.php','tests/deployment.php'] as $file)copy($root.'/'.$file,$directory.'/'.$file);
    // Explicit local-only fixture settings; never copy local.php or deployment.php.
    file_put_contents($directory.'/config/local.php',"<?php return ".var_export(['environment'=>'local','database'=>['host'=>'127.0.0.1','port'=>'3306','name'=>$name,'user'=>'root','password'=>'']],true).';');
    $pdo->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');$created=true;
    $sql=str_replace('university_lost_found',$name,file_get_contents($root.'/database/schema.sql'));
    // Build a pre-messaging schema to check that the additive migration preserves rows.
    $base=explode('-- Additive migration.',$sql)[0];$pdo->exec($base);
    $pdo->exec("INSERT INTO users(username,password_hash) VALUES('migration_fixture','not-a-login-hash')");
    $pdo->exec("INSERT INTO items(category_id,name,type,description,location,event_date,reporter_name,contact,owner_id) SELECT id,'Migration fixture','lost','','Campus','2026-01-01','Fixture','private',1 FROM categories LIMIT 1");
    $before=$pdo->query('SELECT * FROM items')->fetchAll();$users=$pdo->query('SELECT * FROM users')->fetchAll();
    $migration=file_get_contents($root.'/database/migrations/002_private_messages.sql');$pdo->exec($migration);$pdo->exec($migration);
    if($before!==$pdo->query('SELECT * FROM items')->fetchAll() || $users!==$pdo->query('SELECT * FROM users')->fetchAll())throw new RuntimeException('Migration changed existing rows');
    echo "PASS: Migration and repeat import preserve fixture reports/accounts exactly.\n";
    // Verify read tracking is purely additive, including existing message contents.
    $pdo->exec("INSERT INTO users(username,password_hash) VALUES('migration_sender','not-a-login-hash')");
    $pdo->exec('INSERT INTO conversations(item_id,sender_id,reporter_id) VALUES(1,2,1)');
    $pdo->exec("INSERT INTO messages(conversation_id,author_id,body) VALUES(1,2,'Existing private message')");
    $beforeMessages=$pdo->query('SELECT * FROM messages')->fetchAll();
    $beforeConversations=$pdo->query('SELECT * FROM conversations')->fetchAll();
    $reads=file_get_contents($root.'/database/migrations/003_message_reads.sql');$pdo->exec($reads);$pdo->exec($reads);
    if($beforeMessages!==$pdo->query('SELECT * FROM messages')->fetchAll() || $beforeConversations!==$pdo->query('SELECT * FROM conversations')->fetchAll() || $before!==$pdo->query('SELECT * FROM items')->fetchAll())throw new RuntimeException('Read migration changed existing records');
    echo "PASS: Read-tracking migration and repeat import preserve existing messages, conversations and reports.\n";
    $pdo->exec('DELETE FROM conversations');
    $pdo->exec('DELETE FROM items');$pdo->exec('DELETE FROM users');
    $env=getenv();$env['APP_ENV']='local';$env['DB_HOST']='127.0.0.1';$env['DB_PORT']='3306';$env['DB_NAME']=$name;$env['DB_USER']='root';$env['DB_PASSWORD']='';
    mkdir($directory.'/config/sessions',0700);
    file_put_contents($directory.'/.htaccess',"\nRequire local\nphp_value session.save_path \"".str_replace('\\','/',$directory)."/config/sessions\"\nSetEnv APP_ENV local\nSetEnv DB_HOST 127.0.0.1\nSetEnv DB_PORT 3306\nSetEnv DB_USER root\nSetEnv DB_PASSWORD \"\"\nSetEnv DB_NAME ".$name."\n",FILE_APPEND);
    $base=rtrim($argv[1] ?? 'http://localhost/'.rawurlencode(basename($root)),'/').'/tmp/message-test-'.$nonce;
    foreach(['branding','messaging','integration','schema','configuration','deployment'] as $test)run_check([PHP_BINARY,$directory.'/tests/'.$test.'.php',$base],$directory,$env);
    echo "All isolated suites passed. No application database or private configuration was used.\n";
} finally {
    if(is_resource($server)){proc_terminate($server);proc_close($server);}
    if($created && preg_match('/^ulf_message_test_[a-f0-9]{12}$/D',$name))$pdo->exec('DROP DATABASE `'.$name.'`');
    // Only this runner's randomly named, verified workspace fixture may be removed.
    if(is_dir($directory) && str_starts_with($directory,$root.'/tmp/message-test-') && basename($directory)==='message-test-'.$nonce){
        $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);
        foreach($it as $file){if($file->isDir()&&!$file->isLink())rmdir($file->getPathname());else unlink($file->getPathname());}rmdir($directory);
    }
}
