# GetViaMsg - WordPress plugin

The **GetViaMsg WordPress plugin** brings the [`gvm.js`](https://docs.wdft.ovh/gvm-js.html)
paywall into WordPress. It renders the declarative `data-gvm-*` markup server-side, so your
editors configure prices and paywalls from the post editor — no HTML editing.

The advantages:

- **Per-article control** — enable a paywall, set a price, pick a template and hide strategy per post.
- **No markup to maintain** — the plugin emits `data-gvm-*` attributes for you (shortcode + Gutenberg block + classic meta box).
- **Server-side signature verification** — PHP confirms the `gvm-signature` of a resolved payment before revealing protected content.
- **Central settings** — tenant, secret, and environment/API URL in one place.

The plugin is a thin, declarative wrapper: it does **not** reimplement the payment flow, it
delegates to `gvm.js` on the front end.

---

## Installation

1. Build or download `gvm.js` into the plugin's `assets/` directory
   (run `./build.sh`, which vendors `dist/gvm.js` from `gvm-sdk`).
2. Copy the plugin folder into `wp-content/plugins/` and activate it from **Plugins**.
3. Open **Settings → GVM (GetViaMsg)** and fill in the configuration.

---

## Configuration (Settings)

The plugin must be configured before use:

Setting

Description

Required

Tenant

Tenant ID received from GetViaMsg (`data-gvm-tenant`)

✔️

Secret

HMAC-SHA256 key used to verify `gvm-signature` server-side

✔️

Environment / API URL

Maps to `data-gvm-env` (`prod`/`qa`/`dev`/`demo`) or a custom `data-gvm-endpoint` URL

✔️

Currency

Default `PLN`

✖️

Default price

Fallback price for articles without an explicit one

✖️

Default template

Fallback paywall template

✖️

Enabled post types

Post types where the paywall controls are available

✖️

The **Secret** is stored server-side and never rendered to the front end.

---

## Per-article controls

Each post gets a meta box (classic editor) and a sidebar panel (block editor) with:

- **Enable paywall** — on/off.
- **Price** — `0.01 – 10.00`.
- **Template** — paywall template slug.
- **Hide strategy** — `blur` / `hide` / `mangle-blur`.
- **Hide % / sections / words** — optional split of the gated content.
- **Reference** — auto-filled from the post slug/ID.

The plugin wraps the post content in a container carrying the `data-gvm-*` attributes that
`gvm.js` consumes.

---

## Shortcodes

### `[gvm]`

Wrap content to gate it:

```html
[gvm price="2.99" template="paywall" hide-strategy="blur" hide-percent="40"]
  This is the paywalled body.
[/gvm]
```

Attributes (hyphen and underscore forms are equivalent):

Attribute

Description

`price`

Price, `0.01 – 10.00`

`template`

Paywall template slug → `data-gvm-hide-template-name`

`hide-strategy`

`blur` / `hide` / `mangle-blur`

`hide-percent` / `hide-sections` / `hide-words`

Split of the hidden content

`reference`

Unique reference (defaults to the post slug/ID)

`title`

Metadata title

### `[gvm-protected-content]`

Renders its content **only** when the current request carries a valid `gvm-signature` for a
`resolved` payment:

```html
[gvm-protected-content]
  Full article body — only visible after a successful, verified payment.
[/gvm-protected-content]
```

---

## Signature verification

After a successful payment, `gvm.js` appends these query params to redirect/download/inject
targets: `gvm-status`, `gvm-commitment-id`, `gvm-reference`, `gvm-tenant`, `gvm-signature`.

The plugin verifies the signature server-side using the configured **Secret**:

```text
expected = hash_hmac('sha256', commitment_id . tenant . reference . status, secret)
```

The protected content is only revealed when:

1. `gvm-status == "resolved"`, and
2. `gvm-signature` matches `expected` (constant-time `hash_equals`).

---

## Front-end output

When a post is paywalled, the plugin:

1. enqueues `gvm.js` as an ES module (`wp_enqueue_script_module` on WP 6.3+),
2. adds `data-gvm data-gvm-tenant data-gvm-currency data-gvm-env data-gvm-callback` to `<body>`,
3. renders the gated content with the per-article `data-gvm-*` attributes.

This means all `gvm.js` strategies (hide, redirect, download, inject) work unchanged — the
plugin only produces the markup.

---

## Enable logs

```html
<script>
  window.wdftDebug = true;
</script>
```

Use `Environment = demo` for theme development without real payments.
