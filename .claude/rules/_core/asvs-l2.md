# OWASP ASVS v5.0 — Level 2 controls

These are the verification requirements that **must hold** for every change shipped from this repository. They are distilled from OWASP ASVS v5.0 for a payment bundle that runs inside merchants' OroCommerce applications. A change that cannot satisfy an applicable item does not get committed.

This bundle handles payment flows, the merchant's Mollie organization access token and shopper PII (names, addresses, emails sent to Mollie). Level 2 is the minimum. Card data never touches the shop, because it is tokenised by Mollie Components in the browser. Keep it that way.

The "Enforcement here" column maps each control to this codebase. PHP, Twig and route/service configuration details are in `.claude/rules/languages/php/CLAUDE.md`, browser JS details in `.claude/rules/languages/javascript/CLAUDE.md`. The copied core (`IntegrationCore/`) is out of scope for edits (`CLAUDE.md` § Local rules); a control the core violates is fixed in `mollie/integration-core` or compensated in the wrapper.

---

## V1 — Encoding and Sanitization

| ID | Requirement | Enforcement here |
|---|---|---|
| 1.1 | Output encoding chosen for the **sink context** (HTML, attribute, JS, CSS, URL). | Templates in `Resources/views/**/*.html.twig` rely on Twig autoescaping (`html`). Use `|e('js')` / `|e('html_attr')` for other contexts, `|json_encode` inside an autoescaped `data-page-component-options` attribute for JS data, and `path()` / `url()` for URLs. `|raw` only for markup this bundle rendered itself from a template, never for entity, request or Mollie data; `|trans` is not escaping (table in `languages/php/CLAUDE.md` § Output Encoding). No custom escaping helpers. |
| 1.2 | All untrusted input is treated as untrusted across **all** sinks (SQL/DQL, OS commands, templates, file paths). | `Request` values (query, body, JSON content, route parameters), `UploadedFile`, datagrid parameters, the webhook request and Mollie API payloads are untrusted until validated. |
| 1.3 | SQL uses parameter binding or query-builder quoting. | Doctrine ORM / QueryBuilder with `setParameter()`; schema through Oro migrations. **Never** concatenate request data into SQL or DQL. Oro order and payment state changes go through Oro services (`PaymentTransactionProvider`, the payment method `execute()`), never through direct SQL. |
| 1.4 | OS commands are never built from input. | No `exec`, `shell_exec`, `system`, `passthru`, `proc_open` or backticks in bundle code. |

## V2 — Validation and Business Logic

| ID | Requirement | Enforcement here |
|---|---|---|
| 2.1 | Server-side allow-list validation for every untrusted field. | Admin input goes through Symfony form types (`Form/Type/`) with constraints. Route parameters have `requirements`. IDs are integers `> 0` (or loaded by the param converter), amounts are validated as positive decimals within the Mollie-side limits, and enum-like values (Mollie method ids, capture modes, surcharge types, API methods) are checked against an allow-list. Browser JS validation is never enough. |
| 2.2 | Malformed payloads are rejected with a generic error; input is not echoed. | Controllers return a generic `JsonResponse` error (400/422) and log the details. The webhook answers with an HTTP status only. |
| 2.3 | Business invariants are enforced server-side. | Amounts sent to Mollie come from the Oro `PaymentTransaction`, order and checkout, never from the browser. Capture and refund amounts are checked against the Mollie payment (remaining authorized amount, captured or paid minus `amountRefunded`). Order status transitions go through `IntegrationServices/OrderTransitionService`. |
| 2.4 | Numeric values are formatted locale-independently. | Money goes to Mollie as a string with a `.` separator, two decimals and no thousands separator (core `Amount` DTO). |

## V3 — Web Frontend Security

| ID | Requirement | Enforcement here |
|---|---|---|
| 3.1 | Third-party scripts only from required, known origins. | The only external script is `https://js.mollie.com/v1/mollie.js` (Mollie Components), loaded by `mollie-credit-card.js`. Adding another origin is a design decision and needs an ADR in `DESIGN.md`. |
| 3.2 | No secrets in browser code. | The organization access token never reaches templates, page-component options or JS. Only the profile ID, locale and test-mode flag go to Mollie Components. |
| 3.3 | Response headers (CSP, HSTS, nosniff) | These are owned by the merchant's Oro application or server, not by this bundle. Don't break a strict CSP: no `eval`, no `new Function`, no inline scripts; register JS as page components through `jsmodules.yml`. |

