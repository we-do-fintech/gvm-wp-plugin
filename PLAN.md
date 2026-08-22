# PLAN — gvm-wp-plugin

## Goal

WordPress plugin exposing GetViaMsg paywalls with per-article pricing/template, server-side signature verification, and admin configuration.

## Configuration (Settings page)

The plugin must let the admin configure:

- **Tenant** (`data-gvm-tenant`)
- **Secret** (HMAC-SHA256 key for server-side signature verification)
- **Environment / API URL** (maps to `data-gvm-env` and/or `data-gvm-endpoint` — see note)
- Defaults: currency (PLN), default price, default template, enabled post types.

Note: `gvm.js` currently supports `data-gvm-env` (`demo|local|prod|qa|dev`). Custom URL support (`data-gvm-endpoint`) may require a small gvm-sdk enhancement — see gvm-sdk `PLAN.md`.

Note: the plugin renders `data-gvm-*` attributes server-side, so it uses **`gvm.js`** (declarative), not `gvm-overlay.js`. A future "managed" mode could enqueue `gvm-overlay.js` + `data-gvm-config` instead.

## Per-article controls

- Meta-box (classic editor) + Gutenberg sidebar panel: enable paywall, price, template, hide strategy + percent/sections/words, reference (auto: post slug / ID).

## Rendering

- Shortcode `[gvm ...]...[/gvm]` -> wraps content in a div carrying `data-gvm-*` attributes.
- Gutenberg block with server-side `render_callback` + `assets/editor.js`.
- Enqueue `gvm.js` (module) only when a paywall is present; add `data-gvm data-gvm-tenant data-gvm-currency data-gvm-env data-gvm-callback` to `<body>`.

## Signature verification (server-side, PHP)

- Verify the `gvm-signature` query param using:

  `hash_hmac('sha256', $commitmentId . $tenant . $reference . $status, $secret)` (hex), compared with `hash_equals`.

- Use in `[gvm-protected-content]` and for inject/download targets.

## Deliverables (order)

1. Bootstrap + settings page (tenant, secret, environment URL, defaults).
2. Enqueue `gvm.js` as module + body attributes.
3. Shortcode + meta-box.
4. Gutenberg block.
5. Signature verification class + `[gvm-protected-content]`.
6. Templates + `build.sh` + deploy action (SVN wp.org).
