# PIORO-225 — Research: inert ACL/CSRF attributes in controllers

Source: HackerOne report #3990813 (synced as SSD-16868), "ACL/CSRF attributes placed inside PHPDoc
comments are never enforced". Branch under analysis: `master` (Oro 6). Research date: 2026-10-08.

No local OroCommerce application was available. All platform facts below come from the official
sources at release tags (oroinc/platform, oroinc/orocommerce, oroinc/customer-portal,
oroinc/orocommerce-application 6.0.0, compared with 6.1.0; symfony/symfony v6.4.0;
oroinc/documentation-markdown 6.0). The files compared between 6.0.0 and 6.1.0 show no behaviour
difference. Platform paths are relative to `src/Oro/Bundle/` unless stated otherwise.

## 1. What exists today

### 1.1 Affected controller actions (`master`)

| Route (name) | Action | Intended control (inert, inside `/** */`) | Effective control today |
|---|---|---|---|
| `GET /admin/mollie/support/status` (`mollie_payment_support_status`) | `SupportController::getDebugStatus` (`Controller/SupportController.php:46-58`) | `#[Acl(id="oro_integration_view", type="entity", permission="VIEW", class=Channel)]` | Back-office login only |
| `POST /admin/mollie/support/status` (`mollie_payment_support_status_update`) | `SupportController::updateDebugStatus` (`:67-87`) | same `#[Acl]` (VIEW); no CSRF declared at all | Back-office login only; no CSRF |
| `GET /admin/mollie/support/download_debug_data` (`mollie_payment_support_download_debug`) | `SupportController::downloadDebugData` (`:97-114`) | same `#[Acl]` | Back-office login only (but see 1.3) |
| `POST /admin/mollie/validate-connection/{channelId}/` (`mollie_payment_validate_connection`) | `AjaxMollieController::validateConnectionAction` (`Controller/AjaxMollieController.php:33-63`) | `#[AclAncestor("oro_integration_update")]`, `#[CsrfProtection()]` | Back-office login only; no CSRF |
| `GET /mollie/paymentlink/generate/{orderId}` (`mollie_payment_link`, storefront) | `PaymentLinkController::goToMolliePaymentPage` (`Controller/PaymentLinkController.php:37-63`) | `#[AclAncestor("oro_order_frontend_view")]` | **None**: reachable by anyone, including guests |

Not in the report, same class of defect:

| Route | Action | Control |
|---|---|---|
| `GET /admin/mollie/methods/active/{channelId}/{profileId}` (`mollie_active_payment_methods`) | `ActivePaymentMethodsController::getActivePaymentMethods` (`Controller/ActivePaymentMethodsController.php:43-66`) | **No ACL declared at all**, on `master` and on `5.0` (`git show 5.0:Controller/ActivePaymentMethodsController.php`). `channelId` and `profileId` are unvalidated route parameters; `profileId` is also concatenated into a log line (`:59`). |

Routes come from `Resources/config/oro/routing.yml` (all with prefix `/mollie`; `mollie_payment_link` and
`mollie_payment_callback` have `frontend: true`). Controllers are explicit services in
`Resources/config/services.yml:372-398` with `setContainer(@oro_platform.twig.service_locator)`.
The real attribute classes are imported in all three files (`use Oro\Bundle\SecurityBundle\Attribute\…`)
but never used.

### 1.2 How it happened (git)

