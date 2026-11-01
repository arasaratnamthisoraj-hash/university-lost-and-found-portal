<?php
require __DIR__.'/includes/bootstrap.php';
require __DIR__.'/includes/messaging.php';
allow_methods(['GET','POST']); $user=require_login();
$id=filter_var($_GET['id'] ?? null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
$conversation=null; $item=null;
if (isset($_GET['id'])) {
    if (!$id) fail(404,'Conversation not found.');
    $conversation=conversation_for_user($id,(int)$user['id']);
} else {
    $itemId=filter_var($_GET['item'] ?? null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
    if (!$itemId) fail(404,'Report not found.');
    $item=find_item($itemId);
    if ($item['owner_id']===null || (int)$item['owner_id']===(int)$user['id']) fail(403,'This reporter cannot be messaged.');
}
$body=''; $error=null;
if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf(); $body=input($_POST,'body'); $error=message_error($body);
    if (!$error) {
        rate_limit('message-user',(string)$user['id'],30,3600);
        if ($conversation) {
            // Check participants again in the INSERT, not just in the page controller.
            $q=db()->prepare('INSERT INTO messages (conversation_id,author_id,body) SELECT id,?,? FROM conversations WHERE id=? AND (sender_id=? OR reporter_id=?)');
            $q->execute([$user['id'],$body,$id,$user['id'],$user['id']]);
            if ($q->rowCount()!==1) fail(404,'Conversation not found.');
        } else $id=start_conversation((int)$itemId,(int)$user['id'],$body);
        redirect('conversation.php?id='.$id);
    }
    http_response_code(422);
}
$rows=[]; $more=false;
if ($conversation) {
    // Read state is scoped to the authenticated participant, never a posted user ID.
    // Opening marks all currently received messages, including older history, read.
    if ($_SERVER['REQUEST_METHOD']==='GET') mark_conversation_read((int)$id,(int)$user['id']);
    $before=filter_var($_GET['before'] ?? null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]) ?: PHP_INT_MAX;
    $q=db()->prepare('SELECT m.id,m.body,m.created_at,u.username FROM messages m JOIN conversations c ON c.id=m.conversation_id JOIN users u ON u.id=m.author_id WHERE c.id=? AND (c.sender_id=? OR c.reporter_id=?) AND m.id<? ORDER BY m.id DESC LIMIT 51');
    $q->execute([$id,$user['id'],$user['id'],$before]); $rows=$q->fetchAll();
    $more=count($rows)>50; $rows=array_slice($rows,0,50);
}
$title='Private conversation'; require __DIR__.'/includes/header.php';
?>
<section class="narrow panel"><a href="messages.php">&larr; Private inbox</a><h1><?= e($conversation['item_name'] ?? $item['name'] ?? 'Deleted report') ?></h1><p>Private conversation. Do not share passwords or sensitive personal information. Messages are stored on the server; they are not end-to-end encrypted.</p>
<?php if($more): ?><p><a href="conversation.php?id=<?= e($id) ?>&amp;before=<?= e(end($rows)['id']) ?>">Older messages</a></p><?php endif; ?>
<?php foreach(array_reverse($rows) as $message): ?><article class="panel"><p><strong><?= e($message['username']) ?></strong> <span><?= e($message['created_at']) ?></span></p><p class="description message-body"><?= e($message['body']) ?></p></article><?php endforeach; ?>
<?php if($error): ?><p class="error-summary" role="alert"><?= e($error) ?></p><?php endif; ?>
<form method="post" class="auth-form"><input type="hidden" name="csrf" value="<?= e(token()) ?>"><label for="body">Message</label><textarea id="body" name="body" rows="5" maxlength="2000" required><?= e($body) ?></textarea><button>Send message</button></form></section>
<?php require __DIR__.'/includes/footer.php'; ?>
