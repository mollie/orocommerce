# Learnings

This file is append-only. It records corrections and traps. Read it before planning. The entry format is in `PRINCIPLES.md` §4.4.

## 2026-10-09 — ACL/CSRF attributes written inside docblocks were never enforced (PIORO-225)

- **Symptom:** the Oro 6 compatibility commit `1680d13` replaced `@Acl(…)` / `@AclAncestor(…)` /
  `@CsrfProtection()` inside the docblocks with `#[Acl(…)]` etc. in place. PHP treats them as comment
  text, so five controller actions on `master` (6.0.0–6.2.3) had no ACL or CSRF check (HackerOne
  #3990813). `ActivePaymentMethodsController` never declared an ACL on either branch.
- **Correction:** Oro 6 reads ACL only through `ReflectionMethod::getAttributes()`, and an action
  without a bound ACL is allowed (`ClassAuthorizationChecker`, platform 6.0.x). `#[Acl]` with an id Oro
  already defines silently overwrites it; reuse ids with `#[AclAncestor]`.
- **Rule going forward:** on `master`, ACL/CSRF attributes go between the docblock and the method,
  reusing existing ids through `#[AclAncestor]`; every new action declares one. `acl-attributes.yml`
  enforces the placement in CI.

## 2026-10-13 — The version check compared against the wrong branch and a stale fallback

- **Symptom:** `ConfigurationService::VERSION_CHECK_URL` still pointed to the `4.2` branch of
  `mollie/orocommerce` (version `4.2.10`), so 5.x/6.x shops were never told about a new release. The
  fallback parameter `mollie_payment.version` stayed at `5.0.10`, because the release rule named only
  `composer.json`. The upstream tags for 6.2.1–6.2.3 and 5.3.1–5.3.3 were pushed with a `v` prefix,
  so the download link `…/releases/tag/<version>` was a 404.
- **Correction:** the URL reads the branch's own upstream line (`master` / `5.0`), the release commit
  bumps `composer.json` and `mollie_payment.version` together, tags have no `v` prefix on both remotes,
  and a failing version check is caught and logged instead of breaking the channel form.
- **Rule going forward:** a release touches every place the version lives (`CLAUDE.md` § Local rules,
  `PRINCIPLES.md` §3.6). When `master` moves to a new Oro major, re-point `VERSION_CHECK_URL` on the
  previous line's branch.
