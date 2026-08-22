# GVM (GetViaMsg) — WordPress Plugin

WordPress plugin integrating [GetViaMsg](https://wdft.ovh/) (`gvm.js`) paywalls
into your site: per-article pricing, configurable hide strategies, and
server-side signature verification. Plain PHP, no framework.

## Requirements

- WordPress 6.3+ (uses `wp_enqueue_script_module`; older versions fall back to `type="module"`).
- PHP 7.4+.

## Installation

1. Copy the `gvm-wp-plugin` folder to `/wp-content/plugins/`, or upload the zip via **Plugins → Add New → Upload Plugin**.
2. Activate the plugin.
3. Go to **Settings → GetViaMsg** and configure the settings below.

### Required settings

| Field | Option key | Description |
| --- | --- | --- |
| Tenant | `gvm_tenant` | Rendered as `data-gvm-tenant` (3-60 chars). |
| Secret | `gvm_secret` | HMAC-SHA256 key used for server-side `gvm-signature` verification. Stored in options only and never rendered on the frontend. |
| Environment / API URL | `gvm_env_url` | A named environment (`demo`, `local`, `prod`, `qa`, `dev`) maps to `data-gvm-env`; a full URL maps to `data-gvm-endpoint`. |

Optional defaults: currency (`gvm_currency`, default `PLN`), default price
(`gvm_default_price`), default template (`gvm_default_template`), JS callback
(`gvm_callback`), and enabled post types (`gvm_post_types`, multi-select).

## Usage

### Per-article paywall

Edit a post of an enabled post type and enable the paywall in the
**GetViaMsg Paywall** meta box (classic editor) or the Gutenberg sidebar panel.
The entire content is wrapped in a paywall.

### Shortcode

```
[gvm price="2.99" template="paywall" hide-strategy="blur" hide-percent="40"]
Your premium content goes here.
[/gvm]
```

Supported attributes (hyphen and underscore forms are equivalent):

- `price` — `data-gvm-price` (0.01-10).
- `template` — paywall `<template>` slug (`data-gvm-hide-template-name`).
- `hide-strategy` / `hide_strategy` — `blur|hide|mangle-blur`.
- `hide-percent` / `hide_percent` — `data-gvm-hide-percent` (1-100).
- `hide-sections` / `hide_sections` — `data-gvm-hide-sections`.
- `hide-words` / `hide_words` — `data-gvm-hide-words`.
- `reference` — `data-gvm-reference` (auto-generated from slug/post ID when empty).
- `title` — `data-gvm-metadata-title`.

### Server-side protected content

```
[gvm-protected-content reference="my-article"]
Secret content revealed only after payment.
[/gvm-protected-content]
```

The content is only rendered when the request carries a valid `gvm-signature`
(see below). Otherwise a paywall is rendered in its place.

### Gutenberg block

Add the **GetViaMsg Paywall** block and nest content inside it. The block is
server-rendered to the same `data-gvm-*` wrapper markup.

## Signature verification

After a successful payment, `gvm.js` redirects (or fetches) with the query
parameters `gvm-status`, `gvm-commitment-id`, `gvm-reference`, `gvm-tenant` and
`gvm-signature`. The plugin verifies:

```php
$expected = hash_hmac(
	'sha256',
	$commitment_id . $tenant . $reference . $status,
	$secret
);

$valid = hash_equals( $expected, $signature );
```

`[gvm-protected-content]` renders its content only when `Gvm_Signature::verify_query_signature()`
returns true, i.e. the signature is valid **and** `gvm-status` is `resolved`.

## Bundling `gvm.js`

By default the plugin loads `gvm.js` from
`https://esm.sh/@wdft/gvm-sdk@latest/gvm.js`. To self-host a copy:

```bash
./build.sh
```

This copies `../gvm-sdk/dist/gvm.js` into `assets/gvm.js` (gitignored). If the
file is not found, it prints instructions. Pass a custom path to copy from
elsewhere, e.g. `./build.sh /path/to/gvm.js`.

```bash
./build.sh zip   # build a clean installable zip into dist/
./build.sh all   # vendor (if available) then zip
```

When `assets/gvm.js` exists, the plugin prefers it over the CDN. Override the
URL at any time with the `gvm_sdk_url` filter.

## Development

The repo follows WordPress coding standards (snake_case hooks, `Gvm_` class
prefix, escaped output, nonces + capability checks on all admin input).
Linting is done with PHPCS/WordPress-Coding-Standards; PHP is not available in
this environment, so `php -l` and PHPCS were not run here.

## License

GPL-2.0-or-later.
