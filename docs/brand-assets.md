# Independent project identity

The website uses an original tag-and-magnifier SVG mark defined in `includes/icons.php`. It is a generic Lost & Found project icon, with no university crest, seal, motto or institutional symbol. It is used by the shared header across all pages. The existing burgundy/cream/gold styling remains a project design choice, not an official identity specification.

The academic title remains “University of Ruhuna Lost & Found Portal.” The header prominently states “Independent Student Project.” The homepage, login page and registration page display: “Independent student project — not an official university service or endorsed by the University of Ruhuna.” Page titles and footer also identify the independent project. University links have been removed from the shared branding. Authentication copy refers to accounts created for this portal, not university accounts or enrollment verification.

## Retired assets and deployment

The local, Git-ignored `assets/images/ruhuna-logo.png.png` is retained only as an unused historical file. It is not referenced by runtime pages and is excluded from the deployment allowlist. The builder removes that specific obsolete file from a previous generated build, without deleting the source file or other unexpected files. Remove any obsolete remote copy manually after uploading the seven changed runtime files; nothing has been uploaded or removed remotely here.

The assignment PDF remains excluded from Git and deployment because redistribution permission has not been established. No replacement campus image or university emblem has been generated.

## Verification limits

Local HTTP/DOM tests check disclosures, titles, independent project identification, the original SVG mark and absence of crest imagery on homepage/login/registration. Database-backed regression tests run in an isolated local copy that excludes private configuration. These tests do not establish the cause or resolution of Google’s deployed-site security finding. The current Search Console sample URLs, deployed files, host security and any injected/third-party content still need review.
