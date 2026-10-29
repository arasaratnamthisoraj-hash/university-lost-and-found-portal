# Development verification — updated 28 October 2026

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
