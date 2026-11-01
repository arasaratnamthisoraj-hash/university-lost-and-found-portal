<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require_once __DIR__.'/../deployment/build-infinityfree.php';
require_once __DIR__.'/../config/settings.php';
$root=dirname(__DIR__);$directory=sys_get_temp_dir().'/ulf-build-'.bin2hex(random_bytes(6));$passed=0;
function deployment_check(bool $ok,string $label):void{global $passed;if(!$ok)throw new RuntimeException('FAIL: '.$label);$passed++;echo 'PASS: '.$label.PHP_EOL;}
try{
    $count=build_infinityfree($root,$directory);
    deployment_check($count===24,'Build contains exactly 24 allowlisted runtime files');
    $expected=array_merge(infinityfree_runtime_files(),['config/deployment.php']);$actual=[];
    foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory,FilesystemIterator::SKIP_DOTS)) as $file)if($file->isFile())$actual[]=str_replace('\\','/',substr($file->getPathname(),strlen($directory)+1));
    sort($actual);sort($expected);deployment_check($actual===$expected,'Package has no tests, documentation, SQL, backups, archives or private configuration');
    $settings=portal_settings($directory.'/config');deployment_check($settings['production']&&$settings['canonical_origin']==='https://university-lost-and-found.infinityfreeapp.com','Package forces production and the fixed canonical origin');
    $blocked=false;try{portal_database_config($settings);}catch(RuntimeException $e){$blocked=true;}deployment_check($blocked,'Credential-free production package fails closed until configured');
    deployment_check(file_get_contents($directory.'/config/.htaccess')==="Require all denied\n"||trim(file_get_contents($directory.'/config/.htaccess'))==='Require all denied','Private configuration directory retains access denial');
    $rules=file_get_contents($directory.'/.htaccess');deployment_check(str_contains($rules,'RewriteRule ^(?:config|includes)')&&str_contains($rules,'RewriteCond %{HTTPS} !=on'),'Package adds independent config blocking and native HTTPS redirect');
    deployment_check(!is_file($directory.'/assets/images/ruhuna-logo.png.png')&&!str_contains(file_get_contents($directory.'/includes/header.php'),'ruhuna-logo'),'Official university logo is absent from the package and header');
    if(!is_dir($directory.'/assets/images'))mkdir($directory.'/assets/images');
    file_put_contents($directory.'/assets/images/ruhuna-logo.png.png','retired build fixture');
    build_infinityfree($root,$directory);
    deployment_check(!is_file($directory.'/assets/images/ruhuna-logo.png.png'),'Rebuild removes only the known retired logo artifact');
    file_put_contents($directory.'/config/local.php','<?php return [];');$rejected=false;try{build_infinityfree($root,$directory);}catch(RuntimeException $e){$rejected=true;}deployment_check($rejected,'Rebuild refuses to include or overwrite an unexpected private config');
    echo "\n$passed deployment checks passed.\n";
}finally{
    if(is_dir($directory)){foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST) as $file){if($file->isDir())rmdir($file->getPathname());else unlink($file->getPathname());}rmdir($directory);}
}
