# Private messaging

For the unread-notification upgrade, apply migration 003 after 002 and follow [the current unread notification instructions](unread-messages.md). The current separate package is `build/infinityfree-unread/htdocs/`; the messaging package mentioned below is the earlier release.

Signed-in students select **Contact reporter** on a report and send a 1–2000 character message. The reporter finds it in **Inbox**. Both participants reply within the same conversation. No email service, attachments, external library, cron job, or hosting configuration change is needed. Inbox and message history are paginated; sending is limited to 30 messages/hour/account.

The server derives the recipient from the report owner and checks membership on every conversation read and reply. Anonymous users cannot send; third parties receive 404 for conversation IDs. Self-messaging and messaging ownerless legacy reports are rejected. The old contact endpoint is now owner-only, regardless of the retained `contact_visible` column. Existing contacts remain stored but are never automatically shared with other users. Do not put contacts into public report fields. Users can still voluntarily type personal information into a private message.

Messages are escaped text, stored on the server, and protected in transit by the site's existing HTTPS requirement. They are not end-to-end encrypted; database/hosting operators can access them. Report deletion leaves conversation history available to its participants. There are no notifications outside the inbox, read receipts, message deletion, or moderation features. Review retention and abuse handling before a wider launch.

## Apply the migration safely

1. Privately export a complete backup using phpMyAdmin. Do not put it in `/htdocs` or Git. Record the existing reports/accounts counts. If a staging copy is available, import the backup there and rehearse first.
2. In InfinityFree's hosting panel, open phpMyAdmin and explicitly select your existing application database. Confirm that migration `001_report_ownership.sql` has already supplied `users` and `items.owner_id`. Do not rerun 001 if already installed.
3. Import **only** `database/migrations/002_private_messages.sql` through phpMyAdmin. This adds `conversations` and `messages`, their indexes and foreign keys. It contains no DROP, ALTER, UPDATE, DELETE, database selection, or report/account inserts. It uses InnoDB and ordinary MariaDB/MySQL syntax. No credentials need to be edited or shared.
4. Confirm both new tables and their foreign keys exist, and compare reports/accounts counts and sample records with the backup. `CREATE TABLE IF NOT EXISTS` makes a repeated import safe for an already-correct schema; it does not repair a partially/incompatibly created table. MySQL DDL auto-commits: inspect any error before retrying. Do not drop existing tables to resolve an error.
5. Upload the runtime files only after successful import. The SQL is imported through phpMyAdmin; it must not be uploaded into `/htdocs`. Do not import `schema.sql`, `schema_hosting.sql`, or the generated fresh-install schema over an existing installation.
6. Use two test accounts and a new test report on the host to check sending/replying and a third account to verify denial. Confirm HTTPS, secure sessions, mobile layout, private contacts and direct `/config/` and `/includes/` access denial. This host-side verification has not been performed locally.

For local XAMPP, select the existing local database in phpMyAdmin and apply the same 002 migration. The implementation does not automatically migrate any application database. Fresh installations use the updated `database/schema.sql` (or the builder's data-free hosting schema for an empty hosting database).

## Upload files

Use the regenerated `build/infinityfree-messaging/htdocs/` folder as the local FileZilla source. Upload these paths to the matching paths under remote `/htdocs`:

- `messages.php` (new)
- `conversation.php` (new)
- `includes/messaging.php` (new)
- `includes/header.php`
- `item.php`
- `item-form.php`
- `contact.php`
- `assets/style.css`

Do not overwrite `config/local.php` or upload tests, docs, SQL, backups, logs, or the enclosing `build/.htaccess`. The existing credentials, HTTPS configuration and runtime protection files are unchanged.

## Local verification

With XAMPP Apache and MariaDB running, execute:

```powershell
C:/xampp/php/php.exe tests/run-isolated.php
```

The runner uses only explicit local XAMPP defaults and a random `ulf_message_test_*` database. It never loads the real private configuration. It applies 002 twice to seeded pre-messaging fixtures, checks exact report/account preservation, then runs branding, messaging, CRUD/authentication, schema, configuration and package suites through Apache. It removes its own scratch database, sessions and copied runtime files in `finally`. The runner needs local CREATE/DROP DATABASE permissions and Apache overrides enabled; it must never be uploaded or used on production.
