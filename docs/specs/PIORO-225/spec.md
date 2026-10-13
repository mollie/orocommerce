# PIORO-225 — Spec: enforce ACL/CSRF on the bundle's controller actions

Research: `research.md` (same folder). Origin: HackerOne #3990813 / SSD-16868.

## Problem

On `master` (Oro 6) the ACL and CSRF declarations of five controller actions sit inside `/** */`
docblocks and are never read, so the actions are reachable by any back-office user (admin routes) or
by anyone (the storefront payment link). `ActivePaymentMethodsController` declares no ACL at all on
both branches. `SupportController::downloadDebugData` calls `AbstractController::get()`, which does not
exist in Symfony 6.4. `AdminLinkPaymentRedirectListener` uses an undefined `$this->session` on `master`.

## Goal

Every bundle controller action enforces the ACL (and, for state changes, the CSRF protection) that
`5.0` already declares, using real Oro 6 attributes; the recurrence is caught in CI.

## Branches and merge

No commit goes directly onto `master` or `5.0`. The work happens on local working branches:

| Working branch | Created from | Contains |
|---|---|---|
| `bugfix/PIORO-225` | `master` | the `master` tasks and `Release 6.2.4` |
| `bugfix/PIORO-225-5.0` | `5.0` | the ported commits and `Release 5.3.4` |

The developer pushes and merges each working branch into its target branch (`master` / `5.0`).
Nothing is pushed or merged by the agent.

## Scope

### `master` (working branch `bugfix/PIORO-225`)

1. `PaymentLinkController::goToMolliePaymentPage`: real `#[AclAncestor('oro_order_frontend_view')]`
   (object-level VIEW on the order through the param converter, as on `5.0`).
2. `SupportController`:
   - `getDebugStatus`: `#[AclAncestor('oro_integration_view')]` (no redefinition of Oro's
     `oro_integration_view` through `#[Acl]`).
   - `downloadDebugData`: `#[AclAncestor('oro_integration_update')]` (changed after `/code-review`: the
     ZIP holds `phpinfo()` and all logs); the temporary ZIP is deleted after it is sent.
   - `updateDebugStatus`: `#[AclAncestor('oro_integration_update')]` + `#[CsrfProtection()]`.
   - `DebugService` injected through the constructor instead of `$this->get(DebugService::class)`.
   - ZIP content unchanged.
3. `AjaxMollieController::validateConnectionAction`: `#[AclAncestor('oro_integration_update')]` +
   `#[CsrfProtection()]`.
