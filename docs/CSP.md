# Content Security Policy

## Status

Fase 7 has a nonce-based CSP with three reversible modes. The application defaults to `CSP_MODE=off`; this is intentional until the full page and interaction inventory has been exercised in report mode. No claim is made that every route is violation-free or ready for enforcement.

| Mode | Response header | Use |
|---|---|---|
| `off` | No CSP header | Default and immediate rollback |
| `report` | `Content-Security-Policy-Report-Only` | Observe violations without blocking resources |
| `enforce` | `Content-Security-Policy` | Block resources that do not match the policy |

Set `CSP_MODE=report` in the environment to begin observation. Return to `off` or `report` by changing the environment value if a rollout breaks a page. Do not switch production to `enforce` until the principal authenticated, public, admin, auth, error, and styleguide pages and their interactions have been checked with no unexplained violations.

Every response receives a fresh Vite nonce. Vite-generated script/style tags and the retained theme bootstrap script use that nonce. The policy does not allow `unsafe-eval` or `unsafe-inline` for scripts. `style-src-attr 'unsafe-inline'` remains as a documented compromise because the views use inline presentation styles; reduce these uses before considering removal.

The local environment additionally permits Vite's `http://localhost:5173` development origin and WebSocket connection. Production does not receive those sources. The report endpoint is `POST /csp-report`, is unauthenticated, CSRF-exempt, limited to 30 requests per minute, and rejects request bodies larger than 16 KiB. It accepts legacy CSP and Reporting API JSON reports and writes only a directive, disposition, and stripped origins to the rotating `storage/logs/csp-YYYY-MM-DD.log` channel. It omits document URLs, paths, query strings, user identifiers, IP addresses, and request bodies.

## Alpine and inline-code inventory

The standard Alpine runtime has been replaced by `@alpinejs/csp`. Stateful UI is registered in `resources/js/components.js`; date selection and the habit form use external JavaScript modules. No literal HTML `onclick`, `onchange`, or `onsubmit` handlers should be added.

| Surface | Handling |
|---|---|
| Modal state, focus loop, dirty-form close guard | Registered `modal` Alpine component |
| Dropdown state and close events | Registered `dropdown` Alpine component |
| Admin navigation state and class | Registered `adminSidebar` Alpine component |
| Rupiah input formatting | Registered `rupiahInput` Alpine component |
| Calendar date selection | `data-calendar-*` attributes and `resources/js/calendar.js` |
| Habit form conditional fields and preview | `resources/js/habit-form.js` |
| Theme bootstrap in `layouts/base.blade.php` | Retained inline script with the request nonce |
| Inline `style` attributes | Retained for presentational values; allowed by `style-src-attr` |
| Simple event dispatch (`$dispatch`) on buttons | Kept as Alpine CSP-compatible event expressions |

## Reporting and release checklist

1. Deploy with `CSP_MODE=report` and the default same-origin `CSP_REPORT_URI=/csp-report`.
2. Visit every user, admin, authentication, error, and styleguide page; exercise every modal, dropdown, money input, calendar, and form state.
3. Review only the sanitized CSP channel. Explain or remove every violation; do not broaden the policy without a documented source requirement.
4. After an observation period with no unexplained violations, try `enforce` in a controlled environment and repeat the interaction checks.
5. Keep `off`/`report` available as a configuration rollback. Never add a third-party source based solely on a report; verify ownership and necessity first.

At this point the endpoint and mode behavior are covered by feature tests, but a full browser sweep and an observation period have not been completed. Keep production in `off` or `report` until that work is recorded.
