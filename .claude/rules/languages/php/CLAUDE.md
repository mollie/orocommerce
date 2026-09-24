---
paths:
  - "*.php"
  - "Condition/**/*.php"
  - "Controller/**/*.php"
  - "DependencyInjection/**/*.php"
  - "Entity/**/*.php"
  - "EventListener/**/*.php"
  - "Exceptions/**/*.php"
  - "Form/**/*.php"
  - "Integration/**/*.php"
  - "IntegrationServices/**/*.php"
  - "Manager/**/*.php"
  - "Mapper/**/*.php"
  - "Migrations/**/*.php"
  - "PaymentMethod/**/*.php"
  - "Provider/**/*.php"
  - "Resources/views/**/*.twig"
  - "Resources/config/**/*.yml"
---

# PHP Security Rules (OroCommerce bundle)

Security rules for the PHP code, Twig templates and service/route configuration of the
`MolliePaymentBundle`. The copied core (`IntegrationCore/`) and its mirrored tests (`Tests/Unit/`) are
out of scope, because the core is never edited in place (`CLAUDE.md` § Local rules).

## Prerequisites

- `.claude/rules/_core/owasp-2025.md`: core web security
- `.claude/rules/_core/asvs-l2.md`: the verification standard and checklist for this repo

Every rule has to work on the **PHP 8.1 floor** of the shared code (see `CLAUDE.md` § Local rules)
and on both maintained branches (Oro 6 on `master`, Oro 5 on `5.0`; `CLAUDE.md` § Supported branches).

---

## Access Control and CSRF

### Rule: Every controller action declares its ACL and, when it changes state, its CSRF protection, as a real attribute

**Level**: `strict`

**When**: Adding or changing an action in `Controller/`, or a route in `Resources/config/oro/routing.yml`.

**Do** (`master`, Oro 6: PHP attributes outside the docblock):
```php
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;

/**
 * @Route("/validate-connection/{channelId}/", name="mollie_payment_validate_connection", methods={"POST"})
 */
#[AclAncestor('oro_integration_update')]
#[CsrfProtection()]
public function validateConnectionAction(Request $request, ?Channel $channel = null): JsonResponse
```

On `5.0` (Oro 5) the same controls are Doctrine annotations **inside** the docblock:
`@AclAncestor("oro_integration_update")`, `@CsrfProtection()` (`Oro\Bundle\SecurityBundle\Annotation\…`).

**Don't**:
```php
/**
 * @Route(...)
 * #[AclAncestor("oro_integration_update")]   // inside a comment: never read, no ACL check at all
 * #[CsrfProtection()]                         // inside a comment: no CSRF check
 */
```

**Why**: Oro 6 reads ACL through native `ReflectionMethod::getAttributes()`
(`Oro\Component\PhpUtils\Attribute\Reader\AttributeReader::getMethodAttributes()`, used by
`Oro\Bundle\SecurityBundle\Attribute\Loader\AclAttributeLoader`, platform 6.0.0). A `#[…]` inside
`/** */` is plain comment text. `#[CsrfProtection]` is what makes
`CsrfProtectionRequestListener` check the `_csrf` double-submit cookie against the `X-CSRF-Header`
header (`Oro\Bundle\SecurityBundle\Csrf\CsrfRequestManager`, platform 6.0.0). Backoffice routes are
behind the Oro login, but without an ACL any backoffice user reaches them. Storefront routes
(`options: frontend: true`) are reachable by customer users and, depending on the firewall, by guests.

Further:
- State changes accept POST only (`methods={"POST"}`) and carry `#[CsrfProtection()]`.
- A storefront action that takes an order id also checks that the order belongs to the current
  customer user (`#[AclAncestor('oro_order_frontend_view')]` plus the entity-level check through
  `isGranted('VIEW', $order)`), or requires an unguessable token (the payment transaction
  `accessIdentifier`, as in `CallbackController`).
- Check a new ACL id or attribute against the Oro source of the supported version before using it
  (`CLAUDE.md` § Official references).

**Refs**: OWASP A01:2025, CWE-284, CWE-352, CWE-639, ASVS 4.2, 4.3, 4.5, 8.1

---

## Database Access

### Rule: Use Doctrine (ORM / QueryBuilder) with bound parameters

**Level**: `strict`

