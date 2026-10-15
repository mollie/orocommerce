# PIORO-225 — Tasks

Plan: `plan.md`. Every task is one commit on its working branch, subject as listed, body line
`ISSUE: PIORO-225`. No delegation (spec): tasks run sequentially in the main session. Nothing is
pushed or merged; the developer merges `bugfix/PIORO-225` → `master` and `bugfix/PIORO-225-5.0` → `5.0`.

Status: `todo` / `in progress` / `done (<sha>)`.

## `bugfix/PIORO-225` (from `master`)

| ID | Goal | Files | Verification | blockedBy | Status |
|---|---|---|---|---|---|
| T1 | Create the working branch; commit the feature artifacts. Subject: `Add PIORO-225 research, spec and plan` | `docs/specs/PIORO-225/*` | `git status` clean apart from untracked files that are not ours | — | done (fcd0381) |
| T2 | Real ACL/CSRF attributes on the support endpoints; inject `DebugService`. Subject: `Enforce ACL and CSRF on Mollie support endpoints` | `Controller/SupportController.php`, `Resources/config/services.yml` | `php -l` 8.1/8.4; grep guard; attribute check (3 actions) | T1 | done (c392e35) |
| T3 | Real ACL/CSRF attributes on connection validation. Subject: `Enforce ACL and CSRF on Mollie connection validation` | `Controller/AjaxMollieController.php` | `php -l`; grep guard; attribute check | T1 | done (668a146) |
| T4 | Real order-view ACL on the admin payment link. Subject: `Enforce order view ACL on Mollie admin payment link` | `Controller/PaymentLinkController.php` | `php -l`; grep guard; attribute check | T1 | done (ede0d76) |
| T5 | ACL and route requirements on the active-methods endpoint. Subject: `Add ACL and route requirements to active payment methods endpoint` | `Controller/ActivePaymentMethodsController.php` | `php -l`; attribute check; requirements match `pfl_…`, `mollieProfileId`, `0` | T1 | done (f000775) |
| T6 | Flash bag through the request stack on admin-link failure. Subject: `Fix flash message on Mollie admin payment link failure` | `EventListener/Callback/AdminLinkPaymentRedirectListener.php` | `php -l`; no `$this->session` left in the class | T1 | done (dc4a511) |
| T7 | CI guard against ACL/CSRF attributes inside docblocks. Subject: `Add CI check for ACL attributes inside docblocks` | `.github/workflows/acl-attributes.yml` | YAML parses (`python3 -c 'import yaml…'` or careful review); guard self-test: hits on old `SupportController.php`, none on the tree | T2, T3, T4 | done (040bcbb) |
| T8 | Docs in sync. Subject: `Document controller ACL enforcement` | `DESIGN.md`, `CLAUDE.md`, `LEARNINGS.md` | grep `DESIGN.md` for `$this->get(DebugService` (none); sections listed in `plan.md` §3 updated | T2–T7 | done (2c5eca7) |
| T9 | Verify + review: plan §10 commands, manual script on Oro 6, `/code-review` → `/simplify` → `/security-review`; fixes as their own commits | — | real outputs reported; findings resolved | T8 | done (reviews: /code-review → R1, R2; /simplify and /security-review clean) |
| R1 | `/code-review`: debug download needs `oro_integration_update`; delete the temp ZIP after sending. Subject: `Restrict Mollie debug data download and delete its temp file` | `Controller/SupportController.php` | `php -l`; Sales Rep 403, Marketing Manager ZIP; no temp file left | T9 | done (d8287b0) |
| R2 | `/code-review`: object-level VIEW on the channel in active methods. Subject: `Check channel access in active payment methods endpoint` | `Controller/ActivePaymentMethodsController.php` | `php -l`; endpoint unchanged for allowed users, channel `0` and unknown ids | T9 | done (32e766c) |
| T10 | Release. Subject: `Release 6.2.4` | `composer.json` | `version` = `6.2.4` | T9 | handed to the developer (release done by the developer) |

## `bugfix/PIORO-225-5.0` (from `5.0`)