4. `ActivePaymentMethodsController::getActivePaymentMethods`: `#[AclAncestor('oro_integration_view')]`
   plus a `@ParamConverter` for the channel, so VIEW is also checked on that channel (added after
   `/code-review`: the class-level check alone let a user run Mollie calls with another organization's channel);
   route `requirements` for `channelId` (`\d+`) and `profileId` (`[A-Za-z0-9_]+`; it must also match
   the `mollieProfileId` placeholder that `website-profile.html.twig` passes to `path()`).
5. `AdminLinkPaymentRedirectListener::redirectToFailureUrl`: use the request-stack session as the
   success path does.
6. CI guard: a GitHub Actions workflow that fails when `#[Acl`, `#[AclAncestor` or `#[CsrfProtection`
   appears on a docblock line (`^\s*\*.*#\[(Acl|AclAncestor|CsrfProtection)\b`) in bundle PHP files.
7. Docs: `DESIGN.md` (back-office endpoints, admin payment link, existing-deviation note),
   `CLAUDE.md` (Commands / CI, § Supported branches `.github/workflows` row), `LEARNINGS.md` entry.
8. `Release 6.2.4`.

### `5.0` (port, `bugfix/PIORO-225-5.0`)

- Items 2 (the `updateDebugStatus` ACL/CSRF change, `downloadDebugData` → `oro_integration_update` and
  `deleteFileAfterSend(true)`; `$this->get()` works on Symfony 5.4 and stays) and 4 (ACL, requirements,
  `@ParamConverter`), in Doctrine annotation form (`@AclAncestor`, `@CsrfProtection`).
- Items 1, 3, 5 need no change on `5.0` (already correct). Item 6 is not ported (`PRINCIPLES.md` §7
  forbids touching `.github/workflows/` in a port; `5.0` uses annotations, which the guard does not
  target).
- AI resources (`DESIGN.md`, `CLAUDE.md`, `LEARNINGS.md`, this spec folder) brought over unchanged.
- `Release 5.3.4` with README release notes.

## Out of scope

- Payment-link token for guests, moving the payment-link state change off GET.
- Reducing the debug ZIP content.
- Any `IntegrationCore/` change.
- `.gitattributes` / CRLF for `.claude/hooks/plan-gate.sh` (the developer fixes it locally).

## Acceptance criteria

1. `grep -rnE '^\s*\*.*#\[(Acl|AclAncestor|CsrfProtection)\b' --include=*.php . ` (excluding
   `IntegrationCore/`, `Tests/`) returns nothing on `master`; the CI guard fails on a deliberately
   broken copy and passes on the fix.
2. Reflection on each changed action shows the expected attributes (`ReflectionMethod::getAttributes()`).
3. In an Oro 6 application:
   - A back-office user whose role lacks Integration VIEW gets 403 on `GET /admin/mollie/support/status`,
     `/admin/mollie/support/download_debug_data` and `/admin/mollie/methods/active/{id}/{profile}`;
     without Integration EDIT, 403 on `POST /admin/mollie/support/status` and `POST /admin/mollie/validate-connection/{id}/`.
   - An administrator gets the debug ZIP (no 500) and can toggle debug mode from the browser with the CSRF header.
   - The POST endpoints without `X-CSRF-Header` return 403.
   - The "Verify" button on the channel form and the website-profile method refresh still work.
   - Payment link: guest → access denied; another customer's buyer → denied; the order's own customer
     user → redirected to Mollie checkout.
   - Admin-link payment failure → redirect to the failure URL with the error flash, no PHP error.
4. On `5.0` in an Oro 5 application: the same checks for the active-methods and debug-toggle changes;
   everything else unchanged.
5. `php -l` green on changed files; PHP 8.1 floor kept; ASVS checklist answered; docs in sync.

## Decisions

| Decision | Why |
|---|---|
| Payment link: ACL `oro_order_frontend_view` as on `5.0` | Smallest change, gives ownership via the param converter; guest access was never intended on `5.0` either |
| Debug endpoints kept and fixed, ZIP unchanged | Mollie support relies on them; access is now limited to Integration VIEW/EDIT |
| Debug download requires `oro_integration_update` (review change, confirmed by the developer) | `phpinfo()` and the application logs are more than Integration VIEW should expose (ASVS 13.2) |
| CI guard stays a narrow grep (review question, confirmed by the developer) | Reliable; missing ACLs are caught by the rule file and review |
| Debug toggle requires `oro_integration_update` + CSRF | It changes global state |
| Validate connection: ACL + CSRF as on `5.0` | Stricter than Oro's UPS/FedEx pattern, matches our own `5.0` behaviour |
| `AclAncestor` instead of `Acl` for `oro_integration_view` | `Acl` with an existing id silently overwrites Oro's definition |
| Guard as a GitHub Actions workflow on `master` | Catches changes from any author; port rules keep workflows out of `5.0` |
| `$this->session` fix included | Same release, same files area, crash on the admin-link failure path |
| No subagent delegation | The change is small and sequential |
| Ticket `PIORO-225`; working branches `bugfix/PIORO-225` (from `master`), `bugfix/PIORO-225-5.0` (from `5.0`); developer merges | Developer's choice; `-5.0` suffix as in `bugfix/PISYL-323-5.0` (the documented default is `_5.0`) |

## Open risks

- Guests and shoppers without a session can no longer open admin payment links on `master` (same as
  `5.0` today). What the storefront shows an anonymous visitor on denial (login redirect or 403) is not
  verified.
- Merchants whose staff used the debug or connection endpoints with restricted roles lose access.
- Prod applications need the cache rebuilt for the ACL to apply (`oro:platform:update` does it).
- Manual verification needs Oro 6.0/6.1 and Oro 5.x applications; none is available in this environment yet.