**When**: Any read or write to the database: the Mollie entity repositories in `Entity/Repository/`,
Oro entities through `DoctrineHelper` / the entity manager, and migrations in `Migrations/`.

**Do**:
```php
$qb = $this->entityManager->createQueryBuilder()
    ->select('e')
    ->from(MollieBaseEntity::class, 'e')
    ->where('e.type = :type')
    ->setParameter('type', $type);
```
Schema changes go through Oro migrations (`Migrations/Schema/vX_Y/*` implementing `Migration`, plus
the `MolliePaymentBundleInstaller` for fresh installs). Data fixtures go into `Migrations/Data/ORM/`.

**Don't**:
```php
$conn->executeQuery("SELECT * FROM mollie_entity WHERE type = '" . $request->get('type') . "'");
$qb->where("e.index_1 = '$value'");   // value concatenated into DQL
```

**Why**: Request values concatenated into SQL or DQL are injection. Oro order and payment state
changes go through the Oro services (`PaymentTransactionProvider`, the payment method's `execute()`,
workflow/order services), never through direct SQL, so Oro's own listeners run.

**Refs**: OWASP A05:2025, CWE-89, ASVS 1.3

---

## Input Handling

### Rule: Validate request input at the entry point with Symfony forms/constraints or explicit allow-lists

**Level**: `strict`

**When**: Reading `Request` (query, body, JSON content, files, route parameters), the webhook request,
or data returned by the Mollie API.

**Do**:
```php
$data = json_decode($request->getContent(), true);
if (!isset($data['debugStatus']) || !is_bool($data['debugStatus'])) {
    return new JsonResponse(['success' => false], 400);
}
$captureMode = in_array($value, ['automatic', 'manual'], true) ? $value : null;   // allow-list
```
Admin forms use Symfony form types (`Form/Type/`) with constraints (`NotBlank`, `Choice`, `Range`,
`Image`, …). Route parameters use `requirements` (as `accessIdentifier` in `CallbackController`).

**Don't**:
```php
$profileId = $request->get('profileId');               // used as-is in an API call or a log line
$config->setValue($request->request->all('settings')); // stored without validation
```

**Why**: Controllers, form subscribers and event listeners are the trust boundary. Everything below
them assumes validated types.

**Refs**: OWASP A05:2025, CWE-20, ASVS 2.1

---

## Output Encoding

### Rule: Let Twig autoescape; never use `|raw` on data; no custom escaping helpers

**Level**: `strict`

**When**: Rendering in `Resources/views/**/*.twig`, building HTML or JS config in PHP, or returning
messages in a `JsonResponse` that the JS puts into the page.

**Order of preference**: (1) Twig with its default autoescaping and filters, (2) Symfony/Oro helpers
(form themes, `|trans`, `|json_encode` in an autoescaped attribute, `path()` / `url()`), and (3) plain PHP
(`htmlspecialchars`, `json_encode`) only where neither exists. Don't write custom escaping helpers.

| Need | Helper | Escapes? |
|---|---|---|
| Text / attribute value | `{{ value }}` in a `.html.twig` template (autoescape `html`) | Yes |
| Attribute in a JS context | `{{ value|e('js') }}` / `{{ value|e('html_attr') }}` | Yes, for that context |
| Page-component options | `data-page-component-options="{{ options|json_encode }}"` (as in `Form/fields.html.twig`) | Yes: autoescaping HTML-encodes the JSON inside the attribute |
| Translation | `{{ 'key'|trans }}`, `{{ 'key'|trans({'{name}': name}) }}` | The result is autoescaped when printed; `|trans|raw` is **not** |
| Pre-rendered markup | `{{ html|raw }}` | **No**. Allowed only for markup this bundle rendered itself from a template (as the sub-form contents in `Form/fields.html.twig`), never for request, entity or Mollie data |
| URL | `{{ path('route', {...}) }}` / `url()` | Yes (route generation encodes parameters) |

**Don't**:
```twig
{{ order.customerNotes|raw }}                 {# unescaped entity data #}
{{ mollieErrorMessage|trans|raw }}             {# translation is not escaping #}
<script>var profileId = '{{ profileId }}';</script>   {# HTML escaping inside a JS string #}
```

**Why**: Order data, method names, image paths and Mollie error messages end up in backoffice and
storefront pages, so they are XSS vectors. Twig's autoescaping covers them unless it is bypassed.

