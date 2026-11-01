<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
$base=rtrim($argv[1] ?? 'http://127.0.0.1:8093','/');
$config=require __DIR__.'/../config/database.php';
// This suite must never run against the application or a remote database.
if ($config['host']!=='127.0.0.1' || !preg_match('/^ulf_message_test_[a-f0-9]{12}$/D',$config['name'])) throw new RuntimeException('Use the isolated test runner.');
$pdo=new PDO("mysql:host=127.0.0.1;port={$config['port']};dbname={$config['name']};charset=utf8mb4",$config['user'],$config['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$passed=0; $jars=[]; $users=[]; $item=null; $legacy=null;
function mc(bool $ok,string $label):void { global $passed; if(!$ok)throw new RuntimeException('FAIL: '.$label); $passed++;echo 'PASS: '.$label.PHP_EOL; }
function req(string $path,int $actor=0,?array $data=null):array {
    global $base,$jars;
    $jars[$actor] ??= tempnam(sys_get_temp_dir(),'ulf-message-');
    $ch=curl_init($base.'/'.$path);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_COOKIEFILE=>$jars[$actor],CURLOPT_COOKIEJAR=>$jars[$actor],CURLOPT_TIMEOUT=>15]);
    if($data!==null)curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query($data)]);
    $body=curl_exec($ch);$status=curl_getinfo($ch,CURLINFO_RESPONSE_CODE);curl_setopt($ch,CURLOPT_COOKIELIST,'FLUSH');curl_close($ch);unset($ch);return [$status,(string)$body];
}
function mt(string $body):string { if(!preg_match('/name="csrf" value="([a-f0-9]+)"/',$body,$m))throw new RuntimeException('Missing token'); return $m[1]; }
function badge(string $html):int {
    $dom=new DOMDocument();@$dom->loadHTML('<?xml encoding="UTF-8">'.$html);$xpath=new DOMXPath($dom);
    return (int)$xpath->evaluate('string(//nav//a[@href="messages.php"]//span[contains(@class,"unread-badge")])');
}
function unread(int $actor):int { [$s,$b]=req('messages.php',$actor);if($s!==200)throw new RuntimeException('Inbox unavailable');return badge($b); }
try {
    $prefix='msg_'.bin2hex(random_bytes(5)); $password=bin2hex(random_bytes(20)); $tokens=[];
    for($a=1;$a<=3;$a++) {
        $q=$pdo->prepare('INSERT INTO users (username,password_hash) VALUES (?,?)');$q->execute([$prefix.$a,password_hash($password,PASSWORD_DEFAULT)]);$users[$a]=(int)$pdo->lastInsertId();
        [$s,$b]=req('login.php',$a);$t=mt($b);
        [$s,$b]=req('login.php',$a);mc(hash_equals($t,mt($b)),'CSRF token persists between requests');$t=mt($b);
        [$s]=req('login.php',$a,['csrf'=>$t,'username'=>$prefix.$a,'password'=>$password]);mc($s===303,'Fixture user signs in '.$a);
        [$s,$b]=req('messages.php',$a);$tokens[$a]=null;
        // A fresh authenticated CSRF token is available on the existing create form.
        [$s,$b]=req('item-form.php',$a);$tokens[$a]=mt($b);
    }
    $q=$pdo->prepare("INSERT INTO items (category_id,name,type,description,location,event_date,reporter_name,contact,owner_id,contact_visible) SELECT id,?,'lost','','Campus','2026-01-01','Private reporter','secret@example.invalid',?,1 FROM categories LIMIT 1");
    $q->execute([$prefix,$users[1]]);$item=(int)$pdo->lastInsertId();
    $q->execute([$prefix.' legacy',null]);$legacy=(int)$pdo->lastInsertId();
    [$s,$b]=req('item.php?id='.$item);mc($s===200&&str_contains($b,'Contact reporter')&&!str_contains($b,'secret@example.invalid'),'Public details offer messaging without contact data');
    [$s]=req('messages.php');mc($s===303,'Anonymous inbox redirects to login');
    [$s]=req('conversation.php?item='.$item,0,['body'=>'forged']);mc($s===401,'Anonymous manually constructed send denied');
    [$s]=req('conversation.php?item='.$item,1);mc($s===403,'Reporter cannot message self');
    [$s]=req('conversation.php?item='.$item,1,['csrf'=>$tokens[1],'body'=>'self']);mc($s===403,'Self-send POST denied');
    [$s]=req('conversation.php?item='.$legacy,2);mc($s===403,'Ownerless legacy report cannot receive messages');
    [$s]=req('conversation.php?item='.$item,2,['body'=>'missing token']);mc($s===403,'New conversation requires CSRF');
    foreach(['',str_repeat('x',2001)] as $body){[$s]=req('conversation.php?item='.$item,2,['csrf'=>$tokens[2],'body'=>$body]);mc($s===422,'Empty or oversized body rejected');}
    mc((int)$pdo->query('SELECT COUNT(*) FROM conversations')->fetchColumn()===0,'Invalid sends create no empty conversations');
    $payload='<script>alert(1)</script> \' OR 1=1 --';
    [$s]=req('conversation.php?item='.$item,2,['csrf'=>$tokens[2],'body'=>$payload,'reporter_id'=>$users[3]]);mc($s===303,'Valid sender starts conversation');
    $c=$pdo->query('SELECT * FROM conversations')->fetch(PDO::FETCH_ASSOC);$id=(int)$c['id'];
    mc((int)$c['reporter_id']===$users[1],'Recipient derived from report, ignores forged recipient');
    mc(unread(1)===1,'Recipient badge counts one received unread message');
    mc(unread(2)===0,'Sent messages are excluded from sender badge');
    mc(unread(3)===0,'Other accounts do not see another participant unread count');
    [$s,$b]=req('messages.php',3);mc(!str_contains($b,'unread-badge'),'Zero unread count renders no badge');
    [$s,$b]=req('index.php');mc(!str_contains($b,'unread-badge'),'Anonymous page has no unread badge');
    [$s]=req('conversation.php?id='.$id,3);mc($s===404&&unread(1)===1,'Unauthorized read cannot clear recipient notifications');
    [$s]=req('conversation.php?id='.$id,2);mc($s===200&&unread(1)===1,'Sender opening cannot mark recipient messages read');
    [$s]=req('conversation.php?id='.$id,1,['csrf'=>'bad','body'=>'forged']);mc($s===403&&unread(1)===1,'Rejected CSRF request does not mark messages read');
    [$s,$b]=req('conversation.php?id='.$id,1);mc($s===200&&str_contains($b,htmlspecialchars($payload,ENT_QUOTES,'UTF-8'))&&!str_contains($b,'<script>alert'),'Reporter sees escaped message and SQL payload remains text');
    mc(badge($b)===0&&!str_contains($b,'unread-badge')&&unread(1)===0,'Opening conversation clears badge in same response and next request');
    [$s]=req('conversation.php?id='.$id,1);mc(unread(1)===0&&(int)$pdo->query('SELECT COUNT(*) FROM message_reads')->fetchColumn()===1,'Repeated opening is idempotent');
    mc(!str_contains($b,'secret@example.invalid'),'Conversation does not disclose stored contact');
    [$s,$b]=req('messages.php',1);mc($s===200&&str_contains($b,'conversation.php?id='.$id),'Reporter receives private inbox entry');
    [$s,$b]=req('messages.php',3);mc($s===200&&!str_contains($b,'conversation.php?id='.$id),'Third-party inbox excludes conversation');
    [$s]=req('conversation.php?id='.$id,0);mc($s===303,'Anonymous cannot read conversation');
    [$s]=req('conversation.php?id='.$id,0,['body'=>'attack']);mc($s===401,'Anonymous cannot reply');
    [$s]=req('conversation.php?id='.$id,3);mc($s===404,'Third party cannot read guessed conversation ID');
    [$s]=req('conversation.php?id='.$id,3,['csrf'=>$tokens[3],'body'=>'attack']);mc($s===404,'Third party with valid CSRF cannot reply');
    [$s]=req('conversation.php?id='.$id,1,['csrf'=>'bad','body'=>'reply']);mc($s===403,'Replies require valid CSRF');
    [$s]=req('conversation.php?id='.$id,1,['csrf'=>$tokens[1],'body'=>'Reporter reply']);mc($s===303,'Reporter replies');
    mc(unread(2)===1&&unread(1)===0,'Reply notifies the original sender, not its author');
    [$s]=req('conversation.php?id='.$id,2,['csrf'=>$tokens[2],'body'=>'Sender reply']);mc($s===303,'Sender replies in same conversation');
    mc(unread(2)===1&&unread(1)===1,'Sending alone does not mark received messages read');
    [$s,$b]=req('conversation.php?id='.$id.'&user_id='.$users[1],2);mc($s===200&&badge($b)===0&&unread(1)===1,'Opening marks only signed-in recipient despite forged user ID');
    [$s]=req('conversation.php?item='.$item,2,['csrf'=>$tokens[2],'body'=>'Again']);mc($s===303&&(int)$pdo->query('SELECT COUNT(*) FROM conversations')->fetchColumn()===1,'Repeated contact reuses existing conversation');
    mc(unread(1)===2,'Badge counts messages rather than conversations');
    [$s]=req('contact.php?id='.$item,2,['csrf'=>$tokens[2]]);mc($s===403,'Old contact endpoint cannot reveal phone or email despite sharing flag');
    [$s]=req('conversation.php?id=1%20OR%201=1',2);mc($s===404,'Malformed conversation ID rejected');
    $q=$pdo->prepare('INSERT INTO rate_limits (bucket_key,attempts,expires_at) VALUES (?,30,DATE_ADD(UTC_TIMESTAMP(),INTERVAL 1 HOUR)) ON DUPLICATE KEY UPDATE attempts=30');$q->execute([hash('sha256','message-user:'.$users[2])]);
    [$s]=req('conversation.php?id='.$id,2,['csrf'=>$tokens[2],'body'=>'throttle']);mc($s===429,'Message sending persistently rate limited');
    $pdo->prepare('DELETE FROM items WHERE id=?')->execute([$item]);$item=null;
    [$s,$b]=req('conversation.php?id='.$id,1);mc($s===200&&str_contains($b,'Deleted report')&&str_contains($b,'Sender reply'),'Deleting a report preserves conversation history');
    mc(unread(1)===0,'Read tracking works after report deletion');
    [$s]=req('conversation.php?id='.$id,1,['csrf'=>$tokens[1],'body'=>'After removal']);mc($s===303,'Participants can reply after report deletion');
    mc(unread(2)===1,'New arrivals after previous opening remain unread');
    // A second conversation must not be cleared by opening the first one.
    $pdo->prepare('INSERT INTO conversations (item_id,sender_id,reporter_id) VALUES (NULL,?,?)')->execute([$users[3],$users[2]]);
    $otherId=(int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO messages(conversation_id,author_id,body) VALUES (?,?,?)')->execute([$otherId,$users[3],'Different conversation']);
    mc(unread(2)===2,'Badge sums received unread messages across conversations');
    [$s]=req('conversation.php?id='.$id,2);mc(unread(2)===1,'Opening one conversation leaves another unread');
    [$s]=req('conversation.php?id='.$otherId,2);mc(unread(2)===0,'Opening second conversation clears remaining badge');
    $q=$pdo->prepare('INSERT INTO messages(conversation_id,author_id,body) VALUES (?,?,?)');
    for($i=0;$i<55;$i++)$q->execute([$otherId,$users[3],'History '.$i]);
    mc(unread(2)===55,'Unread count includes messages beyond one history page');
    [$s]=req('conversation.php?id='.$otherId,2);mc($s===200&&unread(2)===0,'Opening marks all current received history read, including older pages');
    echo "$passed messaging checks passed.\n";
} finally {
    foreach($users as $uid){$pdo->prepare('DELETE FROM conversations WHERE sender_id=? OR reporter_id=?')->execute([$uid,$uid]);}
    foreach([$item,$legacy] as $iid)if($iid)$pdo->prepare('DELETE FROM items WHERE id=?')->execute([$iid]);
    foreach($users as $uid)$pdo->prepare('DELETE FROM users WHERE id=?')->execute([$uid]);
    foreach($jars as $jar)if(is_file($jar))unlink($jar);
}