- Introduced in `1680d13` "add initial compatibility with OroCommerce 6.0" (2024-04-26). That commit
  replaced `@Acl(` / `@AclAncestor(` / `@CsrfProtection()` inside the docblocks with `#[Acl(` /
  `#[AclAncestor(` / `#[CsrfProtection()]` in place and switched the imports from `Annotation\` to
  `Attribute\`. The Doctrine-style argument syntax (`id="…"`) was kept.
- First Oro 6 version containing it: `6.0.0` (`composer.json` on `origin/oro6`, 2024-07-11; not tagged in
  this repo). First tag here: `6.0.2` (2025-12-26). Every `6.x` release up to `6.2.3` is affected.
- `5.0` is **not affected** by the inert-attribute bug: it still has valid Doctrine annotations
  (`@Acl`, `@AclAncestor("oro_integration_update")`, `@CsrfProtection()`, `@AclAncestor("oro_order_frontend_view")`),
  which Oro 5 reads from docblocks. The missing ACL on `ActivePaymentMethodsController` exists on both
  branches.

### 1.3 What each endpoint does

- **`downloadDebugData`** → `DebugService::getDebugDataFilePath()` (`IntegrationServices/DebugService.php:40-58`)
  builds a ZIP with full `phpinfo()` output (environment variables, which may hold DB credentials and
  the kernel secret), `order-references.json` (all `OrderReference` rows) and **every file in
  `%kernel.logs_dir%`** (`services.yml:287-290`), i.e. the whole application's logs, not only the
  plugin's. The report understates this.
  - However, the action calls `$this->get(DebugService::class)` (`SupportController.php:108`).
    `AbstractController::get()` does not exist in Symfony 6.4 (`FrameworkBundle/Controller/AbstractController.php`
    v6.4.0; `UPGRADE-6.0.md` L110 "Remove `get()`, `has()` … in `AbstractController`"). On Oro 6 the
    action therefore ends in a PHP `Error` (500) before any data is produced. Verified from source,
    **not verified on a live instance**. The disclosure is real on any setup where the call succeeds,
    and the endpoint is functionally broken either way.
- **`getDebugStatus` / `updateDebugStatus`** read/set the global `debugModeEnabled` config
  (`Configuration`). Debug mode makes `LoggerService` log everything and enables the webhook URL
  replacement (`DESIGN.md` §8 Logging). No UI in this repo calls these routes or the download route
  (grep over `Resources/` finds no reference); they are reached by typing the URL.
- **`validateConnectionAction`** builds the channel form, `handleRequest()`s it and returns whether the
  `transport.authToken` field is valid (token validation calls Mollie `GET /v2/permissions`). It
  persists nothing. Because only the sub-field validity is read, the form's own CSRF token does not
  protect it either. Called by Oro's `orointegration/js/app/components/integration-connection-checker-component`
  (`Resources/views/Form/authorization.html.twig:22-26`), which POSTs the serialized form with jQuery `$.ajax`.
- **`goToMolliePaymentPage`** (via `AdminLinkVisitHandler::handleAndGetCheckoutUrl`, `Manager/AdminLinkVisitHandler.php:79-94`)
  on a **GET**: deactivates all active non-Mollie transactions of the order, creates and executes a
  new `mollie_admin_link` purchase transaction (Mollie `POST /v2/payments`), and redirects to the
  Mollie checkout URL. Order ids are sequential integers, so today anyone can trigger this for any
  order and obtain its checkout page (which shows amount and merchant). On exception it redirects to
  `oro_order_frontend_view`. The admin sends this link to the shopper (`MolliePaymentLinkProvider::getSiteSpecificPaymentLink`,
  `Manager/MolliePaymentLinkProvider.php:197-215`); it is also rebuilt in the failure flash of
  `AdminLinkPaymentRedirectListener` (`EventListener/Callback/AdminLinkPaymentRedirectListener.php:92-104`).
- **`getActivePaymentMethods`** runs `PaymentMethodService::getEnabledMethodsWithTempProfileId($profileId)`
  in the context of an arbitrary `channelId` (Mollie `GET /v2/methods?profileId=`) and returns the
  method list. Called by `Resources/public/js/app/views/mollie-payments-refresher.js:66-69` (GET).

## 2. Platform behaviour (verified)

1. **ACL is read only from native attributes.** `Oro\Component\PhpUtils\Attribute\Reader\AttributeReader`
   L31-44 uses `ReflectionClass/ReflectionMethod::getAttributes()`; `SecurityBundle/Attribute/Loader/AclAttributeLoader.php`
   L90-120 binds `Acl` or `AclAncestor` per class/method. A `#[…]` inside `/** */` is comment text.
2. **No ACL means allowed.** `SecurityBundle/Authorization/ClassAuthorizationChecker.php` L38 starts
   with `$isGranted = true` and checks only when an attribute is bound. Enforcement:
   `SecurityBundle/EventListener/ControllerListener.php` L59-61 throws `AccessDeniedException` on the
   main request (`kernel.controller`, priority -1).
3. **ACL metadata is cached** in `%kernel.cache_dir%/oro/acl_attributes.php`; prod needs a cache clear
   after the fix (`SecurityBundle/Resources/config/services.yml` L282-290).
4. **Attribute signatures** (6.0.0): `Acl(...$arguments)` accepts named `id, type, ignoreClassAcl, class,
   permission, groupName, label, description, category, validate`; **unknown names are silently
   ignored** (`Attribute/Acl.php` L13-37). `AclAncestor(?string $id)`. `CsrfProtection(bool $enabled = true,
   bool $useRequest = false)`. Oro's own usage: `IntegrationBundle/Controller/IntegrationController.php`
   L33 `#[Acl(id: 'oro_integration_view', type: 'entity', class: Integration::class, permission: 'VIEW')]`,
   L51 `oro_integration_update` with `EDIT`, L57-64 `#[AclAncestor('oro_integration_update')] #[CsrfProtection()]`.
