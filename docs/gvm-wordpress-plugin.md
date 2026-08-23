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
| Currency | Currency (currently `PLN`) |
| Default price | Fallback price when an article has no own price |
| Default template | Paywall template name |
| JS callback | Optional JS function called after payment |
| Post types | Content types where the paywall controls are available |
| Payment template | Look of the payment window (QR + SMS) — editable HTML |
| Paywall template | Look of the content blocker — editable HTML |
| Download template | Look of the file download button — editable HTML |

---

## Three content-selling modes

| Mode | How it works | Where it happens |
| --- | --- | --- |
| **Content blocker (hide)** | Part of the article is hidden, the rest is visible. After payment the content unlocks without a page reload | in the browser |
| **Full article (redirect)** | Only a teaser is visible. After payment the page redirects to the full article, rendered after verification | on the server |
| **File download (download)** | A teaser and a download button are visible. After payment the file is served for download after verification | on the server |

---

## Per-article settings

Each article (on enabled content types) has a **GetViaMsg Paywall** panel
(the Gutenberg block editor, or a meta box in the classic editor):

| Field | Description |
| --- | --- |
| Enable paywall | Turns the paywall on for the article |
| Price | Access price (0.01 – 10.00) |
| Redirect after payment | "Full article" mode (redirect after payment) |
| Download file | File to sell (download mode) |
| Hide strategy | How to hide content: `hide` / `blur` / `mangle-blur` / `none` |
| Hide sections / percent / words | How much content to show before the block |
| Reference | Unique identifier (defaults to the article slug or ID) |
| Condition | Optional condition for showing the paywall |

---

## Templates

The plugin uses three templates, which you can freely edit in the settings:

| Template | Role |
| --- | --- |
| **Payment** | Payment window (QR code, SMS sending, countdown) |
| **Paywall** | Content blocker with an "Unlock for X" button |
| **Download** | "Download for X" button |

Templates use `data-gvm-bind-*` placeholders (e.g. `data-gvm-bind-price`,
`data-gvm-bind-currency`, `data-gvm-bind-qr`, `data-gvm-bind-send-sms`),
which the plugin fills in automatically.

---

## Shortcodes

### `[gvm]` — block a fragment of content

```
[gvm price="2.99" hide-strategy="blur" hide-percent="40"]
  Paid content…
[/gvm]
```

Attributes: `price`, `hide-strategy`, `hide-percent`, `hide-sections`, `hide-words`,
`reference`, `title`, `cond` (hyphen and underscore forms are equivalent).

### `[gvm-protected-content]` — content only after payment

```
[gvm-protected-content]
  Full article — visible only after payment and verification.
[/gvm-protected-content]
```

### `[gvm-download]` — file download

```
[gvm-download price="1.99"]
```

Uses the file set in the "Download file" field of the current article.

---

## Conditions

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
