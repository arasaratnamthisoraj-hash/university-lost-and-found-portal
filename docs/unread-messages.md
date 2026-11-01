# Unread inbox notifications

The navigation badge counts unread received messages for the signed-in participant across their conversations. Own messages never count. Zero renders no badge. It is computed on page loads, without polling or background requests.

Opening an authorized conversation with GET marks all its currently received messages as read, including older paginated history. POSTs, rejected requests, and other users' conversations do not mark messages read. The badge updates in the same response. New arrivals remain unread until another opening. A separate `message_reads` table stores receipts without changing existing messages or exposing read receipts to the other participant.

## Existing database setup

1. Privately back up the database through phpMyAdmin. Select the existing application database and confirm migration 002 is already installed.
2. Import **only** `database/migrations/003_message_reads.sql`. It creates one table with indexes/foreign keys; it does not alter or update existing reports, accounts, conversations, or messages. MySQL DDL auto-commits: inspect any error before retrying. Reimporting is safe for an already-correct table but does not repair an incompatible table.
3. Verify `message_reads` exists and existing record counts/content remain unchanged. Existing received messages initially count as unread until their recipient opens the conversation. Do not import a fresh-install schema over existing data.
4. Upload the four runtime changes only after successful migration. Do not upload SQL or overwrite private configuration. Use the same migration for the local application database; implementation tests migrated only disposable databases.

Use `build/infinityfree-unread/htdocs/` as the local FileZilla source, and upload these files to matching paths under remote `/htdocs`:

- `includes/messaging.php`
- `includes/header.php`
- `conversation.php`
- `assets/style.css`

This incremental upload assumes the previous private-messaging release is installed. The separate folder also contains the complete 24-file runtime package for review, with no secrets, SQL, tests, documentation, logs, or backups. Retain the remote private `config/local.php`.

On the host, verify with two accounts: a new message shows a badge only for its recipient; opening clears it; a reply notifies the other account. A third account must not read the conversation or clear its badge. Check navigation on mobile and desktop. No hosting checks or browser visual checks were performed automatically.

## Local tests

Run `C:/xampp/php/php.exe tests/run-isolated.php` with XAMPP Apache and MariaDB running. The local-only runner uses a disposable database and copied runtime files, never the real private configuration. It checks repeat migration and exact preservation of seeded messages/conversations/reports, then executes all regression suites. Scratch databases and runtime copies are removed in `finally`.
