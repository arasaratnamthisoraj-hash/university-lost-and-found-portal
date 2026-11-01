<?php
declare(strict_types=1);

function unread_message_count(int $userId): int {
    $q = db()->prepare('SELECT COUNT(*) FROM conversations c JOIN messages m ON m.conversation_id=c.id LEFT JOIN message_reads r ON r.message_id=m.id AND r.user_id=? WHERE (c.sender_id=? OR c.reporter_id=?) AND m.author_id<>? AND r.message_id IS NULL');
    $q->execute([$userId,$userId,$userId,$userId]);
    return (int)$q->fetchColumn();
}

function mark_conversation_read(int $conversationId, int $userId): void {
    // Store receipts for the exact received messages visible to this statement.
    // A concurrent arrival cannot be swallowed by an ID-based read watermark.
    // Membership is enforced in SQL, and repeated openings are idempotent.
    $q = db()->prepare('INSERT INTO message_reads (message_id,user_id) SELECT m.id,? FROM messages m JOIN conversations c ON c.id=m.conversation_id WHERE m.author_id<>? AND c.id=? AND (c.sender_id=? OR c.reporter_id=?) ON DUPLICATE KEY UPDATE message_id=VALUES(message_id)');
    $q->execute([$userId,$userId,$conversationId,$userId,$userId]);
}

function conversation_for_user(int $id, int $userId): array {
    $q = db()->prepare('SELECT c.*, i.name AS item_name FROM conversations c LEFT JOIN items i ON i.id=c.item_id WHERE c.id=? AND (c.sender_id=? OR c.reporter_id=?)');
    $q->execute([$id, $userId, $userId]);
    $conversation = $q->fetch();
    if (!$conversation) fail(404, 'Conversation not found.');
    return $conversation;
}

function message_error(string $body): ?string {
    return $body === '' || mb_strlen($body) > 2000 || str_contains($body, "\0")
        ? 'Enter a message of 1–2000 characters.' : null;
}

function start_conversation(int $itemId, int $senderId, string $body): int {
    $pdo = db();
    $pdo->beginTransaction();
    try {
        // Lock the report until the first message is saved; never trust a posted recipient.
        $q = $pdo->prepare('SELECT owner_id FROM items WHERE id=? FOR UPDATE');
        $q->execute([$itemId]); $owner = $q->fetch();
        if (!$owner || $owner['owner_id'] === null || (int)$owner['owner_id'] === $senderId) {
            $pdo->rollBack(); fail(403, 'This reporter cannot be messaged.');
        }
        $q = $pdo->prepare('INSERT INTO conversations (item_id,sender_id,reporter_id) VALUES (?,?,?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)');
        $q->execute([$itemId, $senderId, $owner['owner_id']]);
        $id = (int)$pdo->lastInsertId();
        $q = $pdo->prepare('INSERT INTO messages (conversation_id,author_id,body) VALUES (?,?,?)');
        $q->execute([$id, $senderId, $body]);
        $pdo->commit(); return $id;
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
}
