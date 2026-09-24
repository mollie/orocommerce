---
paths:
  - "Resources/public/js/**/*.js"
  - "Resources/views/layouts/**/*.yml"
  - "Resources/config/jsmodules.yml"
---

# JavaScript Security Rules (browser code of the OroCommerce bundle)

These rules cover the bundle's **browser-only** JavaScript. There is no server-side JS and no build
step in this repo: the files are Oro page components and views, registered as dynamic imports and
built by the shop's own Oro asset build (`oro:assets:build`).
- Backoffice (`Resources/config/jsmodules.yml`): `mollie-payment-link-generator`, `mollie-form-refresher`,
  `mollie-accordion-view`, `mollie-payments-refresher`, `mollie-tabs-view` (integration channel
  settings form, payment-link action).
- Storefront (`Resources/views/layouts/default/config/jsmodules.yml`): `mollie-applepay`,
  `mollie-payment-method-component`, `mollie-issuer`, `mollie-order-submit`, `mollie-credit-card`
  (checkout: method options, issuers, Mollie Components card fields, order submit).

## Prerequisites

- `.claude/rules/_core/owasp-2025.md`: core web security
- `.claude/rules/_core/asvs-l2.md`: the verification standard and checklist for this repo
- `.claude/rules/languages/php/CLAUDE.md`: the server side of every endpoint these scripts call

---

## DOM Output

### Rule: Put data into the DOM as text; never as HTML

**Level**: `strict`

**When**: Rendering anything that comes from an AJAX response, a `data-*` attribute, page-component
options, a form field or the URL. That includes messages, method names, issuer names, amounts and
Mollie error texts.

**Do**:
```javascript
const $option = $('<option/>').val(issuer.id).text(issuer.name);   // text, never parsed as HTML
$select.append($option);
$message.text(response.message);
```
Build selectors from data with `$.escapeSelector()` or filter with `.filter()` instead of
concatenating values into selector strings.

**Don't**:
```javascript
$message.html(response.message);                                // XSS if the text contains markup
$select.append('<option>' + issuer.name + '</option>');
$('.mollie-payment-method[data-payment-method-id="' + identifier + '"]');  // unescaped selector input
```

**Why**: Messages and names can come from the Mollie API or from admin input. In the backoffice, an
XSS runs with the administrator's session.

