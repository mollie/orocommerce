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
