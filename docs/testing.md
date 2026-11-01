# Unread notifications verification — 31 October 2026

All 45 non-private source and generated-package PHP syntax checks pass. The separate 24-file unread deployment package matches current runtime source and contains no private local configuration.

The isolated XAMPP Apache/MariaDB run passes 56 messaging checks (including 21 new unread checks), 116 CRUD/authentication checks, 21 branding checks, 5 schema checks, 16 configuration checks and 9 packaging checks. Two migration checks verify additive migration/reimport preservation, including existing message contents. Real application databases and private configuration were not used. The temporary database/runtime copy was removed. Hosted behavior and visual mobile checks remain manual. Run `C:/xampp/php/php.exe tests/run-isolated.php`. These results supersede the earlier counts below.

# Private messaging verification — 31 October 2026

The isolated XAMPP Apache/MariaDB runner passes 35 messaging checks, 116 existing CRUD/authentication integration checks, 21 branding checks, 5 schema checks, 16 configuration checks, and 9 packaging checks. An additional check verifies that importing and reimporting the additive messaging migration preserves seeded reports/accounts exactly. All 45 non-private source/package PHP syntax checks pass. Real application databases and private configuration are not used. Scratch databases and copied files are removed.

Run `C:/xampp/php/php.exe tests/run-isolated.php`. Messaging tests include anonymous and third-party forged requests, self-send denial, forged recipients, CSRF, input limits, escaped SQL/XSS payloads, contact privacy, replies, repeat conversations, rate limiting and history after report deletion. Production migration/hosting and visual responsive checks remain manual. These results supersede older contact-sharing behavior and package counts below.

# Development verification — updated 30 October 2026

## Independent student-project branding update

The official crest was removed from all rendered shared headers and deployment builds. An original inline SVG project mark is used instead. The academic project title is retained, while the header and titles explicitly identify an independent student project. Homepage/login/registration main content contains the requested exact disclaimer; authentication copy describes this portal's own accounts. No credential, hosting, schema, authorization or CRUD logic was changed.

- **116 existing integration checks passed** in an isolated local database/copy, excluding all private local configuration.
- **5 schema**, **16 configuration**, **9 deployment** and **21 HTTP/DOM branding checks passed**. The branding checks cover homepage, login and registration, including titles, the visible header label, SVG mark, exact disclaimer, no crest image and no institutional links in shared branding.
- **21 non-private source PHP files** and **16 generated PHP files** passed syntax checks. Private configuration and local configuration templates were not executed or printed during these checks.
- The clean build has **21 runtime files**, no official logo and no private configuration. A former generated logo is removed by the builder only at its known obsolete artifact path; the unused source asset remains local and ignored by Git.
- All 12 recorded credential/security/CRUD/schema/hosting file checksums matched after the changes, including `config/local.php`. Existing local database records and ID sequences also matched the pre-test checksum. No real report was used for mutation tests; the isolated database and test copy were removed.

Manual verification remains: desktop/mobile visual appearance, the actual deployed files after manual upload, Search Console sample URLs and any misleading/injected content or redirects. Google’s security issue has not been verified resolved. No upload, Git push or Search Console review request was performed.

## InfinityFree preparation — 30 October 2026

Configuration and packaging were prepared locally only. No remote upload, remote MySQL connection, Git commit, push, or deployment was performed. Production uses a protected private PHP config and a non-secret forced-production marker; local XAMPP defaults remain supported. See `docs/infinityfree-deployment.md` for FileZilla instructions and hosting-specific verification gates.