`.html()` / `innerHTML` is allowed only to clear an element, to insert markup the server rendered for
this purpose (a Twig-rendered form fragment returned by the bundle's own endpoint), or for static
markup written in the script. Say so in a comment where it's used.

**Refs**: OWASP A05:2025, CWE-79, ASVS 1.1

---

## Code Execution

### Rule: No dynamic code

**Level**: `strict`

**When**: Always.

**Don't**: `eval`, `new Function`, `setTimeout` / `setInterval` with a string argument, `document.write`,
or `javascript:` URLs.

**Why**: These break a strict Content-Security-Policy in the merchant's shop and turn any injected
string into code.

**Refs**: OWASP A05:2025, CWE-95

---

## AJAX Requests

### Rule: Server-provided URLs, Oro's CSRF header, and the right application

**Level**: `strict`

**When**: Calling any bundle route (`/mollie/…`) or an Oro endpoint.

**Do**:
- Take endpoint URLs from what the server rendered (page-component options, form `action`,
  `data-*` attributes built with Twig `path()`). Oro may run under a backoffice prefix or a
  subfolder, so never hard-code `/admin/...` or `window.location.origin + '/…'`.
- Storefront components call storefront routes only (`options: frontend: true`). Backoffice routes
  are for backoffice scripts.
- State changes are POST to an action with `#[CsrfProtection()]`, and the request carries Oro's
  `X-CSRF-Header` (double-submit against the `_csrf` cookie, `Oro\Bundle\SecurityBundle\Csrf\CsrfRequestManager`).
  Check in the Oro source how the Oro AJAX layer sends it before adding your own header code.
- Handle a non-JSON or empty error response and show a generic, translated message.

**Don't**:
- Put the API token or other secrets in query strings.
- Call cross-origin URLs other than Mollie Components (`https://js.mollie.com/v1/mollie.js`) and the
  Apple Pay JS API that the browser provides.

**Refs**: OWASP A01:2025, CWE-352, ASVS 4.1, 4.3

---

## Checkout: Card Data and Mollie Components

### Rule: Card data stays inside Mollie Components; submit only the token

**Level**: `strict`

**When**: Changing `mollie-credit-card.js`, `mollie-order-submit.js`, `mollie-applepay.js`, or the
storefront layout templates under `Resources/views/layouts/default/`.

**Do**:
- Load `mollie.js` only from `https://js.mollie.com/v1/mollie.js` (through `scriptjs`, as today).
- Initialize with only the profile ID, locale and test-mode flag from the component options.
- Get the card token with `mollie.createToken()` and put only that token into the hidden
  `<method>-mollie-card-token` field.

**Don't**:
- Read, copy, log, store or send card numbers, CVC or expiry. The shop must never see them.
- Add other third-party scripts or origins to checkout without a documented decision in `DESIGN.md`.
- Keep the token longer than the order submit (no `localStorage` / `sessionStorage` / cookies).

**Why**: This keeps card data out of the merchant's shop and PCI scope.

**Refs**: OWASP A04:2025, A08:2025, CWE-311, ASVS 14.2

---

## Secrets and Personal Data

### Rule: No secrets in browser code; no payment or customer data in browser storage or the console

**Level**: `strict`

**When**: Always.

**Do**: Only non-secret configuration reaches the browser (profile ID, test-mode flag, locale,
method ids, endpoint URLs).

**Don't**:
- Put the organization API token in JS, `data-*` attributes or page-component options.
- Store order, customer or payment data in `localStorage` / `sessionStorage`.
- Use `console.log` with tokens, responses containing customer data, or keys. Remove debug logging
  before committing; keep `console.error` for real failures only, without payloads.

**Refs**: OWASP A04:2025, A09:2025, CWE-200, CWE-532, ASVS 13.1, 14.1

---

## Navigation

### Rule: Redirect only to server-provided URLs

**Level**: `warning`

**When**: Setting `location`, calling `mediator.execute('redirectTo', …)`, or following a URL from a
response.

**Do**: Use a URL that the server rendered or returned for this action (for example the
`errorUrl` / `redirectUrl` of the Oro payment result).

**Don't**: Redirect to a value taken from the query string, the hash or a form field (open redirect).

**Refs**: OWASP A01:2025, CWE-601

---

## Conventions That Protect Correctness

### Rule: Follow the Oro component pattern and the existing syntax level

**Level**: `warning`

**When**: Adding or changing a script.

**Do**:
- Write modules as the existing files do: `define(function(require) { … })`, dependencies through
  `require('…')` (`jquery`, `underscore`, `oroui/js/mediator`,
  `oroui/js/app/components/base/component`, `oroui/js/app/views/base/view`), a component that
  extends `BaseComponent` / `BaseView` and reads its `options` from `data-page-component-options`.
- Register every new module in the matching `jsmodules.yml` (`dynamic-imports: molliepayment:`) and
  reference it as `molliepayment/js/app/...`.
- Talk to the Oro checkout through `mediator` events, as the existing components do. Never define a
  global `window.Mollie`, because `Mollie` is the global function from `mollie.js`.
- Keep to the syntax already used in these files (`let`/`const`, arrow functions, `var` in older
  files). Oro's asset build transpiles it; ES module `import`/`export` syntax is not used here.

**Don't**: Pull in libraries from a CDN, or add npm dependencies, without a decision in the plan.

**Why**: Oro loads page components only through its module registry, and overwriting `window.Mollie`
breaks Mollie Components.