5. **Redeclaring an existing ACL id silently overwrites it** (`AclAttributeStorage::add()` L142-148). Our
   `SupportController` declares `#[Acl(id: 'oro_integration_view' …)]`, which Oro's IntegrationBundle
   already defines; `#[AclAncestor('oro_integration_view')]` is the correct way to reuse it.
6. **CSRF.** `SecurityBundle/EventListener/CsrfProtectionRequestListener.php` L34-60 (`kernel.controller`,
   priority -10) checks requests whose `_csrf_protection` attribute is set (by Sensio's
   `ControllerListener` from `#[CsrfProtection]`, SensioFrameworkExtraBundle 6.2.6 L63-102). It
   compares header `X-CSRF-Header` with cookie `_csrf` (`Csrf/CsrfRequestManager.php` L14-35) and
   throws a 403 on mismatch, independent of the HTTP method or `X-Requested-With`.
7. **Oro's JS adds the CSRF header automatically** to same-origin jQuery ajax calls
   (`UIBundle/Resources/public/js/extend/jquery.js` L14-29, `$.ajaxPrefilter`). The connection checker
   uses `$.ajax` POST (`IntegrationBundle/Resources/public/js/app/components/integration-connection-checker-component.js`
   L63-84), so restoring `#[CsrfProtection()]` does not break the "Verify" button.
8. **Object-level ACL through the param converter.** `SecurityBundle/Request/ParamConverter/DoctrineParamConverter.php`
   (decorates `sensio_framework_extra.converter.doctrine.orm`, services.yml L660-665) L63-80 checks
   the action's ACL permission on the converted object when the ACL has `permission` + `class` and the
   object is an instance of that class (`Authorization/RequestAuthorizationChecker.php` L41-66;
   `AclAncestor` resolves to the ancestor's definition). So:
   - `#[AclAncestor('oro_order_frontend_view')]` + `@ParamConverter("order", class=Order)` → `VIEW` on that specific order.
   - `#[AclAncestor('oro_integration_update')]` + `Channel $channel` → `EDIT` on that channel.
   - Sensio FrameworkExtraBundle stays supported (`oro/platform` `composer.json` L94 `~6.2.6`, 6.0.0 and 6.1.0).
9. **`oro_order_frontend_view`** (orocommerce 6.0.0 `OrderBundle/Controller/Frontend/OrderController.php`
   L27-33): entity ACL on `Order`, `VIEW`, group `commerce`. Order frontend ownership is `customerUser` /
   `customer` (`OrderBundle/Entity/Order.php` L75-79). Default roles (`OrderBundle/Migrations/Data/ORM/data/frontend_roles.yml`):
   BUYER `VIEW_BASIC`, ADMINISTRATOR `VIEW_DEEP`, **ANONYMOUS has no Order permission**. Guests are
   evaluated with the website's guest role (customer-portal 6.0.0 `CustomerBundle/Security/AnonymousCustomerUserRolesProvider.php`
   L29-44, `CustomerVisitorAclVoterDecorator.php` L41-58), so **a guest is denied** with default roles.
10. **Prefix and firewalls.** Non-`frontend` routes get `%web_backend_prefix%` (customer-portal 6.0.0
    `FrontendBundle/EventListener/RouteCollectionListener.php` L31-38; documented example value `/admin`).
    `^%web_backend_prefix%` requires `IS_AUTHENTICATED` (platform `SecurityBundle/Resources/config/oro/app.yml`
    L97). The storefront firewall `frontend` (`^/`) allows `anonymous_customer_user` (customer-portal
    `CustomerBundle/Resources/config/oro/app.yml` L45-67). So: back-office routes → any logged-in
    back-office user, regardless of role; `frontend: true` routes → anyone, including guests.
11. **Oro's own validate-connection actions** (orocommerce 6.0.0/6.1.0 `UPSBundle/Controller/AjaxUPSController.php`
    L60-63, `FedexShippingBundle/Controller/ValidateConnectionController.php` L30-33) use `POST` +
    `#[ParamConverter]` + `#[CsrfProtection()]` and **no** ACL attribute. Adding `#[AclAncestor('oro_integration_update')]`
    to ours is stricter than Oro's pattern (and is what our `5.0` code declares).

## 3. Impact assessment (master)

| Endpoint | Who can reach it today | Impact |
|---|---|---|
| download debug data | any back-office user (any role) | High in principle (phpinfo + all app logs + order references); on Oro 6 the action likely crashes on `$this->get()` first (not verified live) |
| support status POST | any back-office user; CSRF-able from a foreign site against a logged-in admin | Low–medium: toggles global debug logging and webhook URL replacement |
| support status GET | any back-office user | Low |
| validate connection | any back-office user; CSRF-able | Low: no persistence; outbound token check to Mollie |
| payment link | **anyone, unauthenticated** | Medium–high: state change on GET for any order id (deactivates non-Mollie transactions, creates Mollie payments), exposes checkout page of foreign orders |
| active methods (not reported) | any back-office user | Low: method list per channel/profile |

