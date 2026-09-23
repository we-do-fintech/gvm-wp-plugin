# GetViaMsg for WordPress — User Guide

This guide walks a site administrator through installing and configuring the
GetViaMsg (GVM) plugin, up to the first paid article and the first inline
(content or download) paywall.

---

## Before you start

Make sure you have:

| Requirement | Where to get it |
| --- | --- |
| **WordPress 6.3+** with PHP 7.4+ | your hosting / IT |
| **Tenant ID** | WDFT (GetViaMsg) |
| **Secret key** | WDFT (GetViaMsg) — used for server-side payment verification, never shown on the site |
| **Environment** | WDFT (GetViaMsg) — e.g. `demo`, `dev`, `qa`, `prod` |
| **Signed contract with WDFT** | required before going live in production |
| Administrator access to WordPress | your site |

> **Environments:** pick the right one before you start testing.
>
> | Environment | Use it for |
> | --- | --- |
> | `demo` | **Templates & layout** — payments are fully simulated (no SMS, no charge). Best for designing and iterating on the payment / paywall / inline / download templates. |
> | `dev` / `qa` | **Integration testing** — real SMS gateway, but dummy money and the full test payment process. |
> | `prod` | **Live** — real money and the real payment gateway. Requires a signed contract with WDFT. |
>
> Set up everything on `demo` first, run the real SMS flow on `dev`/`qa`, and
> switch to `prod` only after the contract with WDFT is signed.

---

## 1. Download the newest version

1. Open the releases page:
   **https://github.com/we-do-fintech/gvm-wp-plugin/releases**
2. Under the newest release, download the file
   `gvm-wp-plugin-<version>.zip`.

---

## 2. Install in WordPress

1. Log in to WordPress admin.
2. Go to **Plugins → Add New → Upload Plugin**.
3. Choose the downloaded `.zip` and click **Install Now**.
4. Click **Activate Plugin**.

Alternative (manual): copy the `gvm-wp-plugin` folder into
`wp-content/plugins/` and activate it from **Plugins → Installed Plugins**.

After activation the protected downloads folder
(`wp-content/uploads/gvm/`) is created automatically.

---

## 3. Settings page setup

Go to **Settings → GetViaMsg**.

Fill in the fields from WDFT:

| Field | What to enter |
| --- | --- |
| **Tenant** | your Tenant ID |
| **Secret** | your Secret key (stored on the server only; never printed on the site) |
| **Environment / API URL** | `demo` for testing, then the environment from WDFT (`dev` / `qa` / `prod`) |
| **Currency** | `PLN` |
| **Default price** | fallback price used when an article has none (e.g. `0.99`) |
| **Default hide strategy** | used for new articles/blocks: `hide`, `blur`, `mangle-blur` (default) or `none` |
| **Post types** | which content types get the paywall controls (usually **Posts**) |
| **Analytics** | optional: GTM (`dl`), GA4 (`gtag`), custom event (`custom`) |

Optional — templates (leave empty to use the built-in designs):

| Template | Used for |
| --- | --- |
| **Payment template** | the payment window (QR + SMS + consent) |
| **Paywall template** | full page/post paywall (sticky bar) |
| **Inline template** | paywall inside a block/shortcode |
| **Download template** | file download paywall |

Click **Save Changes**. Each template field links to the template gallery at
**https://templates.getviamsg.wdft.ovh/** if you want more designs.

---

## 4. Your first article

1. Create or edit a post.
2. Open the **GetViaMsg Paywall** panel:
   - **Gutenberg (block editor):** sidebar → *GetViaMsg Paywall*.
   - **Classic editor:** the *GetViaMsg Paywall* meta box.
3. Configure:
   - **Enable paywall** — turn it on.
   - **Price** — access price (0.01–50.00).
   - **Hide strategy** — how much is hidden and how (`mangle-blur` by default).
   - **Redirect after payment** (optional) — show a teaser, then send the reader
     to a verified URL that renders the full article server-side. With
     `blur`/`mangle-blur` a generated placeholder is shown under the teaser.
   - **Reference** — leave empty to auto-generate from the slug/ID (3–59 chars).
   - **Category** — defaults to `article`.
   - **Condition** (optional) — e.g. `ab > 0.1 AND language includes 'pl'`.
   - **Advanced** — how much to hide (sections / percent / words; only one is applied).
4. Click **Publish** / **Update**.
5. Open the post on the site — the paywall should appear.

---

## 5. Your first inline item

Inline items are placed inside a post and use the **inline** template. There are
two kinds.

### A. Paid content (part of an article)

1. In the editor, add the block **GetViaMsg — Paid content**.
2. Put the paid content inside the block.
3. In the block sidebar set **Price**, **Hide strategy**, **Reference**,
   **Category** and optional **Condition**.
4. Update the post.

### B. Paid download (a file)

1. In the editor, add the block **GetViaMsg — Paid download**.
2. Click **Upload file** — the file is stored in `uploads/gvm/` (protected).
3. Set **Price**, and optionally **Reference** (use this if the auto-generated
   reference from the file name would be too long), **Category** (default
   `report_pdf`) and **Condition**.
4. Update the post.

You can add several "Paid download" blocks in one article to offer multiple
files.

### Shortcodes (alternative to blocks)

```
[gvm price="2.99" hide-strategy="blur" hide-percent="40"]
Paid content goes here.
[/gvm]

[gvm-protected-content reference="my-article"]
Content revealed only after a verified payment.
[/gvm-protected-content]

[gvm-download file="report.pdf" price="1.99" reference="report-2026"]
```

---

## What the reader sees

1. The reader opens the article and sees the teaser / inline paywall.
2. They click the unlock button → the payment window opens (QR code + SMS).
3. After the payment is confirmed:
   - **hide / blur / mangle-blur** — the content unlocks without a page reload,
   - **redirect** — the page reloads with the full article,
   - **download** — the file starts downloading.

Payments are always verified **server-side** with the Secret key, so paid
content and files are only served after confirmation.

---

## Troubleshooting

| Problem | Check |
| --- | --- |
| No paywall appears | Plugin active; the post type is selected in **Post types**; **Enable paywall** is on; Tenant/Environment are set |
| Payment window does not open | Correct **Tenant** and **Environment**; the article has a **Reference** (3–59 chars) and a valid **Price** (0.01–50.00) |
| Redirect/download does not unlock | **Secret** is correct (server-side signature verification); the file exists in `uploads/gvm/` |
| Reading time / words missing in redirect mode | The **Paywall template** must contain `<span data-gvm-bind-reading-time></span>` and `<span data-gvm-bind-reading-words></span>`; clear the template field to fall back to the built-in one |
| Download says "access denied" | The **Reference** on the download block matches the one used for payment; the file name is correct |
| Nothing works in production | A **signed contract with WDFT** is required and the production **Environment / Secret** must be set |

---

## Templates & support

- Template gallery: **https://templates.getviamsg.wdft.ovh/**
- Detailed plugin documentation: **https://docs.wdft.ovh/getviamsg-wordpress-plugin**
- Analytics events reference: **https://docs.wdft.ovh/getviamsg-analytics-events-gvmjs**
- gvm.js client behaviour & template building: **https://docs.wdft.ovh/gvm-js.html**
- GetViaMsg: **https://wdft.ovh/**
- Plugin repository: **https://github.com/we-do-fintech/gvm-wp-plugin**

For Tenant ID, Secret key, environment and production contracts, contact WDFT
(GetViaMsg).
