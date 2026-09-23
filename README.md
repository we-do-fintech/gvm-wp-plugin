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

Optional defaults: currency (`gvm_currency`, a select — currently `PLN`),
default price (`gvm_default_price`), default hide strategy
(`gvm_default_hide_strategy`, default `mangle-blur`), JS callback
(`gvm_callback`), enabled post types (`gvm_post_types`, multi-select), and
analytics trackers (`gvm_analytics`, `dl` / `gtag` / `custom`, mapped to
`data-gvm-analytics`).

The content-category catalog is loaded dynamically from
`https://overlay.<env>.gvm.wdft.ovh/categories` (cached for 12 hours) and falls
back to a bundled `assets/categories.json` / the built-in catalog. Defaults are
`article` for page/post paywalls and `report_pdf` for downloads.

## Usage

### Per-article paywall

Edit a post of an enabled post type and enable the paywall in the
**GetViaMsg Paywall** meta box (classic editor) or the Gutenberg sidebar panel.
The entire content is wrapped in a paywall. Hide detail fields (sections,
percent, words) live under **Advanced**; the condition field is last. Files are
no longer configured here — use the **Paid download** block or the
`[gvm-download]` shortcode.

With **Redirect after payment** enabled, blur / mangle-blur render a
shape-preserving placeholder under the teaser (same paragraphs and word
lengths, random characters) so the paywall looks like it covers real content
without sending it to the browser. `hide` keeps the current teaser-only view.
The placeholder is filterable via `gvm_redirect_filler`,
`gvm_redirect_filler_alphabet` and `gvm_redirect_filler_max_words`.

### Shortcode

```
[gvm price="2.99" hide-strategy="blur" hide-percent="40"]
Your premium content goes here.
[/gvm]
```

Supported attributes (hyphen and underscore forms are equivalent):

- `price` — `data-gvm-price` (0.01-10).
- `hide-strategy` / `hide_strategy` — `blur|hide|mangle-blur`.
- `hide-percent` / `hide_percent` — `data-gvm-hide-percent` (1-100).
- `hide-sections` / `hide_sections` — `data-gvm-hide-sections`.
- `hide-words` / `hide_words` — `data-gvm-hide-words`.
- `reference` — `data-gvm-reference` (auto-generated from slug/post ID when empty).
- `title` — `data-gvm-metadata-title`.
- `cond` — `data-gvm-cond` condition expression (applied at page level), e.g. `ab > 0.1 AND language includes 'pl'`.
- `category` — `data-gvm-category` (max 64 chars), sent with the commitment.

The same controls (including the condition) are available per-article in the
meta box / sidebar panel and as block attributes. The `[gvm]` and
`[gvm-protected-content]` shortcodes use the **inline** template.

### Server-side protected content

```
[gvm-protected-content reference="my-article"]
Secret content revealed only after payment.
[/gvm-protected-content]
```

The content is only rendered when the request carries a valid `gvm-signature`
(see below). Otherwise a paywall is rendered in its place.

### Gutenberg blocks

Two server-rendered blocks are registered:

- **GetViaMsg — Paid content** (`gvm/paywall`) — wraps content in an inline paywall (hide strategy).
- **GetViaMsg — Paid download** (`gvm/download`) — sells a single file download, with an upload button and an optional explicit `reference`.

### Download (gated file)

Upload the file with the **Upload file** button in the **Paid download** block
(or the `[gvm-download]` shortcode) — the file is stored in the protected
`wp-content/uploads/gvm/` directory, blocked from direct HTTP access by a
generated `.htaccess`. The plugin renders a download trigger; after payment the
`?gvm_download=<post_id>&file=<filename>` endpoint verifies the signature and
streams the file with `Content-Disposition: attachment`.

The reference is derived from the post reference + file name. For long file
names, set an explicit **Reference** (3-59 chars) on the block/shortcode; it is
recorded on save and used by the download endpoint. The block defaults to the
`report_pdf` category.

## Signature verification

After a successful payment, `gvm.js` redirects (or fetches) with the query
parameters `gvm-status`, `gvm-commitment-id`, `gvm-reference`, `gvm-tenant` and
`gvm-signature`. The plugin verifies (matching gvm-sdk / gvm backend):

```php
$expected = hash_hmac(
	'sha256',
	$commitment_id . $tenant . $reference . $status . $secret, // note: secret is appended to the message
	$secret
);

$valid = hash_equals( $expected, $signature );
```

`[gvm-protected-content]` / redirect / download reveal their content only when
`Gvm_Signature::verify_query_signature()` returns true, i.e. the signature is
valid **and** `gvm-status` is `resolved` or `duplicated`.

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

## Built-in templates

The plugin ships with editable default templates (Settings → GetViaMsg):

- **Payment** — `templates/payment.php`, based on `payment-with-terms` (QR + SMS + consent gate).
- **Paywall** — `templates/paywall.php`, based on `paywall-sticky` (page/post).
- **Inline** — `templates/inline.php`, based on `paywall-inline` (blocks/shortcodes).
- **Download** — `templates/download.php` (gated file trigger).

Each template field links to the template gallery at
<https://templates.getviamsg.wdft.ovh/> for more designs. `./build.sh` also
vendors `../gvm-sdk-admin/categories.json` into `assets/categories.json` as the
offline fallback catalog.

## Development

The repo follows WordPress coding standards (snake_case hooks, `Gvm_` class
prefix, escaped output, nonces + capability checks on all admin input).
`php -l` passes on all PHP files.

A local WordPress + MySQL stack is provided for development
(`wordpress-env/docker-compose.yml`) and bind-mounts the plugin source:

```bash
./dev.sh up       # WordPress at http://localhost:8080, plugin mounted
./dev.sh logs     # tail WordPress logs
./dev.sh down     # stop the stack
```

## License

GPL-2.0-or-later.