| ID | Goal | Files | Verification | blockedBy | Status |
|---|---|---|---|---|---|
| P1 | Create the working branch; bring the AI resources and spec folder over unchanged (`git checkout bugfix/PIORO-225 -- DESIGN.md CLAUDE.md LEARNINGS.md docs/specs/PIORO-225`). Subject: `Add PIORO-225 research, spec and plan` | those paths | diff vs `bugfix/PIORO-225` empty for those paths | T9 | done (14d1439) |
| P2 | Port T2 + R1 (`updateDebugStatus`: `@AclAncestor("oro_integration_update")` + `@CsrfProtection()`; `downloadDebugData`: `@AclAncestor("oro_integration_update")` + `deleteFileAfterSend(true)`; `$this->get()` stays). Subjects as T2, R1 | `Controller/SupportController.php` | `php -l` 8.1/8.2; annotations present in the docblock | P1 | done (c66c742, 9fdb787) |
| P3 | Port T5 + R2 (`@AclAncestor("oro_integration_view")`, `Annotation\AclAncestor` import, requirements, `@ParamConverter`). Subjects as T5, R2 | `Controller/ActivePaymentMethodsController.php` | `php -l` 8.1/8.2 | P1 | done (cfb87fa, c4d50db) |
| P4 | Verify + review on `5.0`: manual subset on Oro 5 (plan §10 steps 1–5, 7, 8), three reviews | — | real outputs reported | P2, P3 | done (manual subset on 5.1.0; /code-review answered without changes; /simplify and /security-review clean) |
| P5 | Release. Subject: `Release 5.3.4` | `composer.json`, `README.md` (`**5.3.4:**` notes) | `version` = `5.3.4` | P4 | handed to the developer (release done by the developer) |

T3, T4, T6 and T7 are not ported: `5.0` already has correct annotations for T3/T4, uses the
`$session` property in T6's listener, and port rules keep `.github/workflows/` out of `5.0`.

## Verification log (`master`, OroCommerce 6.0.2, local store in `logeecom_dev`)

Bundle installed from the working copy (`composer update`/`reinstall mollie/orocommerce`,
`oro:platform:update --force`, `cache:clear`). HTTP through a temporary PHP 8.3 built-in server, because
the container's Apache vhost does not point at `public/`. Test users: back-office Catalog Manager (no
Integration VIEW), Sales Rep (VIEW only), Marketing Manager (VIEW + EDIT), Administrator; storefront
users of the order's customer (BUYER and ADMINISTRATOR) and of another customer.

| Check | 6.2.1 (before) | Branch (after) |
|---|---|---|
| No-Integration user: support status GET / POST | 200 / 200 (state changed, no CSRF) | 403 / 403 |
| No-Integration user: active methods, debug download, validate connection | 200, 500, reaches action | 403 each |
| Sales Rep (VIEW): status, active methods | 200 | 200 |
| Sales Rep (VIEW): debug download | 500 (`UndefinedMethodError: get`) | 403 (needs EDIT) |
| Sales Rep (VIEW): POSTs with valid CSRF | 200 | 403 |
| EDIT user / admin: POST without or with forged `X-CSRF-Header` | 200 | 403 |
| EDIT user / admin: toggle debug true/false, bad payload | — | 200 / 200 / 400 |
| Admin: debug download | 500 | ZIP (`phpinfo.html`, `order-references.json`, `logs/`), temp file deleted |
| Admin: "Verify" with the rendered channel form / invalid token | — | success JSON / failure JSON |
| `methods/active/abc/x-y` | 200 | 404 |
| Payment link, guest | action body runs (crashes in `MollieDtoMapper:431`) | 302 to `/customer/user/login`, no transaction |
| Payment link, other customer / other buyer of the same customer | — | 403 / 403, no transaction |
| Payment link, customer ADMINISTRATOR of the order's customer | — | ACL passes, action runs (Mollie rejects the demo order data, fallback to order view) |

Not exercised: the admin-link failure flash (T6; no unpaid Mollie admin-link order available), the
order's own customer user (password unknown; DEEP access covered instead), a second organization for R2.

## Verification log (`5.0` port, OroCommerce 5.1.0, local store in `logeecom_dev`)

Bundle installed from the working copy (`composer update mollie/orocommerce`, `oro:platform:update --force`,
`cache:clear`); HTTP through a temporary PHP 8.2 built-in server. Same back-office roles as on 6.0.2.
Port note: `getDebugStatus` also moved from `@Acl` (a redefinition of Oro's identical `oro_integration_view`)
to `@AclAncestor("oro_integration_view")`, as on `master`.

| Check | 5.3.2 (before) | Branch (after) |
|---|---|---|
| No-Integration user: active methods | 200 (no ACL) | 403 |
| No-Integration user: support endpoints, validate connection | 403 (annotations already worked) | 403 |
| Sales Rep (VIEW): debug download | 200 ZIP | 403 (needs EDIT) |
| Sales Rep (VIEW): status POST without / with CSRF | 200 / 200 | 403 / 403 |
| EDIT user / admin: status POST without or with forged CSRF | 200 | 403 |
| EDIT user / admin: toggle true/false with CSRF, debug download | — | 200 / 200, ZIP (temp file deleted) |
| Admin: "Verify" with the rendered channel form | — | success JSON |
| `methods/active/abc/x-y`; channel `0` / unknown id | 200 | 404; `success:false` JSON |
| Payment link, guest (unchanged on `5.0`) | — | 302 to `/customer/user/login` |
