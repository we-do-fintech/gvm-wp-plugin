=== GetViaMsg Paywall ===
Contributors: wdft
Tags: paywall, monetization, sms, subscription, content
Requires at least: 6.3
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 0.1.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Integrates GetViaMsg (gvm.js) paywalls into WordPress with per-article pricing and server-side signature verification.

== Description ==

GetViaMsg Paywall adds SMS-based paywalls to your WordPress content, powered by the
[GetViaMsg gvm.js SDK](https://esm.sh/@wdft/gvm-sdk@latest/gvm.js).

Features:

* Global settings (tenant, secret, environment, currency, defaults) via the Settings API.
* Per-article controls in the classic editor meta box and the Gutenberg sidebar.
* `[gvm]` shortcode and a server-rendered Gutenberg block for wrapping content (inline template).
* `[gvm-protected-content]` shortcode with server-side `gvm-signature` verification (HMAC-SHA256).
* Configurable hide strategies (hide / blur / mangle-blur) with sections, percent or words under "Advanced".
* Built-in templates: sticky paywall (page/post), inline paywall (blocks/shortcodes), payment with a terms gate and a download trigger, plus a link to the template gallery.
* File downloads are sold through the "Paid download" block or `[gvm-download]` shortcode (no per-article download field).
* Content categories with remote catalog lookup and a built-in fallback (defaults: `article` for pages/posts, `report_pdf` for files).
* gvm.js is enqueued as an ES module (WordPress 6.3+) only when a paywall is present.

== Installation ==

1. Upload the `gvm-wp-plugin` folder to `/wp-content/plugins/`, or install the zip via Plugins → Add New → Upload Plugin.
2. Activate the plugin.
3. Go to Settings → GetViaMsg and enter your tenant and secret.

== Usage ==

**Per-article paywall**

Edit a post (enabled post type) and enable the paywall in the "GetViaMsg Paywall"
meta box or Gutenberg sidebar. The whole content is wrapped in a paywall.

**Shortcode**

`[gvm price="0.75" template="paywall" hide_strategy="hide" hide_sections="6"]Your content[/gvm]`

**Server-side protected content**

`[gvm-protected-content reference="my-article"]Secret content[/gvm-protected-content]`

Content is only rendered after gvm.js redirects back with a valid `gvm-signature`.

**Block**

Add the "GetViaMsg Paywall" block and nest content inside it.

== Frequently Asked Questions ==

= Where do I get a tenant and secret? =

From your GetViaMsg provider (WDFT).

= Does the plugin support currencies other than PLN? =

gvm.js currently supports PLN only.

= Can I self-host gvm.js? =

Yes. Run `build.sh vendor /path/to/gvm-sdk/dist/gvm.js` to bundle a copy in
`assets/gvm.js`, which the plugin then prefers over the CDN.

== Changelog ==

= 0.1.4 =
* Updated all GetViaMsg links to the new domain `https://getviamsg.com` (payment template footer and the template gallery link in Settings → GetViaMsg).
* User guide (USER_GUIDE.md) now includes annotated screenshots for installation, settings, blocks and the reader payment flow.

= 0.1.3 =
* Maximum price raised to 50.00 (requires a matching gvm.js / @wdft/gvm-sdk release — the SDK must allow `data-gvm-price` up to 50.0).
* Category field: you can now clear it to browse the full category list; the default (`article` for posts/pages, `report_pdf` for downloads) is applied server-side when left empty.
* User guide (USER_GUIDE.md) with environment guidance: `demo` for templates, `dev`/`qa` for real SMS + dummy money, `prod` for real payments.
* Links to the detailed plugin, analytics-events and gvm.js documentation in the README, user guide and settings page.

= 0.1.2 =
* Redirect mode now supports blur / mangle-blur: a shape-preserving placeholder (same paragraphs and word lengths, random characters) is rendered under the teaser, so the paywall looks like it covers real content without leaking it. Filters: `gvm_redirect_filler`, `gvm_redirect_filler_alphabet`, `gvm_redirect_filler_max_words`.

= 0.1.1 =
* Removed the per-article "Download file" field; files are sold via the "Paid download" block or `[gvm-download]` shortcode.
* Default hide strategy is now `mangle-blur` (configurable in Settings → GetViaMsg).
* Added an `inline` template used by the "Paid content" block and the `[gvm]` / `[gvm-protected-content]` shortcodes.
* The "Paid download" block/shortcode accepts an explicit `reference` and defaults to the `report_pdf` category.
* Categories are resolved dynamically from `https://overlay.<env>.gvm.wdft.ovh/categories` with a bundled fallback; defaults are `article` (pages/posts) and `report_pdf` (files).
* Hide detail fields moved under "Advanced"; the condition field is now last.
* Currency is a select (PLN today) ready for future currencies.
* Built-in templates now ship with the plugin: `payment-with-terms`, `paywall-sticky`, `paywall-inline` and a refreshed download paywall.

= 0.1.0 =
* Initial release.