- **116 existing integration checks passed** through XAMPP Apache against an isolated fresh test database and local-only application copy. This includes authentication, server ownership, private contacts, CSRF, SQL injection-shaped inputs, output escaping, CRUD, optional Description and search/filter/sort.
- **5 schema checks passed**; neither existing local schema nor original records were altered.
- **16 configuration checks passed**, including private-file settings without environment variables, local defaults, override precedence, forced production, missing/empty/administrative credential rejection, DSN input validation and forwarded-header rejection.
- **8 deployment checks passed**, covering the exact 22-file runtime allowlist, exclusions, production marker, credential-free fail-closed behaviour, config protections, unchanged logo and refusal to rebuild over an unexpected private config.
- **10 local HTTP deployment checks passed**, covering fixed-host HTTPS redirection, forwarded-header spoofing, HTTP POST rejection before session creation, production/error settings, Secure/HttpOnly/SameSite cookies with simulated trusted TLS metadata, generic failure without credentials and denial of direct config/include access (including a disposable canary config file).
- **21 source PHP files and all 16 generated runtime PHP files passed syntax checks**.
- Original report/user/category/rate-limit data and ID sequences matched the pre-test checksum. The isolated test database and copy were removed. No real reports were used for mutation tests.

The cookie test is an application-level TLS-metadata simulation, not an actual InfinityFree certificate test. Remote PHP/extensions, native HTTPS indication, valid SSL, private config denial, startup-level error reporting, FTPS and the existing remote database state remain manual deployment gates. No diagnostic endpoint or test script is included in the upload folder.

## Current ownership/privacy verification — 28 October 2026

The approved additive ownership migration was applied once to the existing local MariaDB database after creating the ignored, access-protected backup `database/backups/before-ownership-2026-10-28.sql`. All four existing reports remain ownerless, locked and contact-private. SHA-256 checksums of all original report fields and category records matched before migration and after testing. No existing report was deleted or reset.

- All **17 PHP files** passed syntax checks.
- `php tests/integration.php`: **116 checks passed** through the running XAMPP Apache and MariaDB services. This supersedes the historical 58-check result below.
- Coverage includes registration/password hashing, weak/duplicate registration rejection, sign-in, CSRF rotation, anonymous create denial, logout, owner CRUD, non-owner GET/POST denial with valid CSRF, forged owner fields, locked legacy fixtures, anonymous public search/filter/sort/details, private contact omission from HTML, owner disclosure, consent, revocation, POST-only contact, no-store responses and persistent login/contact throttling.
- Existing validation, prepared-query behavior, output escaping, optional descriptions and 3000/3001-character boundaries, safe deletion, foreign keys and protected paths continue to pass. Migration SQL and the private backup also returned 403 through Apache.
- `php tests/schema.php`: **5 checks passed**. Fresh schema import/reimport, category seeding, private/locked defaults and empty descriptions work; fresh and migrated column/foreign-key definitions match. The temporary test database was removed; the application database was retained.
- `php tests/configuration.php`: **3 checks passed**, covering rejection of missing/default administrative production credentials and acceptance of explicit non-default configuration. No connection is attempted by that test.
- Integration test accounts/reports were removed; there are no pre-created production accounts. IP throttling counters are intentionally retained.

These are HTTP/database and configuration tests, not browser visual tests. The campus logo and existing hero/design remain in place; the new account controls reuse existing styles. Manual browser checks remain: responsive navigation/forms, password manager/autofill behavior, keyboard focus, checkbox consent, and a two-account walkthrough. Actual hosting HTTPS/proxy configuration, secure-cookie behavior, session expiry timing, host-level log/backup protection, trusted legacy-owner assignment, and branding redistribution permission require manual review. No Git push or deployment was performed.

## Scope

Implemented the three requested stages: database/project structure; persistent CRUD; search/filter/sort, validation and responsive CSS. The official rubric is the scope reference. The portal has no accounts, ownership rules, image uploads or additional workflow features.

## Verified environment and results