**Refs**: OWASP A05:2025, CWE-79, ASVS 1.1

---

## Webhook

### Rule: Trust only what is fetched back from Mollie; stay idempotent; map errors to a retry status

**Level**: `strict`

**When**: Changing `EventListener/Callback/PaymentCheckoutCallbackListener` (`oro_payment.callback.notify`),
`IntegrationServices/OrderTransitionService`, or anything the core `WebHookTransformer` calls back.

**Do**:
- Read only the `id` from the request and let the core `WebHookTransformer` fetch the payment.
- Keep status updates safe to run more than once (Mollie retries on non-2xx). Skip unchanged
  statuses.
- Catch `\Throwable` in the listener and answer with an HTTP status only: success, or a retryable
  error. Never let an exception render an Oro error page to Mollie.

**Don't**:
- Read status, amount or metadata from the webhook body.
- Perform side effects (emails, inventory, transitions) without checking whether they already happened.

**Refs**: OWASP A08:2025, CWE-345, ASVS 4.4

---

## File Uploads

### Rule: Allow-list raster image types and size; generate the file name server-side

**Level**: `strict`

**When**: Handling `UploadedFile` (payment-method image upload: `Form/Type/PaymentMethodSettingsType`
`image` field, `EventListener/ChannelSettingsListener::uploadImage()`, `IntegrationServices/FileUploader`).

**Do**:
```php
new Image([
    'maxSize' => '2M',
    'mimeTypes' => ['image/png', 'image/jpeg', 'image/gif'],   // explicit raster allow-list
]);
$fileName = $prefix . '-' . bin2hex(random_bytes(8)) . '.' . $file->guessExtension();  // server-side name
```

**Don't**:
- Accept `image/svg+xml` (active content) for a file served from `public/`.
- Use `getClientOriginalName()` or the client extension as the stored path.

**Refs**: OWASP A06:2025, CWE-434, ASVS 5.1–5.3

---

## Secrets and Tokens

### Rule: No tokens in code, HTML or logs; tokens are HMAC/CSPRNG with constant-time comparison

**Level**: `strict`

**When**: Handling the Mollie organization API token, building links or tokens, or logging.

**Do**:
- Read and write the token only through the channel settings / core `Configuration`
  (`IntegrationServices/ConfigurationService`). Never log it, and never render it back in full in a
  form or a `JsonResponse`.
- Build tokens with `hash_hmac('sha256', $data, $secret)` or `random_bytes()`, and compare with
  `hash_equals()`.
- Log Mollie IDs (`tr_…`) and Oro order IDs, not payloads with addresses or emails.

**Don't**:
- `md5($orderId . $customerId)` or `uniqid()` as an access token.
- `rand()`, `mt_rand()` or `uniqid()` for anything security-relevant.
- `Logger::logDebug(...)` / `logError(...)` with full API requests/responses, headers or card tokens.

**Refs**: OWASP A04:2025, A09:2025, CWE-330, CWE-532, ASVS 11.1–11.4, 14.1, 14.3

---

## Dangerous Functions

### Rule: No dynamic code execution or unsafe deserialization

**Level**: `strict`

**When**: Always.

**Don't**: `eval`, `assert` with strings, `preg_replace` with `/e`, `exec` / `shell_exec` / `system` /
`passthru` / backticks, or `unserialize` on anything not written by this bundle (use `json_decode`).
Never pass a variable to `include` / `require`. Never instantiate a class or resolve a service whose
name comes from request data.

**Refs**: OWASP A05:2025, CWE-94, CWE-502

---

## Error Handling

### Rule: Catch at entry points; show generic messages

**Level**: `warning`

**When**: Controllers, event listeners (callback, Doctrine entity listeners, form subscribers),
payment method `execute()` actions, migrations.

**Do**: At the entry point, catch `\Throwable`, log the details through the core `Logger`
(→ `IntegrationServices/LoggerAdapter`, the core `ShopLoggerAdapter`), and show a generic, translated message (flash bag,
`JsonResponse`, payment result `errorUrl`).

**Don't**: Let exceptions reach Oro's error page, print raw Mollie API error bodies to customers, or
throw from a Doctrine listener in a way that aborts an unrelated Oro flush without a message.

**Refs**: OWASP A10:2025, CWE-209
