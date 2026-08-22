#!/usr/bin/env bash
set -euo pipefail

# publish.sh — publish gvm-wp-plugin to the WordPress.org plugin repository (SVN).
#
# Usage:
#   WP_SVN_USERNAME=... WP_SVN_PASSWORD=... ./publish.sh
#
# The version is read from readme.txt ("Stable tag"). Publishing requires a
# hosted plugin on wordpress.org and SVN write access.

SLUG="${WP_PLUGIN_SLUG:-gvm-wp-plugin}"
SVN_URL="https://plugins.svn.wordpress.org/${SLUG}"
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

command -v svn >/dev/null 2>&1 || { echo "svn is required" >&2; exit 1; }

VERSION="$(grep -m1 '^Stable tag:' "$ROOT/readme.txt" | awk '{print $3}')"
if [[ -z "$VERSION" ]]; then
	echo "Could not read Stable tag from readme.txt" >&2
	exit 1
fi

WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT

echo "Checking out $SVN_URL ..."
svn checkout --depth empty "$SVN_URL" "$WORK/svn"

# Sync trunk.
svn update --set-depth infinity "$WORK/svn/trunk"
rsync -a --delete \
	--exclude '.git' --exclude 'node_modules' --exclude 'vendor' \
	--exclude 'dist' --exclude '*.zip' --exclude '.DS_Store' \
	"$ROOT/" "$WORK/svn/trunk/"

# Ensure vendored gvm.js (gitignored) is published when present.
if [[ -f "$ROOT/assets/gvm.js" ]]; then
	mkdir -p "$WORK/svn/trunk/assets"
	cp "$ROOT/assets/gvm.js" "$WORK/svn/trunk/assets/gvm.js"
fi

echo "Adding files ..."
svn add --force "$WORK/svn/trunk" 2>/dev/null || true
svn stat "$WORK/svn" | awk '/^\?/{print $2}' | while read -r f; do
	svn add "$f" 2>/dev/null || true
done
svn stat "$WORK/svn" | awk '/^\!/{print $2}' | while read -r f; do
	svn rm "$f" 2>/dev/null || true
done

echo "Committing trunk ($VERSION) ..."
svn commit "$WORK/svn/trunk" -m "Release $VERSION"

# Tag the release.
if svn info "$WORK/svn/tags/$VERSION" >/dev/null 2>&1; then
	echo "Tag $VERSION already exists, skipping tag creation."
else
	echo "Creating tag $VERSION ..."
	svn copy "$SVN_URL/trunk" "$SVN_URL/tags/$VERSION" -m "Tag $VERSION"
fi

echo "Published $SLUG $VERSION to WordPress.org."
