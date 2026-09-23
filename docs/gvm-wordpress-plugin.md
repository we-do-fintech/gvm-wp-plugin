# GetViaMsg — WordPress plugin

The **GetViaMsg** plugin adds paid content (paywalls) to your WordPress site.
Readers pay for access via SMS/QR (GetViaMsg), and you decide what is paid —
a whole article, a part of it, or a downloadable file.

No code required — everything is configured from the WordPress admin.

---

## Installation

Install the plugin from the WordPress plugin directory, or from a `zip` file provided by
WDFT P.S.A. (Plugins → Add New → Upload Plugin), then activate it.

---

## Configuration

After activation, go to **Settings → GetViaMsg** and fill in the fields:

| Setting | Description |
| --- | --- |
| Tenant | Tenant ID received from GetViaMsg |
| Secret | HMAC key used to verify payments (entered once, never shown on the site) |
| Environment / API URL | Environment: `demo`, `local`, `prod`, `qa`, `dev`, or a custom API URL |
| Currency | Currency (a select — currently `PLN`, ready for more) |
| Default price | Fallback price when an article has no own price |
| Default hide strategy | Strategy for new articles/blocks: `hide` / `blur` / `mangle-blur` / `none` (default `mangle-blur`) |
| JS callback | Optional JS function called after payment |
| Post types | Content types where the paywall controls are available |
| Analytics | Emits gvm.js analytics events to GTM (`dl`), GA4 (`gtag`) and/or a custom `CustomEvent` (`custom`) |
| Payment template | Look of the payment window (QR + SMS + consent gate) — editable HTML |
| Paywall template | Look of the content blocker for a full page/post — editable HTML |
| Inline template | Look of the content blocker inside a block/shortcode — editable HTML |
| Download template | Look of the file download button — editable HTML |

Each template field links to the template gallery at
<https://templates.getviamsg.com/> — *Looking for inspiration?* The plugin
ships with `payment-with-terms`, `paywall-sticky` and `paywall-inline` as the
defaults. Categories are loaded from
`https://overlay.<env>.gvm.wdft.ovh/categories` (with a bundled fallback);
defaults are `article` for pages/posts and `report_pdf` for files.

---

## Three content-selling modes

| Mode | How it works | Where it happens |
| --- | --- | --- |
| **Content blocker (hide)** | Part of the article is hidden, the rest is visible. After payment the content unlocks without a page reload | in the browser |
| **Full article (redirect)** | Only a teaser is visible. After payment the page redirects to the full article, rendered after verification. With blur / mangle-blur a shape-preserving placeholder (same paragraphs and word lengths, random characters) is shown under the teaser | on the server |
| **File download (download)** | A teaser and a download button are visible. After payment the file is served for download after verification | on the server |

---

## Per-article settings

Each article (on enabled content types) has a **GetViaMsg Paywall** panel
(the Gutenberg block editor, or a meta box in the classic editor):

| Field | Description |
| --- | --- |
| Enable paywall | Turns the paywall on for the article |
| Price | Access price (0.01 – 50.00) |
| Redirect after payment | "Full article" mode (redirect after payment) |
| Hide strategy | How to hide content: `hide` / `blur` / `mangle-blur` / `none` |
| Reference | Unique identifier (defaults to the article slug or ID, 3-59 chars) |
| Category | Category sent with the commitment (default `article`) |
| Condition | Optional condition for showing the paywall (evaluated last) |
| Advanced | How much to hide: after N sections / percent / words (only one is applied) |

Files are no longer configured here — use the **Paid download** block or the
`[gvm-download]` shortcode.

---

## Blocks

Two Gutenberg blocks are available:

| Block | Purpose |
| --- | --- |
| **GetViaMsg — Paid content** | Wraps content in an inline paywall (hide strategy) |
| **GetViaMsg — Paid download** | Sells a single file download (with an upload button and an optional explicit `reference`) |

Place multiple "Paid download" blocks in one article to offer a list of files.
The download block defaults to the `report_pdf` category.

---

## Templates

The plugin uses four templates, which you can freely edit in the settings:

| Template | Role |
| --- | --- |
| **Payment** | Payment window (QR code, SMS sending, countdown, consent gate) |
| **Paywall** | Sticky content blocker for a full page/post |
| **Inline** | In-content blocker for a block/shortcode (`hide` / `blur`) |
| **Download** | "Download for X" button |

Templates use `data-gvm-bind-*` placeholders (e.g. `data-gvm-bind-price`,
`data-gvm-bind-currency`, `data-gvm-bind-qr`, `data-gvm-bind-send-sms`),
which the plugin fills in automatically. Looking for inspiration? See the
gallery at <https://templates.getviamsg.com/>.

---

## Shortcodes

### `[gvm]` — block a fragment of content

```
[gvm price="2.99" hide-strategy="blur" hide-percent="40"]
  Paid content…
[/gvm]
```

Attributes: `price`, `hide-strategy`, `hide-percent`, `hide-sections`, `hide-words`,
`reference`, `title`, `cond`, `category` (hyphen and underscore forms are equivalent).

### `[gvm-protected-content]` — content only after payment

```
[gvm-protected-content]
  Full article — visible only after payment and verification.
[/gvm-protected-content]
```

### `[gvm-download]` — file download

```
[gvm-download file="report.pdf" price="1.99" reference="report-2026"]
```

Downloads a single file (`file` = filename in `uploads/gvm/`). The `reference`
attribute is optional; use it when the auto-generated reference (post reference +
file name) would be too long. Place one shortcode (or one "Paid download" block)
per file to offer a list of downloads in one article.

---

## Conditions
> Only works on "client" side, on "payment after redirect" (server side) conditions not work.
The paywall can be shown only when a condition is met, e.g.:

```
ab > 0.1 AND language includes 'pl' AND utm_source == 'test'
```

Supported operators: `>`, `<`, `==`, `!=`, `includes`, `startsWith`, `endsWith`,
`matchMedia`, combined with `AND` / `OR` and parentheses.

---

## Security

The **Secret** key is stored only on the server and never reaches the site.
Payment is verified server-side (HMAC-SHA256 signature), so the "full" content
and downloadable files are only served after the payment is confirmed.

---

## User guide and repository

You can find current [USER_GUIDE.md](https://github.com/we-do-fintech/gvm-wp-plugin/blob/main/USER_GUIDE.md) at [gvm-wp-plugin repository](https://github.com/we-do-fintech/gvm-wp-plugin/)
Current releases are available at [Github Releases](https://github.com/we-do-fintech/gvm-wp-plugin/releases)
