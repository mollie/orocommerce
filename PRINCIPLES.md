# PRINCIPLES.md — how work is done in this repository

Read this together with `CLAUDE.md` (repo map, local rules, sources of truth). It encodes the
working agreement, the spec-driven development flow and the engineering principles that every
change must follow, whether a human or an AI wrote it. Where `CLAUDE.md` or `.claude/rules/` is more
specific, it wins; this file covers everything in between.

## 1. Working agreement

1. **Plan before code.** For any non-trivial change, explore first, then present a plan. Surface every
   real design decision as an explicit question with 2–4 options and their trade-offs; never pick one
   silently. Once the developer chooses, that choice is fixed and goes into the spec/plan verbatim.
2. **Artifacts are in English.** Code, comments, docs, specs, plans and commit messages are all in English.
3. **Report reality.** Run the verification and paste the real result. Never claim a green result
   without running it. If something failed or was skipped, say so first, not last.
4. **No hallucination.** Read a file before editing it, and grep before referencing it. Never invent an
   Oro, Symfony or Mollie API, file, class, service id, event, route, ACL resource or dependency. For
   Mollie API questions, use https://docs.mollie.com/reference/overview. For OroCommerce and Symfony
   questions, the sources of truth are, in this order:
   1. **The local OroCommerce application's source files**, when one is available in the development
      environment (`vendor/oro/platform/src/Oro/…`, `vendor/oro/commerce/src/Oro/…`,
      `vendor/symfony/…`). Read these first, instead of searching online for code that already exists
      locally. Check that its version matches the branch (`composer.lock` entries for `oro/platform`
      and `oro/commerce`: 6.0/6.1 for `master`, 5.0/5.1 for `5.0`). If the location isn't known, ask
      the developer once for the path.
   2. **The official online source and documentation** of the supported versions, listed in
      `CLAUDE.md` § Official references (the oroinc GitHub repositories at the release tag,
      the Markdown documentation `oroinc/documentation-markdown` at the version branch,
      doc.oroinc.com, symfony.com), when there is no local application or its version doesn't match.

   Never answer from memory, and never state platform behaviour as fact without a cited source (file
   + version, or URL): either check it or mark it "not verified". Never derive project facts or
   constraints from IDE or developer-environment files (`.idea/`, `.vscode/`, local settings). Use
   `composer.json`, the bundle configuration, the README, CI and the code.
5. **Ask when a rule conflicts.** If an instruction conflicts with a rule here or in `.claude/rules/`,
   follow the developer but name the rule being overridden.
6. **Consult `LEARNINGS.md` before planning** (§4.4), and append to it whenever the developer
   corrects an approach.
7. **The developer owns git history and the remote.** All work happens in the local repository. The
   developer decides when to push (§3).

## 2. The development flow (spec-driven, mandatory)

Every non-trivial task goes through these phases, in order. The artifacts live in
`docs/specs/<feature>/`. A trivial change (typo, a one-line fix with an obvious cause, a
docs-only edit) may skip Research and Spec, but it still gets a light plan (§2.3) and the full
Verify + Review phases.

| # | Phase | Output | Gate to the next phase |
|---|---|---|---|
| 1 | Research | `docs/specs/<feature>/research.md` | Findings cite files/lines; open questions listed |
| 2 | Spec | `docs/specs/<feature>/spec.md` | No open decision remains; the developer confirms the spec |
| 3 | Plan | `docs/specs/<feature>/plan.md` + `tasks.md` | **The developer approves the plan.** No production code before this. |
| 4 | Implement | Local commits, one per task | Each task's own verification is green before its commit |
| 5 | Verify | Real gate output | All gates green (§2.5) |
| 6 | Review | Findings resolved | `/code-review` → `/simplify` → `/security-review` clean |
| 7 | Hand-off | Change summary to the developer | The developer decides when to push |

### 2.1 Research