- During the previously verified functional runs, existing XAMPP Apache and MariaDB were running; their configuration was not changed.
- PHP CLI: 8.4.27; MariaDB: 10.11.16; PDO MySQL and mbstring available.
- Imported schema successfully: categories and items, six categories, InnoDB, utf8mb4, primary keys, unique category name and category foreign key.
- All 10 PHP files passed `php -l`; this read-only check was repeated successfully during upload preparation on 27 October 2026.
- The earlier functional run after making Description optional passed all 58 checks in `php tests/integration.php` through Apache at `http://localhost/university-lost-and-found-portal` and against the actual database. This superseded the 34-check baseline and is now superseded by the ownership run above.
- Added checks verified empty-description Create and Update for both lost and found reports, persistence, details fallback text, acceptance at 3000 characters, and rejection at 3001 characters without overwriting the saved description.
- Four isolated JavaScript checks also passed in that change: optional blank and whitespace values accepted; required blank rejected; required nonempty accepted. These were not browser interaction tests.
- Create, detail read, populated edit, persisted update and persisted deletion verified independently by SQL and HTTP.
- Search, literal percent/underscore search, combined type/category filtering, three sort orders and empty state verified.
- Missing/malformed IDs, invalid fields, excessive length, impossible/future/malformed dates, invalid update and CSRF rejection verified.
- Stored HTML is escaped; injection-shaped query input is treated as data; sort expressions use an allowlist.
- GET deletion shows confirmation without deleting. Only a valid CSRF-protected POST removed the record.
- Direct HTTP access to config, includes, SQL and tests returned 403. Invalid category references were rejected by MariaDB.
- The integration suite removed its own temporary test records. These results do not assert that the user's database contains no other reports.

An initial malformed-date test exposed that trimming input removed a trailing null byte. Date validation was changed to examine the original string and require exact YYYY-MM-DD syntax before parsing. The complete suite passed after the fix.

These are request/database integration tests, not browser automation. They bypass JavaScript and thereby verify that server validation works independently. No Apache/MariaDB service restart was performed: persistence was checked using separate database queries and HTTP requests.

## Upload-preparation verification — 27 October 2026

Apache and MariaDB were not running during this documentation/ignore-rule update. They were not started, and the integration suite was not rerun: it writes temporary database records. The 58-check result above records the previous completed run, not a new functional run today. No database changes were made for upload preparation. Git was unavailable at the checked locations and on PATH; ignore rules require final confirmation with `git check-ignore` and review of staged files once Git is available. No repository was initialized, commit created or push performed.

## Manual checks still required

The browser connector exposed no browser sessions. Native Chrome inspection could not capture its minimized window, so no visual/browser pass is claimed.

1. At desktop, tablet and mobile widths (for example 1440px, 768px and 375px), check navigation, form controls, wrapping, cards and absence of horizontal overflow. Current responsive CSS includes 1000px, 760px and 520px breakpoints.
2. Use keyboard-only navigation to check visible focus, label associations and form feedback.
3. Submit empty/whitespace required fields with JavaScript enabled and disabled. Check native browser validation and server messages. Description is optional: verify that it can be left blank or cleared, and that empty descriptions show "No additional description provided." on the details page.
4. Confirm the JavaScript deletion dialog; cancel should preserve the report. Repeat with JavaScript disabled to check the server confirmation page still works.
5. Inspect the browser console/network for asset errors and CSP violations.
6. Complete a visual create → search → view → edit → cancel deletion → confirm deletion walkthrough. Capture real UI screenshots for the report.

## Rubric coverage and remaining submission work

CRUD covers the main 16 marks; database design addresses four; validation/error handling, responsive UI, organized shared code and setup/ERD documentation address the other implementation criteria. These are coverage claims, not a predicted grade.

Git was unavailable on PATH during environment inspection; version control and GitHub submission remain to be set up. The final report (at most 10 pages), UI screenshots, camera-on video (at most five minutes), index-number filenames and submission through the official sheet remain. Deadline: on or before 31 October 2026. Academic integrity/AI disclosure rules require confirmation with the instructor.

The previous shared report access has been replaced by account ownership and consent-based contact disclosure. Public hosting still requires the HTTPS, restricted database account and server configuration checks documented in README.md.
