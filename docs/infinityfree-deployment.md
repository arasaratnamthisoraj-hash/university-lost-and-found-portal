# InfinityFree deployment preparation — 30 October 2026

This guide prepares a manual deployment. No remote files or databases have been accessed or changed by the build. Never upload the whole project or the pre-existing ZIP archive.

## Hosting decisions and verified documentation

- InfinityFree's administrator says free hosting does not supply user-configured environment variables to PHP. The application therefore supports a private PHP array, without `putenv()`, dotenv, Composer or shell commands on the host. [Provider guidance, 8 October 2026](https://forum.infinityfree.com/t/sa-topic-title-ilagay-mo-does-infinityfree-support-php-environment-variables-and-private-c/121148).
- That guidance distinguishes secondary domains with their own domain directory (possible access one level above their htdocs) from the main `/htdocs` layout. **Do not assume outside-htdocs access for this account.** This deployment uses `/htdocs/config/local.php`, protected by the config directory's `Require all denied`, an independent root rewrite denial and a direct-execution guard. No hosting directory reassignment is required.
- The provider advertises PHP 8.4. The project uses PHP 8.1+ features; confirm the account has PDO MySQL and mbstring. No CLI tools or test scripts are needed on the host. [Provider specifications](https://www.infinityfree.com/).
- HTTPS detection uses the server's native `HTTPS=on` or `HTTPS=1`, consistent with the provider administrator's recommendation. Arbitrary X-Forwarded-Proto and port numbers are not accepted as proof of TLS. [Provider HTTPS guidance](https://forum.infinityfree.com/t/fix-https-support-infinityfree-net-https-how-to-force-all-traffic-to-https/41758).
- The supplied account's runtime, certificate and access restrictions cannot be verified without the user's manual upload. Local TLS-metadata simulations are not a live InfinityFree TLS test.

## Files to upload

Rebuild from the project folder when runtime source files change:

```powershell
& C:/xampp/php/php.exe deployment/build-infinityfree.php
```

Local upload directory:

`C:\xampp\htdocs\university-lost-and-found-portal\build\infinityfree\htdocs`

It contains exactly 24 runtime files: the ten public PHP entry points, CSS/JavaScript, required includes, configuration loaders, protection files, and the non-secret production marker. It contains **no** `local.php`, tests, SQL, backups, docs, Git files, logs or archives. The enclosing `build/.htaccess` prevents accidental access to the local build over XAMPP; do not upload that enclosing access-denial file as the website's root .htaccess.

The shared header uses an original inline Lost & Found SVG mark from `includes/icons.php`. No university crest is used or packaged. The assignment PDF is not included.

## Before uploading

1. Verify in InfinityFree's Domains panel that `university-lost-and-found.infinityfreeapp.com` maps to `/htdocs`. If the panel shows a different directory, use that directory instead; do not move/delete existing sites.
2. Confirm a valid SSL certificate is active for the exact hostname. Do not bypass certificate warnings. The package redirects HTTP GET/HEAD requests to the fixed HTTPS hostname and rejects HTTP form submissions with 426 before starting a session.
3. If `/htdocs` already contains a site, make a private backup and review filename conflicts before replacing files. Keep any existing private config and data; do not use a destructive synchronization or delete-all action.
4. Prepare the private configuration described below. The credential-free package intentionally fails closed until it is supplied.

## Exact FileZilla upload steps

1. Open **File → Site Manager → New Site**. Name it `University Lost and Found`.
2. Protocol: **FTP — File Transfer Protocol**. Use the FTP hostname in **InfinityFree Client Area → hosting account → FTP Details** (normally `ftpupload.net`); **do not use `sql303.infinityfree.com` as the FTP host**. Port: **21**.
3. Encryption: **Require explicit FTP over TLS**. Logon type: **Ask for password**. User: the FTP username shown in FTP Details (confirm it matches this account; the supplied account identifier is `if0_43303703`). Enter the FTP/hosting password privately in FileZilla when connecting. This guide does not request the password. SFTP/SSH is not assumed. If explicit TLS is unavailable, stop and consult provider support or use its HTTPS file manager; do not silently switch to plaintext FTP to send credentials.
4. Set the left/local directory to the exact `build\infinityfree\htdocs` path above. Set the right/remote directory to **`/htdocs`**.
5. Ensure local hidden files are visible. Upload the **contents** of the local `htdocs` folder, including its root `.htaccess`, `config/.htaccess` and `includes/.htaccess`. Do not upload a containing `build`, `infinityfree` or second `htdocs` directory.
6. Upload the directory protection files first. Check FileZilla's **Failed transfers** tab; retry only failed entries and check file sizes. Review overwrite prompts individually for an existing installation.
7. Supply only the private `config/local.php` separately, as below. Do not upload examples, SQL files or this guide.

[InfinityFree's FileZilla/FTP instructions](https://forum.infinityfree.com/t/how-to-upload-files-with-ftp/49306).

## Private production configuration

Use `config/local.example.php` as the sanitized template. Do not edit the tracked `config/database.php` or put credentials in .htaccess. Do not put the real private file inside the clean build folder.

Create `local.php` privately using the hosting panel's HTTPS file editor, or prepare it outside the project/Git/web-root directories and upload it over verified FTPS to **`/htdocs/config/local.php`**. Retain the template's direct-access guard. Supply these non-secret values:

| Setting | Value |
|---|---|
| environment | `production` |
| canonical_origin | `https://university-lost-and-found.infinityfreeapp.com` |
| database.host | `sql303.infinityfree.com` |
| database.port | `3306` |
| database.name | `if0_43303703_university_lost_and_found` |
| database.user | `if0_43303703` |
| database.password | Enter privately using the correct PHP string syntax |

Keep the PHP return-array structure and escape backslashes/quotes correctly if they occur in the password. Never include a closing `?>` tag or any output. Use restrictive file permissions supported by the host while keeping the file readable by PHP; do not make it globally writable.

The root project's `.gitignore` excludes `/config/local.php`, and the allowlist builder never copies it. Production settings are forced by `config/deployment.php` in the package, so omitting private credentials or setting `APP_ENV=local` cannot make the host fall back to XAMPP credentials. Explicit environment settings still take precedence over private database values on hosts that support them. No private file is needed to keep the existing XAMPP defaults working.

## Database preparation — manual, preserve existing tables

No PHP entry point installs, drops, truncates or migrates tables. No remote connection has been attempted.

1. Open InfinityFree's control panel → MySQL Databases → phpMyAdmin for `if0_43303703_university_lost_and_found`. Confirm the hostname and account details there.
2. **Inspect existing tables first and take a private backup.** The application expects `categories`, `items`, `users`, and `rate_limits`, including `items.owner_id` and `items.contact_visible`.
3. **Only if the database is empty**, select it and import `deployment/infinityfree/schema.sql` from your computer through phpMyAdmin. This sanitized schema omits the local `CREATE DATABASE` and `USE` statements. It has no report/student data and no DROP/TRUNCATE statements. Keep it outside `/htdocs`.
4. If tables already exist, do not blindly import a schema or backup. Check whether the approved ownership migration has already been applied. Apply only an explicitly reviewed, necessary additive change after backup. Do not rerun `001_report_ownership.sql` on an already upgraded database.
5. If moving existing local reports/accounts is desired, plan a separate private data transfer, preserving IDs, password hashes, category relationships, ownership and sharing flags. The website file package intentionally contains no report data. Never use phpMyAdmin's destructive replacement options or publish the export. Existing local data remains untouched by this preparation.

InfinityFree blocks external MySQL connections: XAMPP cannot directly test `sql303.infinityfree.com`. Connectivity must be checked from the hosting PHP application or its phpMyAdmin. [Provider MySQL instructions](https://forum.infinityfree.com/t/how-to-connect-with-mysql/49340).

## Verify after the manual upload

1. Open `https://university-lost-and-found.infinityfreeapp.com/` in a normal browser. A populated or empty report listing confirms PDO can query the required tables; no public phpinfo/database-debug endpoint is necessary.
2. Confirm HTTP GET redirects to that exact HTTPS hostname without a loop. Verify the certificate, and inspect Set-Cookie for **Secure**, **HttpOnly**, and **SameSite=Lax**.
3. Check `/config/local.php`, `/config/settings.php`, `/config/deployment.php` and `/includes/bootstrap.php` directly, including `?download=1`: they must return **403/404**, never file contents. If any configuration content is visible, stop, take the site out of public use, and fix protections before proceeding. Do not remove denial rules to fix HTTP 500 errors.
4. Confirm reporter names/contacts are absent from anonymous detail HTML; contact requests require login and owner opt-in. Check that the eight public entry points and CSS/JS transferred successfully.
5. With disposable accounts/reports only, verify registration, sign-in, Create with blank Description, Read/search/filter/sort, owner Update/Delete, other-user rejection, contact consent/revocation and logout. Do not run the CLI integration suite against live hosting.
6. Verify host error display is off, private error logging is available, and session storage is writable/private. PHP startup-level error settings are managed by the host; application ini_set calls cannot prove those settings. Do not add unsupported `php_flag` or `SetEnv` directives.

Generic setup failures deliberately do not expose database errors or credentials. Check private control-panel logs and compare the non-secret settings/table structure; never add an endpoint that prints a password or full exception trace. Hosting browser-verification/anti-bot pages can affect automated HTTP clients, so use an ordinary browser for the final account-specific checks.

## Remaining deployment gates

The server-side private password, actual account PHP/extensions, valid SSL, native HTTPS metadata, private file denial, FTP TLS and existing remote database/table state must all be confirmed manually. The local package and test results do not establish those remote facts. Nothing is uploaded, deployed, committed or pushed by the build.

## Branding-only update for the already deployed site

For this update, upload only these seven files from the matching paths under `build/infinityfree/htdocs` to the corresponding paths under remote `/htdocs`:

- `index.php`
- `login.php`
- `register.php`
- `includes/header.php`
- `includes/footer.php`
- `includes/icons.php`
- `assets/style.css`

Do not overwrite `config/local.php`, other configuration, or .htaccess files. No SQL import or database change is needed. After verifying the new pages, manually remove the obsolete remote file `/htdocs/assets/images/ruhuna-logo.png.png` if present; this preparation has not removed any remote file. Refresh cached pages and verify homepage, login, registration and details in a normal browser.

Branding changes alone do not prove that Google’s Deceptive pages finding is resolved. Inspect Search Console’s exact sample URLs and the deployed site for misleading forms, unexpected scripts, injected content or redirects. Once the deployed site has actually been verified and all reported issues addressed, request a security review in Search Console. See [Google’s social-engineering guidance](https://developers.google.com/search/docs/monitor-debug/security/social-engineering) and [Security Issues report instructions](https://support.google.com/webmasters/answer/9044101?hl=en).

## Private messaging upgrade

For existing installations, apply only `database/migrations/002_private_messages.sql` before uploading the messaging runtime changes. See [private messaging instructions](private-messaging.md). The current clean package is `build/infinityfree-messaging/htdocs`; older generated packages are stale. Do not replace private configuration or import a fresh-install schema into an existing database.

## Unread notifications upgrade

The current separate package is `build/infinityfree-unread/htdocs/`. Existing messaging installations must apply only `database/migrations/003_message_reads.sql` before uploading the four runtime changes. See [unread setup and upload paths](unread-messages.md). Older generated packages are stale. Do not overwrite private configuration or import a fresh-install schema into an existing database.
