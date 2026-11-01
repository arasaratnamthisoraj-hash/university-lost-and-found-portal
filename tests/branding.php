<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
$base=rtrim($argv[1]??'http://localhost/university-lost-and-found-portal','/');
$disclaimer='Independent student project — not an official university service or endorsed by any university.';
$academicTitle='University Lost & Found Portal';$passed=0;
function branding_check(bool $ok,string $label):void{global $passed;if(!$ok)throw new RuntimeException('FAIL: '.$label);$passed++;echo 'PASS: '.$label.PHP_EOL;}
foreach(['index.php','login.php','register.php'] as $page){
    $ch=curl_init($base.'/'.$page);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_TIMEOUT=>15]);$html=curl_exec($ch);$status=curl_getinfo($ch,CURLINFO_RESPONSE_CODE);curl_close($ch);
    branding_check($status===200&&is_string($html),$page.' renders');
    $dom=new DOMDocument();@$dom->loadHTML('<?xml encoding="UTF-8">'.$html);$xpath=new DOMXPath($dom);
    $title=trim($xpath->evaluate('string(//title)'));
    branding_check(str_contains($title,$academicTitle)&&str_contains($title,'Independent Student Project')&&!str_contains($title,'University of Ruhuna'),$page.' title uses independent university project branding');
    branding_check($xpath->query('//header//*[contains(@class,"student-project-label")]')->length===1&&str_contains($xpath->evaluate('string(//header)'),'Independent Student Project'),$page.' header prominently identifies student project');
    branding_check($xpath->query('//header//*[contains(@class,"project-mark")]//*[local-name()="svg"]')->length===1,$page.' uses original SVG project mark');
    $notices=$xpath->query('//main//p[contains(@class,"project-disclaimer")]');$found=false;foreach($notices as $notice)if(trim($notice->textContent)===$disclaimer)$found=true;
    branding_check($found,$page.' contains exact disclaimer in main content');
    branding_check(!str_contains($html,'ruhuna-logo')&&$xpath->query('//img')->length===0,$page.' contains no institutional logo image');
    branding_check($xpath->query('//header//a[contains(@href,"ruh.ac.lk")] | //footer//a[contains(@href,"ruh.ac.lk")]')->length===0,$page.' has no institutional affiliation links in shared branding');
}
echo "\n$passed branding checks passed.\n";
