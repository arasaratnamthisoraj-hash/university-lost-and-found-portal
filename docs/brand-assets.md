# Frontend assets

The header uses the user-supplied `assets/images/ruhuna-logo.png.png` at its original aspect ratio. The supplied file is unchanged. Its presence identifies the university context, not university endorsement. The utility bar, hero and footer identify the portal as an independent student project.

No campus photograph was supplied in the project, so the homepage uses the explicitly requested typography-based fallback. No generated or downloaded campus image is used. Before adding a photograph, supply a genuine University of Ruhuna campus image and confirm permission to use it here (including any attribution requirements). Merely being published on a university website is not proof of reuse permission.

The burgundy, gold, cream and white palette is a student-project design choice, not a claim about official university colour specifications.

## Public repository exclusions

Redistribution permission has not been established for the supplied university logo (`assets/images/ruhuna-logo.png.png`) or the course assignment rubric (`docs/Mini Project Evaluation Rubric - COM.pdf`). Both paths are explicitly excluded in `.gitignore`; neither local file is deleted or changed, and the local application's branding is retained. Keep both excluded until permission to redistribute publicly is confirmed. A fresh clone requires an independently authorized copy of the logo at the same path; obtain the rubric through the course's authorized channel.

Ignore rules do not remove files already tracked by Git. Before the first commit, verify the staged file list and confirm that these assets, local credentials, and private exports are absent, while `database/schema.sql` is included. There was no `.git` directory during preparation; no index or history was modified.

## Verification of the typography-based redesign

- At the time of the typography redesign, all 10 PHP files passed syntax checks and the then-existing 34 integration checks passed. The later optional-description change passed 58 checks; see `docs/testing.md` for the current results and their verification limits.
- Homepage and supplied PNG returned HTTP 200; the logo response is `image/png`.
- HTTP checks confirmed the requested heading and supporting text, and removal of the illustrated card markup.
- Before/after checksums matched for the existing item/category records, schema, database configuration, shared security/bootstrap code, and create/edit, detail and deletion pages. Integration tests removed their own temporary records.
- Responsive CSS retains desktop/tablet/mobile breakpoints. Visual review at those sizes, keyboard focus and logo rendering remain manual checks; no browser visual pass is claimed.