- Read-only. Explore the code paths the task touches: entry points (controllers and routes, payment
  callback and Doctrine listeners, operations in `actions.yml`, form subscribers, layout blocks and JS
  components), wrapper services and managers, core calls, and the Oro / Symfony APIs used. For broad
  sweeps, use parallel read-only subagents (Explore) and keep only their conclusions.
- Check the task against `DESIGN.md` (components, flows, constraints), `LEARNINGS.md` and the
  Mollie API reference.
- When the task depends on how OroCommerce or Symfony behave (what an event or payment context
  carries, how ACL or CSRF is applied, what an operation, migration or layout update does), read that
  code: the local application first, otherwise the official source at the branch's version tag (§1.4,
  `CLAUDE.md` § Official references). If the change is ported, check both versions. Cite file and
  version in `research.md`. Anything that could not be checked is listed as "not verified", together
  with how to verify it.
- `research.md` records what exists today (with file paths), what the change will affect, the risks,
  and the questions the spec has to answer. Describe what is there, not what should be there.

### 2.2 Spec

- Interview the developer (AskUserQuestion) in rounds until no open decision remains: goals, scope
  and out-of-scope, acceptance criteria, payment methods affected, branch scope (`master` only, or
  also ported to `5.0`), data/config migration, whether the change needs a core change (§5.8), and
  backward compatibility for merchants who upgrade in place.
- Also ask whether implementation may be delegated to subagents in parallel waves (default: yes), and
  for the Jira ticket if it isn't known yet (§3.3).
- `spec.md` holds: problem and goal, scope and out of scope, acceptance criteria (observable and
  testable), decisions with a one-line *why* each, and open risks.

### 2.3 Plan

- Follow `.claude/plan-template.md`. It must include the **architecture-impact classification**
  (architectural vs business-case-only) and **Risks & emphasis**, which are mandatory.
- **Architectural changes update `DESIGN.md`, including its diagrams, in the same pass.**
- `tasks.md` is the task graph. Each task has an ID, a goal, the files it touches, its verification,
  and `blockedBy` (only where the dependency is real). Group independent tasks into parallel waves.
  Also register the tasks in the task tools (`TaskCreate`/`TaskUpdate`), so progress is visible and
  survives a restart. `tasks.md` stays the durable record.
- `.claude/hooks/plan-gate.sh` enforces the mandatory headings (Architecture impact, Risks &
  emphasis, Verification) on `ExitPlanMode` and on every write of `plan.md`.
- Implementation starts only after the developer explicitly approves the plan.

### 2.4 Implement

- Work on a **local** branch created from `master`. The default name follows the existing convention,
  `feature/<ticket>` or `bugfix/<ticket>` (for example `bugfix/CS-7958`, `feature/PIORO-211`); the
  developer may choose another. Never push.
- The main agent orchestrates, and subagents execute independent tasks in parallel waves when the
  spec allows delegation. Every subagent result is checked against the actual diff before it is accepted.
- **One commit per task**, made only after that task's verification is green. Commit rules are in §3.
- Code follows `CLAUDE.md` § Local rules, `.claude/rules/**` and §5. Keep changes surgical: touch only
  what the task needs.
