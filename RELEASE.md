# Releasing a new version

Release tooling: `build.sh` (zip), `.github/workflows/release.yml` (GitHub
release + zip on `v*` tags), `.github/workflows/deploy.yml` (manual wp.org SVN
deploy), `publish.sh` (manual SVN).

## 1. Bump the version

The version lives in two places — keep them in sync:

- `gvm-wp.php` — plugin header `Version:` and the `GVM_WP_VERSION` constant.
- `readme.txt` — `Stable tag:` and the `== Changelog ==` section.

```txt
Stable tag: 0.2.0
```

```txt
== Changelog ==

= 0.2.0 =
* Added download strategy …
```

Also update `Requires at least:` / `Tested up to:` in `readme.txt` if anything changed.

## 2. Build the installable zip (optional, local check)

```bash
./build.sh all   # vendor gvm.js + categories.json, then writes dist/gvm-wp-plugin-<version>.zip
```

`build.sh` reads the version from the `gvm-wp.php` header. The zip excludes dev-only
files (`wordpress-env/`, `docs/`, `.github/`, build scripts). In CI the sibling
repos are not available; `release.yml` fetches `gvm.js` from the npm package
`@wdft/gvm-sdk` (best effort) and falls back to the esm.sh CDN.

## 3. Commit and tag

```bash
git add -A
git commit -m "release: v0.2.0"
git tag v0.2.0
git push origin main --tags
```

## 4. GitHub release (default)

Pushing a `v*` tag triggers `.github/workflows/release.yml`, which builds the zip
and creates a **GitHub Release** with the zip attached. No secrets required —
this is the way to hand the plugin to a client.

```bash
git tag v0.2.0 && git push origin v0.2.0
```

## 5. Deploy to WordPress.org (optional, manual)

Only when you actually want to publish on wp.org. `deploy.yml` is now
`workflow_dispatch`, so it no longer runs on tags. Either trigger it from the
Actions tab, or use the SVN script.

Repository secrets required for the Action:

- `SVN_USERNAME`
- `SVN_PASSWORD`

The deploy reads the version from `readme.txt` / plugin header and creates the
`tags/<version>` SVN tag automatically.

### Manual SVN

```bash
WP_SVN_USERNAME=youruser WP_SVN_PASSWORD=yourapppassword ./publish.sh
```

Requires `svn` installed and a hosted plugin slug (`WP_PLUGIN_SLUG`, default
`gvm-wp-plugin`). It syncs `trunk`, commits and creates `tags/<version>`.

## 6. Verify

- GitHub → **Releases**: the zip asset is attached.
- Inspect `dist/gvm-wp-plugin-<version>.zip` (Plugins → Add New → Upload Plugin).
- If published to wp.org: check the plugin page / SVN `tags/<version>`.
