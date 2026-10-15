# Plan authoring standard (`docs/specs/<feature>/plan.md`)

Every task plan in this repo MUST contain the sections below, in this order. A plan must be detailed
enough to implement without re-deriving decisions, and reviewable on its own. Keep prose tight and
prefer tables, diagrams and signatures over paragraphs.

The plan and its implementation comply with `PRINCIPLES.md`, `CLAUDE.md` § Local rules, `DESIGN.md`
and the security rules in `.claude/rules/`. **Reference those documents; never restate them in the plan.**

## Scope tiers

- **Light plan**: for a small change (docs-only, config tweak, rename, single-file bugfix). Include §1,
  §2 (if any decisions), §3, a one-line §4 and §10–§11. Skip the rest.
- **Full plan**: everything else (new payment method, new flow, schema/config change, core re-copy,
  multi-file change). Include all sections, in order.

## Required sections

1. **Title + summary.** What the task delivers, the spec it realizes
   (`docs/specs/<feature>/spec.md`), its prerequisites, and explicitly what is **out of scope**. Name
   the target branches: `master` first, and whether it is ported to `5.0` (`PRINCIPLES.md` §7). Say
   whether the change needs a `mollie/integration-core` release and a re-copy of `IntegrationCore/`
   (`PRINCIPLES.md` §5.8); if so, that is a prerequisite.

2. **Decisions.** The locked choices, each with a one-line *why* (plus the cost when it isn't obvious).
   Say where it reuses an existing mechanism (a core service, a Symfony service in `services.yml` /
   `listeners.yml`, a `BootstrapComponent` registration, an Oro extension point, an existing manager,
   mapper decorator or listener) instead of inventing one. Name any security rule it satisfies or
   overrides.

3. **Architecture impact.** Either **architectural** (new or changed component, flow, boundary,
   persistence, route, external call, or a change to the core) or **business-case-only**. If
   architectural, list the `DESIGN.md` sections and diagrams this plan updates in the same pass. Use
   the ownership labels `«oro»` / `«mollie-oro»` / `«mollie-core»` / `«mollie-api»` / `«mollie-js»`.

4. **Risks & emphasis** *(mandatory)*. State the risk and its mitigation for each of these:
   - **Security**: input validation (forms/constraints, route requirements), Twig escaping and `|raw`,
     DQL with bound parameters, a real ACL attribute on every action (annotation on `5.0`) and
     `#[CsrfProtection]` on state changes, storefront order ownership, listeners limited to Mollie
     methods, file uploads, webhook authenticity and idempotency, secrets/PII in logs and debug data
     (`.claude/rules/_core/asvs-l2.md` checklist).
   - **Money and state correctness**: amounts, taxes, rounding, currency, surcharge, the sum of lines
     versus the payment amount, capture/refund limits, and Oro payment-transaction and order-status
     transitions.
   - **Backward compatibility**: existing `mollie_entity` rows (configuration, `OrderReference` with
     the entity id as `shopReference`, Orders API references created by earlier versions),
     `mollie_payment_settings` data, integration channel settings, payment method identifiers
     (`mollie_payment_<channelId>_<mollieId>`), and the migration path (`Migrations/Schema/vX_Y`,
     data fixtures) for merchants upgrading in place.
   - **Compatibility**: the PHP 8.1 floor of the shared code, the Oro / Symfony APIs used on each
     branch (Oro 6.0–6.1 on `master`, Oro 5.x on `5.0`; attributes vs annotations, `RequestStack` vs
     session services, `EnumOption`), and the Oro templates and layout blocks the bundle overrides.
   - **Performance**: DB queries per page load, Mollie API calls per request or webhook (payment step,
     `isApplicable()`, payment creation, admin order page), and complexity over order lines.
   - **Alignment**: `DESIGN.md` layering (Oro entry point → wrapper → core), the DI conventions
     (Symfony container, `BootstrapComponent` only where the core resolves a service), and the core
     copy staying unedited.

5. **Model / flow diagrams** (full plan). The entities touched (with ownership labels) and the runtime
   flows added or changed, as Mermaid `classDiagram` / `sequenceDiagram` in the `DESIGN.md` style.

6. **Persistence changes** (when it touches data). New or changed configuration names, `mollie_entity`
   types and indexes, Doctrine entities/columns, extended fields, Oro tables written, and the
   migration (`Migrations/Schema/vX_Y/*`, installer version, data fixtures) for existing shops.

7. **Class outline.** For each changed or new class: its path, namespace, responsibility, the
   signatures of its key members, and its service definition (`services.yml` / `listeners.yml`, tags,
   arguments) or `BootstrapComponent` registration.

8. **Files.** The exact files to create or edit. For repeated patterns (for example a new payment
   method: `PaymentMethods`, `PaymentMethodConfig` restrictions via a core re-copy,
   `CaptureModeRestrictions`, translations in all `messages.*.yml` locales), describe the pattern once
   and list representative files.

9. **Tasks.** The task graph that goes into `tasks.md`: ID, goal, files, verification, `blockedBy`,
   and wave. Each task is one commit (`PRINCIPLES.md` §3). Port tasks for `5.0` come after the
   `master` tasks are green and reviewed.

10. **Verification.** The exact commands (`php -l` on the changed files, and the core unit tests when
    the core copy changes) and the **manual verification script**: install the bundle into an
    OroCommerce application of the branch's version (`composer`, `oro:platform:update`, cache clear,
    `oro:assets:build` when JS/SCSS changed), then the steps with the expected result for each
    (channel connect/save, checkout, redirect return, webhook, admin capture/refund/payment link, order
    change, as applicable), plus the review sequence `/code-review` → `/simplify` → `/security-review`.
    Name the instruction/doc files this task updates, or state "none".

11. **Done.** The definition of done: gates green on every target branch, the observable outcome
    confirmed, and `DESIGN.md` / instruction files in sync.

## Clear-explanation rule

Every diagram and class outline comes with a short plain-language explanation of *why* it looks that
way and which requirement it serves. A reviewer should understand the intent, not just the shape.