## V4 — API and Web Service

| ID | Requirement | Enforcement here |
|---|---|---|
| 4.1 | Every state-changing endpoint accepts only the intended HTTP method. | State changes (connection validation, debug-mode toggle, payment-link creation, capture, refund, channel save with logo upload) accept POST only, or run as Oro operations/forms that already require it. |
| 4.2 | Admin endpoints are behind Oro backoffice authentication and authorization. | Backoffice routes (no `frontend: true` in `Resources/config/oro/routing.yml`) carry a real `#[Acl]` / `#[AclAncestor]` attribute on `master` (Oro 6) or an `@Acl` / `@AclAncestor` annotation on `5.0` (Oro 5). **An attribute written inside a docblock is not read** (`languages/php/CLAUDE.md` § Access Control). A new or changed order operation in `Resources/config/oro/actions.yml` declares an `acl_resource` (the existing operations declare none; check in the Oro ActionBundle source of the branch's version what applies without one). Never expose admin functionality through a storefront route. |
| 4.3 | State-changing requests are protected against CSRF. | `#[CsrfProtection()]` (`@CsrfProtection()` on `5.0`) on every state-changing controller action; Oro checks the `_csrf` double-submit cookie against the `X-CSRF-Header` header (`Oro\Bundle\SecurityBundle\Csrf\CsrfRequestManager`, platform 6.0.0). Symfony forms keep their form CSRF token. |
| 4.4 | Inbound webhooks are authenticated. | Mollie webhooks carry only an `id`. They arrive on Oro's `oro_payment_callback_notify` route, which requires the payment transaction's access identifier and access token. Authenticity of the data comes from fetching the resource back from the Mollie API with the channel's token (core `WebHookTransformer`); the request body is never trusted. The handler must stay idempotent, because Mollie retries on non-2xx responses. |
| 4.5 | Public (storefront) endpoints that act on an order verify ownership. | The redirect return (`mollie_payment_callback_return`) is keyed by the unguessable transaction `accessIdentifier`. Any storefront route that takes an order id (`mollie_payment_link`) checks that the order belongs to the current customer user (`isGranted('VIEW', $order)` / `oro_order_frontend_view`) or requires an unguessable signed token. |
| 4.6 | Listeners act only for this bundle's own payment methods and orders. | Oro dispatches `oro_payment.callback.*` events and Doctrine entity events for every payment method. Each listener first checks that the transaction's payment method is a Mollie one (`MolliePaymentProvider::hasPaymentMethod()`, `OroPaymentMethodUtility::hasMolliePaymentConfig()`) and returns otherwise. |

## V5 — File Handling

| ID | Requirement | Enforcement here |
|---|---|---|
| 5.1 | Uploaded file type and size are validated server-side (extension allow-list + detected MIME type). | The payment-method logo (`PaymentMethodSettingsType` `image` field, `Image` constraint, `FileUploader`). Give the `Image` constraint an explicit `mimeTypes` allow-list of raster types. |
| 5.2 | Uploaded files are never executable or active content. | Allow raster image types only. SVG is active content (script) and must not be stored as-is under `public/`. |
| 5.3 | User-supplied file names are never used as filesystem paths. | The target name is generated server-side in `FileUploader::upload()` under `public/bundles/molliepayment/img/uploads`. Never use `getClientOriginalName()` in a path. |

## V6/V7 — Authentication and Session Management

| ID | Requirement | Enforcement here |
|---|---|---|
| 6.1 | This bundle adds no authentication of its own. | Backoffice user, customer user and guest authentication belong to Oro. Use Oro's token storage and the checkout/order context; never build a parallel login. |
| 7.1 | Session data is minimal and single-use where possible. | The bundle uses the session only for flash messages. The card token travels only in the checkout form (`oro_workflow_transition[<method>-mollie-card-token]`). Never store the API token, card tokens or full payment payloads in the session. |

## V8 — Authorization

| ID | Requirement | Enforcement here |
|---|---|---|
| 8.1 | Authorization decisions are made server-side, on the resource being accessed. | Controller actions declare ACL (4.2); storefront endpoints that take an order id check ownership (4.5); repository reads of Oro entities for a user use `AclHelper` where Oro does (`ChannelSettingsRepository::getEnabledSettings()`). |
| 8.2 | No client-supplied identity or amount is trusted. | Customer identity comes from Oro, amounts from the transaction, the order or Mollie. |
| 8.3 | Default deny. | A new route without an ACL declaration is not allowed; an unknown operation or method id is a no-op, never a state change. |

## V11 — Cryptography

| ID | Requirement | Enforcement here |
|---|---|---|
| 11.1 | Tokens that protect a resource are unguessable. | Use Oro's payment transaction `accessIdentifier` / `accessToken`, or `hash_hmac('sha256', …, <secret>)` / `random_bytes()`. Never plain `md5`/`sha1` of IDs, and never `uniqid()`. |
| 11.2 | Token comparison is constant-time. | `hash_equals()`. |
| 11.3 | Security randomness comes from a CSPRNG. | `random_bytes()` / `random_int()`. Never `rand`, `mt_rand` or `uniqid` for security. |
| 11.4 | Stored secrets are protected with a vetted primitive. | Use Oro's own crypter (`oro_security.encoder.default`, `SymmetricCrypterInterface`) if a secret is to be encrypted; check its API in the Oro source first. Don't add a custom scheme. |

## V12 — Secure Communication

| ID | Requirement | Enforcement here |
|---|---|---|
| 12.1 | Outbound calls use TLS with certificate verification on. | All Mollie calls go through the core `Proxy` + `CurlHttpClient` over `https://api.mollie.com/v2/`. Never disable `CURLOPT_SSL_VERIFYPEER` / `VERIFYHOST`. |
| 12.2 | Outbound destinations are fixed. | No request-controlled URLs for server-side HTTP calls (SSRF). The outbound hosts are the Mollie API and the fixed version-check URL. |

## V13 — Configuration

| ID | Requirement | Enforcement here |
|---|---|---|
| 13.1 | No secrets in source control. | Organization access tokens live only in the merchant's database. No tokens in code, fixtures, test data, docs or commits, and that includes test tokens. |
| 13.2 | Debug output is off by default. | `debugModeEnabled` defaults to off. Debug data (logs, `phpinfo`, order references) is available only to authorized backoffice users. |
| 13.3 | Dependencies are pinned and reviewed. | The core is a copy of a released `mollie/integration-core` version, re-namespaced. A re-copy is reviewed as a diff of `IntegrationCore/`. The bundle adds no Composer dependency besides `oro/commerce`. |

## V14 — Data Protection

| ID | Requirement | Enforcement here |
|---|---|---|
| 14.1 | No secrets or PII in logs. | `Logger::log*()` (→ `LoggerAdapter` → `LoggerService`, Monolog channel `mollie`) never receives the API token, the `Authorization` header, card tokens, or request/response bodies with addresses or emails. Log IDs (`tr_…`, Oro order id) instead. |
| 14.2 | Only the data Mollie needs is sent. | `MollieDtoMapper` sends what the Payments API requires (lines, amounts, addresses for the methods that need them). Don't add fields "just in case". |
| 14.3 | Stored credentials are protected. | Treat the `authToken` configuration rows as secret. Never render the stored token back in full in a form, a `JsonResponse`, the debug data or logs. |

---

## Verification checklist applied to every change

Before declaring a change done, the implementer (human or AI) walks through:

1. **Input**: every untrusted field (request, uploaded file, datagrid parameter, webhook, Mollie payload) is validated or cast server-side.
2. **Sinks**: SQL/DQL goes through Doctrine with bound parameters, Twig output is autoescaped (no `|raw` on data), and files are validated.
3. **AuthZ**: every controller action has a real ACL attribute (annotation on `5.0`), storefront endpoints check order ownership, state changes are POST with CSRF protection, and every listener checks that it handles a Mollie method.
4. **Crypto / secrets**: there are no tokens in source, access tokens are HMAC/CSPRNG-based, and comparisons are constant-time.
5. **Logging**: there are no API tokens, PII or card tokens in the log.
6. **Webhook**: the resource is fetched from Mollie, the handler is idempotent, and it returns the correct HTTP status for retries.
7. **Errors**: messages shown to shoppers and admins are generic and translated, with no stack traces or raw API errors in storefront output.

A change that cannot answer "yes" to every applicable item is not ready for review.