## 4. What the change will affect

- `Controller/SupportController.php`, `Controller/AjaxMollieController.php`,
  `Controller/PaymentLinkController.php`, `Controller/ActivePaymentMethodsController.php`.
- `Resources/config/services.yml` (inject `DebugService` into `SupportController` instead of `$this->get()`).
- Possibly `Manager/AdminLinkVisitHandler.php` / `Manager/MolliePaymentLinkProvider.php` if the payment
  link gets a token (decision in spec).
- `DESIGN.md` (§ routes/back-office endpoints, §5.7 admin payment link, the "Existing deviations" note
  about `$this->get(DebugService::class)`), `LEARNINGS.md` (the trap), possibly a guard against
  recurrence (CI grep or hook).
- Translations only if a new user-facing message is added.
- No schema, no core (`IntegrationCore/`) change.

## 5. Risks

- **Locking out guest shoppers from the admin payment link.** Restoring `AclAncestor('oro_order_frontend_view')`
  as on `5.0` denies guests (default ANONYMOUS role) and requires the shopper to be logged in as the
  order's customer user (BUYER: own orders only; ADMINISTRATOR: the customer's orders). Admin-created
  orders for guest customers, or links opened in a browser without a session, would stop working.
  What the storefront does for an anonymous denied request (login redirect vs. 403) is **not verified**.
- **Behaviour change for merchants' support flow.** The debug endpoints have no UI caller; whoever uses
  them (Mollie support, merchants) needs the right role after the fix, and a POST with CSRF header.
- **Using `#[Acl(…)]` with Doctrine-style syntax** (`id="…"`) is a parse error in PHP attributes; using
  wrong named arguments (`group_name`) is silently ignored. Use `AclAncestor` with existing ids.
- **Prod cache**: ACL attributes take effect only after a cache clear (`oro:platform:update` already does it).
- **5.0 port**: only `ActivePaymentMethodsController` (missing ACL) and possibly payment-link hardening
  apply; the attribute fix itself does not.

## 6. Side findings (outside the report)

- `AdminLinkPaymentRedirectListener::redirectToFailureUrl()` uses `$this->session` (`:98`), but on
  `master` the class only has `$requestStack` (`:24`, `:46`; the success path uses
  `$this->requestStack->getSession()`). On `master` the admin-link failure path would hit an undefined
  property → `Error`. `5.0` has the `$session` property. Not verified at runtime.
- `ActivePaymentMethodsController` logs the raw `profileId` route parameter (`:59`).

## 7. Questions for the spec

1. **Payment link access model.** Options: (a) restore `AclAncestor('oro_order_frontend_view')` as on
   `5.0` (ownership via param converter, guests locked out); (b) unguessable signed token in the link
   (e.g. HMAC of order id, or a random token stored with `PaymentLinkMethod`), works for guests,
   requires regenerating links already sent; (c) both: ACL for logged-in users, token for guests.
   Should the state change also move off GET (e.g. an intermediate page), or stay as is?
2. **Debug endpoints.** Keep them (fix ACL + `DebugService` injection), or remove them, since no UI uses
   them? If kept: which ACL for the POST toggle (`oro_integration_update` vs. `oro_integration_view`),
   and should the ZIP keep `phpinfo()` and the whole `%kernel.logs_dir%`, or be reduced to the
   `mollie` log channel?
3. **Validate connection.** `AclAncestor('oro_integration_update')` + `CsrfProtection` (as our `5.0`) or
   CSRF only (as Oro's UPS/FedEx)?
4. **Active methods endpoint.** Add `AclAncestor('oro_integration_view')` (or `_update`) and validate
   `channelId` (int) / `profileId` (`pfl_…` pattern) in the route requirements?
5. **Recurrence guard.** Add a check (CI step, `.claude` hook, or only the existing rule + `LEARNINGS.md`)
   that fails on `#[Acl`, `#[AclAncestor`, `#[CsrfProtection` inside docblocks?
6. **Branch scope.** `master` only, or also port the active-methods ACL / payment-link hardening to `5.0`?
7. **Side finding** in `AdminLinkPaymentRedirectListener` (`$this->session`): fix in this ticket or separately?
8. **Release.** Ship as a patch release (`6.2.4`, and `5.3.4` if ported) in this ticket?
9. Delegation of implementation to subagents (default yes).
