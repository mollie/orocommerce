# CLAUDE.md

## What this repository is

The Mollie payment bundle for **OroCommerce**: the Symfony bundle `MolliePaymentBundle`
(`Mollie\Bundle\PaymentBundle`, Composer package `mollie/orocommerce`, `type: symfony-bundle`, PSR-4
root = repository root). Two branches are actively maintained: `master` (OroCommerce 6.x, the primary
branch) and `5.0` (OroCommerce 5.x). All other branches and Oro versions are out of scope; branch
specifics are in § Supported branches.

The shop admin connects Mollie through an Oro integration channel (`mollie_payment_channel`, one per
organization access token and website profile). Every active Mollie method becomes an Oro payment
method. The bundle lists methods in checkout (with Mollie Components, issuers and a surcharge
subtotal) and creates payments through the Mollie Payments API. It handles the redirect return and the
webhook (Oro's `oro_payment.callback.*` events). On the backoffice order page it adds capture, refund
and payment-link operations, and it propagates Oro order changes (cancel, delete) to Mollie.

Business logic lives in a **manual, re-namespaced copy** of the shared core `mollie/integration-core`
under `IntegrationCore/` (`Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic|Infrastructure\…`;
the last full re-copy was core 1.3.4). This repo is the Oro **wrapper** that implements the core's
integration interfaces. See `DESIGN.md`.

## Supported branches

The same AI resources (`CLAUDE.md`, `PRINCIPLES.md`, `DESIGN.md`, `LEARNINGS.md`, `.claude/`) are kept
**identical on both branches**. Everything branch-specific is in this table. Architecture, layout,
commands, rules and the development flow are shared. Changes are made on `master` first and then
ported (`PRINCIPLES.md` §7).

| | `master` | `5.0` |
|---|---|---|
| OroCommerce versions | `oro/commerce ^6.0` (6.0, 6.1); Oro itself requires PHP `~8.3` (6.0) / `~8.4` (6.1) | `oro/commerce ^5.0` (5.0, 5.1); Oro itself requires PHP `~8.1` (5.0) / `~8.2` (5.1) |
| Module version line / tags | `6.x.y` (`composer.json` `version`, tag `6.2.3`) | `5.x.y` (tag `5.3.3`) |
| Local branch suffix (existing convention) | none (for example `bugfix/CS-7958`, `feature/PIORO-211`) | `_5.0` (for example `bugfix/CS-7958_5.0`, `feature/PIORO-218_5.0`) |
| ACL / CSRF declarations | PHP attributes `Oro\Bundle\SecurityBundle\Attribute\{Acl,AclAncestor,CsrfProtection}` | Doctrine annotations `Oro\Bundle\SecurityBundle\Annotation\{Acl,AclAncestor,CsrfProtection}` |
| Session / flash access | `@request_stack` → `getSession()?->getFlashBag()` | `@session` / `@session.flash_bag` services |
| Controller service locator (`setContainer`) | `@oro_platform.twig.service_locator`; `$this->container->get(TranslatorInterface::class)`, injected `FormFactoryInterface` | `@Psr\Container\ContainerInterface`; `$this->get('translator')`, `$this->createForm()` |
| Entity references in configuration | FQCN (`Oro\Bundle\IntegrationBundle\Entity\Channel`) | also bundle aliases (`OroIntegrationBundle:Channel`) |
| Order status enum | `EnumOption` when it exists (Oro 6.1), extend enum class otherwise | extend enum class only |
| Product attribute data fixtures | `Migrations/Data/ORM/LoadMollieProductAttributeFamilyData`, `LoadMollieVoucherCategoryData`, `MollieProductAttribute` | `Migrations/Data/ORM/MollieProductAttribute` only |
| README release notes | none (links to the GitHub Releases page) | release notes per version (`**5.x.y:**` list) |
| `.github/workflows/codeql.yml` | present (branch `master`) | absent |
| Copied core (`IntegrationCore/`) | includes `PaymentMethodConfig::$paymentOnlyApiMethods` | without `$paymentOnlyApiMethods`; otherwise identical to `master`. **Never copy `IntegrationCore/` between branches**; re-copy per branch |

## Architecture map

- `MolliePaymentBundle.php`: the bundle class. `boot()` calls `IntegrationServices\BootstrapComponent::boot()`.
- `DependencyInjection/PaymentExtension.php`: loads `Resources/config/services.yml` and `listeners.yml`.
- `IntegrationServices/`: the core composition root (`BootstrapComponent`) and the core boundary
  implementations (`ConfigurationService`, `OrderTransitionService`, `OrderLineTransitionService`,
  `LoggerAdapter`/`LoggerService`, `VersionCheckService`, `TransactionDescriptionService`), plus
  `FileUploader`, `DebugService`, `PlatformVersionReader`
- `IntegrationCore/`: the copied, re-namespaced `mollie/integration-core`. **Never edit in place.**
- `Integration/`: Oro integration channel type and transport
- `Entity/`: Doctrine entities (`ChannelSettings`, `PaymentMethodSettings`, `MollieBaseEntity` → table
  `mollie_entity`), core repositories (`Repository/`), extend generator extension
- `PaymentMethod/`: the Oro payment method (`MolliePayment`), provider, factory, config providers,
  views, payment creators, `Config/CaptureModeRestrictions`
- `Mapper/`: Oro → Mollie DTO mapping (`MollieDtoMapper`) and the checkout order mapper decorator
- `Manager/`: services behind operations and controllers (capture, refund, payment link, utilities)
- `Controller/`: bundle routes (return callback, payment link, connection validation, active methods, support)
- `EventListener/`: payment callbacks (`Callback/`), Doctrine entity listeners, surcharge, datagrid, kernel exception
- `Form/`: channel and method settings forms and subscriber, operation forms
- `Condition/`, `Provider/`, `Exceptions/`: action condition, surcharge subtotal provider, exceptions
- `Migrations/`: installer, versioned schema migrations, data fixtures
- `Resources/config/`: DI (`services.yml`, `listeners.yml`), `jsmodules.yml`, Oro config (`oro/*.yml`)
- `Resources/views/`, `Resources/public/`, `Resources/translations/`: Twig templates and layout
  themes, JS page components and SCSS, `messages.*.yml` (en, de, de_DE, fr, fr_FR, nl, nl_NL)
- `Tests/Unit/`: unit tests of the copied core only
- `.github/workflows/codeql.yml`: CodeQL (JavaScript only, `master`)

## Where the instructions live

| Scope | File | What it covers |
|---|---|---|
| **Working agreement + development flow** | `PRINCIPLES.md` | Plan first with explicit design options, the spec-driven flow (research → spec → plan → implement → verify → review → hand-off), git rules, documentation discipline, engineering principles, porting to `5.0`. **Mandatory for every change; read it first.** |
| **Architecture** | `DESIGN.md` | The living architecture record: layers, components, domain model, key flows (with ownership labels), module map, patterns, boundaries, constraints. Updated in the same pass as any architectural change. |
| **Plan standard** | `.claude/plan-template.md` | The required sections of every `plan.md`, including Architecture impact and Risks & emphasis. |
| **Security** | `.claude/rules/` | See "MANDATORY sources of truth" below. |
| **Feature artifacts** | `docs/specs/<feature>/` | `research.md`, `spec.md`, `plan.md`, `tasks.md` for each feature. This is the source for resuming work. |
| **Corrections** | `LEARNINGS.md` | Append-only record of corrections and traps. Read it before planning. Format in `PRINCIPLES.md` §4.4. |

## Commands

There is no build step, no composer script, no linter, no static analysis and no PHP test job in CI.
The bundle runs only inside an OroCommerce application.

```bash
# PHP syntax check (not configured in repo — ad hoc; the core copy is not edited, so it is skipped)
find . -name '*.php' -not -path './IntegrationCore/*' -not -path './Tests/*' -not -path './.git/*' \
  -print0 | xargs -0 -n1 php -l
```

`Tests/Unit/` covers the copied core only. There is no `phpunit.xml` or `require-dev` in the repo;
running it needs an ad hoc PHPUnit setup that maps `Mollie\Bundle\PaymentBundle\` to the repository
root. It is relevant only when `IntegrationCore/` is re-copied.

Verification of wrapper code is manual, in an OroCommerce application of the branch's version
(`master`: 6.0/6.1, `5.0`: 5.0/5.1):
1. Require the bundle from this working copy (Composer path or VCS repository) and run
   `php bin/console oro:platform:update --force`, then clear the cache. Run `php bin/console oro:assets:build`
   when JS or SCSS changed.
2. System » Manage Integrations (see the project wiki page "Installation and setup"): create or open a Mollie channel and connect it with a
   test organization access token.
3. Exercise the flow under test (checkout, return, webhook, backoffice order operations).

## Local rules

- **PHP floor is 8.1 for all shared code** (`composer.json` `"php": ">=8.1"`, and OroCommerce 5.0 on
  the `5.0` branch). Don't use syntax or functions newer than PHP 8.1, even though Oro 6 itself needs
  PHP 8.3+, because changes are ported to `5.0`. Avoid patterns deprecated in later versions (for
  example implicit nullable parameters: write `?Type $x = null`).
- **The core copy is never edited in place.** A core change is made in `mollie/integration-core`,
  released there, and re-copied into `IntegrationCore/` in full, with the namespace rewritten to
  `Mollie\Bundle\PaymentBundle\IntegrationCore\…`. The re-copy is reviewed as a diff, on each branch
  separately. Wrapper-specific code lives outside `IntegrationCore/`.
- **Dependencies are wired in the Symfony container.** Define wrapper services in
  `Resources/config/services.yml` / `listeners.yml` with explicit arguments (there is no autowiring)
  and inject core services by their FQCN service ids (exposed through `factory: [<CoreClass>, getInstance]`).
  Register a service in `IntegrationServices/BootstrapComponent::initServices()` only when core code
  must resolve it through `ServiceRegister`. Avoid `new` for services and new `ServiceRegister::getService()`
  calls in wrapper code.
- **Per-channel work runs in the channel context**: `Configuration::doWithContext((string)$channelId, fn)`.
- **Controller security is declared, not assumed.** Every action carries a real ACL attribute
  (annotation on `5.0`), and state changes are POST with CSRF protection
  (`.claude/rules/languages/php/CLAUDE.md` § Access Control and CSRF). An attribute inside a docblock
  is not read on Oro 6.
- **Persistence goes through Doctrine and Oro services.** Core entities go into `mollie_entity` through
  the `Entity/Repository/` implementations; queries use bound parameters. Schema changes are Oro
  migrations (`Migrations/Schema/vX_Y/`, plus the installer for fresh installs). Payment transactions
  and order status change through `PaymentTransactionProvider` and `OrderTransitionService`.
- **Keep the entry points thin.** Controllers, event listeners, operations and form subscribers
  validate input, call a manager/wrapper service or core service, and return a response, flash message
  or result array.
- **Strings** go into `Resources/translations/messages.*.yml` (`mollie.payment.*`) for every existing
  locale (en, de, de_DE, fr, fr_FR, nl, nl_NL) and are used through `|trans` / `TranslatorInterface`.
- **JS** is Oro page components registered in `jsmodules.yml` (`molliepayment/js/app/...`); see
  `.claude/rules/languages/javascript/CLAUDE.md`.
- **Payments API only.** New payments use the Mollie Payments API. Orders API references created by
  earlier versions stay supported (refund, webhook, shipment, checkout link).
- **Release: bump versions in lockstep, in a separate `Release X.Y.Z` commit.** Update `composer.json`
  `version` in the branch's own line (`6.x.y` / `5.x.y`); on `5.0` also add the README release notes.
  The developer merges (GitHub PR) and tags `X.Y.Z` (no `v` prefix).
- **Porting**: changes land on `master` first. The spec decides whether they also go to `5.0`, and if
  so they are ported per `PRINCIPLES.md` §7, keeping the branch specifics from § Supported branches.

## Official references (use them when researching)

Check every claim about OroCommerce, Symfony or Mollie behaviour in the source or the official
documentation, and cite it (file + version tag, or URL). **Read a local OroCommerce application's
`vendor/oro/` sources first** when one is available in the development environment (ask the developer
once for the path; check the version in its `composer.lock`), and use the online sources below only
when there is none or its version doesn't match (`PRINCIPLES.md` §1.4). **Never assert platform
behaviour from memory or by inference.** If it can't be checked, say "not verified". A plausible claim
can still be wrong: `#[AclAncestor(...)]` written inside a controller docblock looks like an ACL
declaration, but Oro 6.0 reads ACL with native `ReflectionMethod::getAttributes()`
(`Oro\Component\PhpUtils\Attribute\Reader\AttributeReader`, oroinc/platform 6.0.0), so it is never
applied.

| Topic | Official source | Notes |
|---|---|---|
| Mollie API | https://docs.mollie.com/reference/overview | Endpoints, payloads, statuses, webhooks |
| OroCommerce source | https://github.com/oroinc/orocommerce | Read the tag of the supported version (for example `6.0.0`, `6.1.0`, `5.1.0`); raw files via `https://raw.githubusercontent.com/oroinc/orocommerce/<tag>/<path>` |
| Oro platform source | https://github.com/oroinc/platform | Security, Action, Integration, Entity, UI bundles; same tag scheme; `https://codeload.github.com/oroinc/platform/tar.gz/refs/tags/<tag>` for a full tree |
| OroCommerce application | https://github.com/oroinc/orocommerce-application | The reference application (`composer.json`, config) per version |
| Oro documentation | https://doc.oroinc.com/ (backend: https://doc.oroinc.com/backend/, versioned: https://doc.oroinc.com/6.0/, https://doc.oroinc.com/5.1/) | Bundles, ACL, operations, layouts, JS components, migrations |
| Oro documentation (Markdown for AI) | https://github.com/oroinc/documentation-markdown | Official Markdown variant of doc.oroinc.com, one branch per version (`5.0`, `5.1`, `6.0`, `6.1`, …); raw files via `https://raw.githubusercontent.com/oroinc/documentation-markdown/<version>/<path>` (for example `backend/…`, `bundles/…`, `frontend/…`). Prefer it over fetching the HTML docs |
| Oro system requirements | https://doc.oroinc.com/backend/setup/system-requirements/ | PHP and service versions per Oro release |
| Oro API reference | https://doc.oroinc.com/api/ | Oro REST/JSON:API docs |
| Oro downloads | https://oroinc.com/b2b-ecommerce/download/ | Official release packages |
| Symfony | https://symfony.com/doc/6.4/ (Oro 6), https://symfony.com/doc/5.4/ (Oro 5) and https://github.com/symfony/symfony | Framework, forms, security, Twig integration |

Unofficial mirrors, community forums and community AI skills are not sources of truth. At most they
are pointers to the official source, which is then read.

## MANDATORY sources of truth

The security rules live in `.claude/rules/`. Two of them are copied from
[TikiTribe/claude-secure-coding-rules](https://github.com/TikiTribe/claude-secure-coding-rules):
`_core/owasp-2025.md` and `cicd/github-actions/CLAUDE.md`. They are MIT-licensed; which files, and
what was changed, is in `.claude/rules/THIRD-PARTY-NOTICES.md`. The other files (`_core/asvs-l2.md`,
`languages/php/CLAUDE.md`, `languages/javascript/CLAUDE.md`) were written for this repository and
cover the bundle's real surface: PHP, Twig and configuration in OroCommerce, and browser-only
JavaScript.

### Always applied (every change, every file)

| Rule set | File | Why mandatory |
|---|---|---|
| **OWASP ASVS v5.0 — Level 2** | `.claude/rules/_core/asvs-l2.md` | The verification standard this bundle is held to (payments, merchant credentials, shopper PII). Walk the checklist at the bottom before declaring any work done. |
| **OWASP Top 10 (2025)** | `.claude/rules/_core/owasp-2025.md` | Cross-cutting controls for the most common web vulnerabilities. |

### Applied per stack (path-scoped, loaded automatically when a matching file is read)

| Stack | Rule file | Loads when reading |
|---|---|---|
| PHP, Twig, configuration (bundle) | `.claude/rules/languages/php/CLAUDE.md` | `*.php` in the root, `{Condition,Controller,DependencyInjection,Entity,EventListener,Exceptions,Form,Integration,IntegrationServices,Manager,Mapper,Migrations,PaymentMethod,Provider}/**/*.php`, `Resources/views/**/*.twig`, `Resources/config/**/*.yml` (not `IntegrationCore/`, not `Tests/`) |
| JavaScript | `.claude/rules/languages/javascript/CLAUDE.md` | `Resources/public/js/**/*.js`, `Resources/config/jsmodules.yml`, `Resources/views/layouts/**/*.yml` |
| CI/CD | `.claude/rules/cicd/github-actions/CLAUDE.md` | `.github/workflows/**` |

The per-stack files are required reading for a change in their scope, even before they load
automatically.

### Enforcement levels (used inside the rule files)

- `strict`: refuse to generate violating code. No "but it works" exceptions.
- `warning`: generate the code, but flag the issue and propose an alternative in the same response.
- `advisory`: mention it as a best practice.

### Conflicts

If a rule conflicts with an explicit developer instruction, follow the developer but **name the
rule being overridden**.

## Tooling

- **Built-in review skills**: `/code-review`, `/simplify` and `/security-review` are the mandatory
  review sequence (`PRINCIPLES.md` §2.6).
- **Plugin**: `.claude/settings.json` enables `security-guidance@claude-plugins-official` from the
  built-in official marketplace. It warns about risky patterns while editing. No other marketplace is
  required.
- **Only official plugins and skills.** The repo references only built-in Claude Code skills and
  plugins from the official Anthropic marketplace (`claude-plugins-official`). Community marketplaces,
  plugins and skills (including Oro or Symfony ones) are never added to `.claude/settings.json` or
  referenced from the instruction files. Platform knowledge comes from § Official references.
- **Task tools**: `.claude/settings.json` sets `CLAUDE_CODE_ENABLE_TODO_TOOLS=1`, so
  `TaskCreate`/`TaskUpdate`/`TaskList` are available for the task graph (`PRINCIPLES.md` §2.3). The
  tools bind at session start, so restart Claude Code after changing this setting.
- **Plan-section gate**: `.claude/hooks/plan-gate.sh`, registered in `.claude/settings.json`,
  denies `ExitPlanMode` and blocks a written `docs/specs/<feature>/plan.md` when the plan is missing
  one of the headings **Architecture impact**, **Risks & emphasis** or **Verification**. It reads the
  hook JSON with `python3`, or `php` as a fallback. If neither is installed, it lets the plan through
  and prints a warning.
- **Permissions**: `git push`, `git commit --amend`, `git rebase` and `git reset --hard` always ask
  first. Reading `.env*`, keys and `auth.json` is denied.
- **Per-developer settings** go into `.claude/settings.local.json`, which is git-ignored.

## Development flow (mandatory)

Every task goes through the spec-driven flow in `PRINCIPLES.md` §2, in every session and without being
asked. Skipping a phase gate is a defect, not a shortcut.

| Phase | What happens | Artifact / gate |
|---|---|---|
| Research | Read-only exploration of the affected code, `DESIGN.md`, `LEARNINGS.md`, the Mollie API reference and the Oro source of the branch's version; parallel read-only subagents for broad sweeps | `docs/specs/<feature>/research.md` |
| Spec | Interview the developer (AskUserQuestion) until no decision is open, including branch scope (`master` only or also `5.0`), core impact, migration and delegation | `docs/specs/<feature>/spec.md`, confirmed by the developer |
| Plan | Per `.claude/plan-template.md`: architecture impact, Risks & emphasis, task graph | `plan.md` + `tasks.md`; **the developer approves before any code** |
| Implement | Local `feature/<ticket>` or `bugfix/<ticket>` branch from `master`; subagents in parallel waves where allowed; **one short commit per task**, author unchanged | Each task's verification green before its commit |
| Verify | `php -l` on changed files, the PHP 8.1 floor, the plan's manual verification in an Oro application, the ASVS checklist, docs in sync | Real output reported |
| Review | `/code-review` → `/simplify` → `/security-review`; every finding verified against the code | All findings resolved or answered |
| Port | If the spec says so: the same commits on a `_5.0` branch from `5.0`, adapted per § Supported branches (`PRINCIPLES.md` §7) | Verify + Review repeated on `5.0` |
| Hand-off | Summary of commits, files and verification to the developer | **No push**: the developer decides when to push |
| Resume | Continue from `docs/specs/<feature>/` + `git log`, never from memory | `tasks.md` updated first |

### Git rules (summary of `PRINCIPLES.md` §3)

- Everything stays in the local repository. Never push, open PRs or touch remotes unless the developer
  asks for that specific action.
- Never change the commit author. Use the configured git user, with no `--author`.
- Commit messages are a short subject line in the existing style (`Add Wero payment method`,
  `Use order entity id for Mollie capture and refund references lookup`, `Release 6.2.3`). When the
  Jira ticket is known, add one body line `ISSUE: <ticket>` (for example `ISSUE: CS-8465`); when it
  isn't known, ask before committing, and commit without the line if there is no ticket. No other
  body, no `Co-Authored-By` and no other trailers.

## When you need to write code

1. Re-read `PRINCIPLES.md`, `LEARNINGS.md`, the relevant `DESIGN.md` sections and the applicable rule
   files (`asvs-l2.md` + the stack file). Don't work from memory.
2. Follow the development flow above. For a non-trivial change, research, spec and plan come
   before code.
3. Generate code that satisfies the `strict` rules unconditionally. If you cannot, stop and ask.
4. Verify before claiming done, and report the real results.
5. Run the review sequence and resolve the findings.
6. Update the docs and instruction files in the same pass (`PRINCIPLES.md` §4), and append any
   correction to `LEARNINGS.md`.
