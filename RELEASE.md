# Releasing a new version

Release tooling: `build.sh` (zip), `publish.sh` (SVN), `.github/workflows/deploy.yml`
(GitHub Action → wp.org SVN).

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

## 2. Build the installable zip

```bash
./build.sh zip   # writes dist/gvm-wp-plugin-<version>.zip
```

`build.sh` reads the version from the `gvm-wp.php` header. The zip excludes dev-only
files (`wordpress-env/`, `docs/`, `.github/`, build scripts).

## 3. Commit and tag

```bash
git add -A
git commit -m "release: v0.2.0"
git tag v0.2.0
git push origin main --tags
```

## 4. Deploy to WordPress.org

### Option A — GitHub Actions (recommended)

Pushing the tag triggers `.github/workflows/deploy.yml`, which builds the zip and
deploys to the wp.org SVN repo via `10up/action-wordpress-plugin-deploy`.

Repository secrets required:

- `SVN_USERNAME`
- `SVN_PASSWORD`

The deploy reads the version from `readme.txt` / plugin header and creates the
`tags/<version>` SVN tag automatically.

### Option B — manual SVN

```bash
WP_SVN_USERNAME=youruser WP_SVN_PASSWORD=yourapppassword ./publish.sh
```

Requires `svn` installed and a hosted plugin slug (`WP_PLUGIN_SLUG`, default
`gvm-wp-plugin`). It syncs `trunk`, commits and creates `tags/<version>`.

## 5. Verify

- Inspect `dist/gvm-wp-plugin-<version>.zip` (Plugins → Add New → Upload Plugin).
- Check the wp.org plugin page / SVN `tags/<version>`.