- **Tests come first where a test harness exists.** Write the failing test, watch it fail for the right
  reason, then implement. The repo has tests only for the copied core and no PHP test harness for the
  wrapper. Until it has one, each task in `tasks.md` names its concrete manual verification (steps plus
  expected result in an OroCommerce application of the branch's version) before the code is written.

### 2.5 Verify (definition of done)

1. **Syntax gate** is green on every changed PHP file: `php -l` (the command is in `CLAUDE.md`
   § Commands). Where available, run it on both PHP 8.1 and the highest PHP version the branch's Oro
   versions require (8.4 for Oro 6.1 on `master`, 8.2 for Oro 5.1 on `5.0`).
2. **PHP 8.1 floor** holds: no syntax or functions newer than PHP 8.1 in changed files, and no
   deprecated patterns of later versions (`CLAUDE.md` § Local rules).
3. **Manual verification** from the plan is executed in an OroCommerce application of the branch's
   version (after `oro:platform:update`, cache clear and, for JS/SCSS, `oro:assets:build`), and the
   result is reported as observed. If it could not be run, say so explicitly; the change is then not
   done.
4. **Core tests**, when `IntegrationCore/` was re-copied: the unit tests in `Tests/Unit/` are updated
   from the core release and run, and the result is reported.
5. **Security checklist** at the end of `.claude/rules/_core/asvs-l2.md` is answered for the change.
6. **Docs are in sync** (§4): `DESIGN.md`, `CLAUDE.md`, `.claude/rules/**`, the plan template and
   `LEARNINGS.md`, wherever the change touched a convention.

### 2.6 Review

Run these in order on the feature diff, and resolve or explicitly answer every finding:

1. `/code-review`: correctness, alignment with `DESIGN.md`, the Risks & emphasis dimensions.
2. `/simplify`: reuse, simplification, efficiency. Fixes are applied and the gates re-run.
3. `/security-review`: `.claude/rules/_core/asvs-l2.md`, `owasp-2025.md` and the per-language rules.

Check each finding against the code before acting on it. A wrong finding gets a technical answer,
not a change. Review fixes are committed like any other task (§3).

### 2.7 Hand-off

Present the developer with a summary: the commits (hash + subject) per branch, the files changed, and
the verification that was run with its real results, plus any gaps. **Stop there.** The developer
reviews and decides when to push and open the PRs, or pushes themselves.

### 2.8 Resume

A session that continues a feature starts from `docs/specs/<feature>/` (`spec.md`, `plan.md`,
`tasks.md`) and `git log`, never from memory. Before continuing, update `tasks.md` to reflect the
commits that already exist.

## 3. Git rules

1. **Local only.** Never `git push`, never open PRs, never change remotes, unless the developer
   explicitly asks for that specific action.
2. **Never change the author.** Commit with the repository's configured git user. Never pass `--author`,
   and never change `user.name` / `user.email`.
3. **Short commit messages in the existing style.** A short subject line: imperative, sentence case,
   no trailing period, no ticket or emoji prefix. Examples from the history:
   - `Add Wero payment method`
   - `Use order entity id for Mollie capture and refund references lookup`
   - `Release 6.2.3`

   When the Jira ticket is known, add exactly one body line `ISSUE: <ticket>` after a blank line (for
   example `ISSUE: CS-8465`). When it isn't known, ask the developer before committing; if there is no
   ticket, commit without the line. No other body, and no trailers (no `Co-Authored-By`, no
   "Generated with").
4. **One task, one commit.** Don't amend or rewrite commits that already exist unless the developer asks.
5. **Never commit secrets** (organization access tokens, including test tokens) or
   developer-environment files (`.idea/`, `.claude/settings.local.json`).
6. **Releases are separate commits.** A release is its own `Release X.Y.Z` commit on each branch, in
   the branch's version line: `composer.json` `version` (`6.x.y` on `master`, `5.x.y` on `5.0`), plus
   the README release notes on `5.0`. The developer merges through a GitHub PR and tags `X.Y.Z` (no
   `v` prefix).

## 4. Documentation discipline

1. **Docs describe the current state.** They say how the code works *now*. The reasoning behind
   significant architecture decisions goes in `DESIGN.md` as short ADRs (What / Why / Cost, numbered).
2. **Docs move in the same pass as the change.** When a flow, component or convention changes, update
   its section and diagrams in `DESIGN.md`. Grep for old names; every hit left in current prose is a bug.
3. **Instruction files are docs too.** When a task changes a convention (a local rule, a gate, a code
   pattern, a branch difference), update its home in the same pass: `CLAUDE.md` (including
   § Supported branches), `PRINCIPLES.md`, `.claude/plan-template.md` or `.claude/rules/**`. If the
   convention has no home yet, create one.
4. **`LEARNINGS.md` is the running record of corrections.** Consult it before planning, and append an
   entry whenever the developer corrects an approach, an assumption proves wrong, or a trap costs
   time. Entry format:

   ```markdown
   ## YYYY-MM-DD — <one-line title>

   - **Symptom:** what went wrong or was corrected
   - **Correction:** what the developer said or what turned out to be true
   - **Rule going forward:** the rule, stated so it can be enforced
   ```

   It is append-only. When a learning becomes a permanent rule, it is also added to its home (§4.3).
5. **Defects and improvement proposals don't go into `DESIGN.md` or `CLAUDE.md`.** `DESIGN.md` §9
   lists only binding constraints of the setup. Found defects, security debts and refactoring ideas
   are reported to the developer, who decides where they are tracked.

## 5. Engineering principles

1. **Simplicity first.** Prefer the simplest solution that solves the actual problem well. Don't add
   layers, abstractions, registries, factories or configuration without a clear and immediate reason.
2. **KISS.** Keep the code simple, explicit and readable. A developer should be able to follow the flow
   without learning a custom framework.
3. **YAGNI.** Don't build for hypothetical future use. Mention a future option instead of implementing it.
4. **SOLID, pragmatically.** Apply it where it improves maintainability and testability, never
   mechanically. Every abstraction needs a concrete responsibility.
5. **Simplify before wrapping.** Check whether existing code can be simplified (moved, renamed,
   reduced) before adding a new abstraction around it.
6. **Clear responsibilities.** Don't mix reading, mapping, validating, transforming, writing and
   Oro-specific behaviour in one unit without reason. In this repo that means controllers, listeners,
   operations and form subscribers stay thin, Oro → Mollie mapping lives in `Mapper/` (and its
   decorators), backoffice actions live in `Manager/`, business logic belongs to the core, and core
   callbacks are implemented in `IntegrationServices/`.
7. **No hidden magic.** Behaviour must be traceable. Services are defined explicitly in
   `services.yml` / `listeners.yml` with their arguments and tags; core-facing registrations go
   through `IntegrationServices/BootstrapComponent`.
8. **Respect the layering.** Oro entry points → wrapper → copied core. The core calls back only
   through the registered integration interfaces. Never edit `IntegrationCore/` in place. A change
   the core should make is made in `mollie/integration-core`, released there, and then re-copied here
   in full (namespace rewritten), on each branch separately.
9. **Preserve the existing project style.** Match naming, namespaces, the DI style, file layout and
   comment density. A diff should read as if the original author wrote it.
10. **Backward compatibility for merchants.** Merchants upgrade in place. Existing integration
    channels and their settings, `mollie_entity` rows (configuration, `OrderReference` keyed by the
    order entity id, Orders API references created by earlier versions), `mollie_payment_settings`
    data, payment method identifiers and order references must keep working after an upgrade. Plan
    the migration path (Oro migrations, data fixtures) explicitly when something must change.
11. **Incremental refactoring.** No big-bang rewrites. Make small, safe steps that preserve behaviour.
12. **Optimal by default.** Avoid N+1 queries and per-row round-trips. Read only what is used. Don't
    make extra Mollie API calls per page load, `isApplicable()` check or webhook. Minimise passes over
    collections. State the request/query count when it matters.
13. **Security by default.** It is weighed in every plan (Risks & emphasis) and reviewed on every
    change (`.claude/rules/`). `strict` rules are never traded for convenience.
14. **Make behaviour verifiable.** Design changes so they can be verified by a test or a concrete
    manual check.
15. **Explain trade-offs.** When several solutions fit, compare them briefly and choose the one with the
    lowest complexity that meets the requirement.

## 6. Decision heuristics

| Situation | Default |
|---|---|
| Add abstraction vs inline | Inline until the third occurrence |
| New dependency vs existing code | Existing code / core service / Oro service |
| Platform helper vs custom code | The platform's own mechanism: Twig autoescaping and filters, Symfony forms and constraints, Oro ACL/CSRF attributes, Oro services and extension points (payment providers, operations, layouts, migrations). Custom code only when none provides it, after checking in the Oro/Symfony source what the helper really does |
| `new Service()` / `ServiceRegister::getService()` vs Symfony DI | A service in `services.yml` / `listeners.yml` with constructor arguments; `BootstrapComponent` registration only where the core resolves it |
| String-built DQL/SQL vs bound parameters | Doctrine QueryBuilder with `setParameter()` |
| Direct entity writes vs Oro services | `PaymentTransactionProvider`, the payment method `execute()`, `OrderTransitionService` |
| Change the core vs work around it in the wrapper | Ask; a core change goes to `mollie/integration-core` first, never into the copy |
| Break config/data vs migrate | Migrate, so merchants upgrading in place keep working |
| Is this done? | Only if the gates are green on every target branch, all three reviews are clean, and the docs match reality |

## 7. Porting changes to `5.0`

Changes are made and verified on `master` first. If the spec says the change also applies to
OroCommerce 5, the port is planned as its own tasks in `tasks.md` and done after the `master` tasks are
green and reviewed. The AI resources are the same on both branches, so no separate setup is needed.

1. **Branch.** Create a local branch from `5.0` using the existing suffix convention, for example
   `feature/<ticket>_5.0` or `bugfix/<ticket>_5.0`. Never push (§3).
2. **Pick the commits.** Take the feature's task commits from `master` in their original order, one at
   a time, with `git cherry-pick <sha>`. This keeps the subject identical (the history shows
   `Add Wero payment method` and `Use order entity id for Mollie capture and refund references lookup`
   on both branches) and keeps the author. Don't add `-x` or trailers; keep the `ISSUE:` line if the
   commit has one. **Never cherry-pick a `Release …` commit**; each branch has its own release (step 6).
3. **Adapt to the branch specifics** (`CLAUDE.md` § Supported branches) while resolving conflicts, or
   in the same commit before continuing:
   - ACL and CSRF: `@Acl` / `@AclAncestor` / `@CsrfProtection` annotations from
     `Oro\Bundle\SecurityBundle\Annotation\…` inside the docblock, not PHP attributes.
   - Session access: inject `@session` / `@session.flash_bag` as the neighbouring services on `5.0`
     do, not `@request_stack`.
   - Controllers: `setContainer` with `@Psr\Container\ContainerInterface`, `$this->get('translator')`
     and `$this->createForm()`, as the existing `5.0` controllers do.
   - Order status enums: the extend enum class only; `EnumOption` does not exist in Oro 5.
   - Migrations and data fixtures: follow the `5.0` layout (`Migrations/Data/ORM/MollieProductAttribute`);
     never bring over `master`'s fixture classes unless the spec says so.
   - Never touch `composer.json` (`version`, `oro/commerce` constraint), the README release notes or
     `.github/workflows/` as part of a port.
   - **Never copy `IntegrationCore/` between branches.** If the change needs a core change, re-copy
     the released core on each branch separately and review the diff (the copies differ, for example
     `PaymentMethodConfig::$paymentOnlyApiMethods`).
4. **If a commit doesn't apply cleanly and the adaptation is more than mechanical**, stop, describe the
   difference to the developer and agree on the approach. Never force the `master` shape onto `5.0`.
5. **Verify on the branch.** Run `php -l` on the changed files, then the plan's manual verification
   in an OroCommerce 5.0/5.1 application, then the review sequence (§2.6) on the `5.0` diff. Report the
   results separately for each branch.
6. **Release** on `5.0` is its own `Release 5.x.y` commit: `composer.json` `version` and the README
   release notes (`**5.x.y:**` entry), as the branch's earlier release commits did.
7. **AI resources.** Changes to `CLAUDE.md`, `PRINCIPLES.md`, `DESIGN.md`, `LEARNINGS.md` or `.claude/`
   are brought over unchanged (`git checkout master -- <paths>` on the `5.0` branch), in their own
   commit. Branch specifics are only ever described in `CLAUDE.md` § Supported branches, never by
   making the files differ between branches.
