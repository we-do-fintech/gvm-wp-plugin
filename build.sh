#!/usr/bin/env bash
set -euo pipefail

# build.sh — vendor the GetViaMsg SDK into the plugin.
#
# The plugin loads gvm.js dynamically from esm.sh by default, so no SDK build is
# required. Running this script vendors a local copy of gvm.js into assets/ so the
# plugin can self-host it (assets/gvm.js is gitignored).
#
#   ./build.sh              Copy ../gvm-sdk/dist/gvm.js -> assets/gvm.js
#   ./build.sh <path>       Copy a specific gvm.js file -> assets/gvm.js
#   ./build.sh zip          Produce a clean installable zip in dist/
#   ./build.sh all          Vendor (if available) then zip

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DEFAULT_SRC="$ROOT/../gvm-sdk/dist/gvm.js"
SLUG="gvm-wp-plugin"
VERSION="$(grep -m1 'Version:' "$ROOT/gvm-wp.php" | sed -E 's/.*Version:[[:space:]]*//')"
VERSION="${VERSION:-0.1.0}"
DIST="$ROOT/dist"

vendor_sdk() {
	local src="${1:-$DEFAULT_SRC}"

	if [[ -f "$src" ]]; then
		mkdir -p "$ROOT/assets"
		cp "$src" "$ROOT/assets/gvm.js"
		echo "Vendored gvm.js from $src"
	else
		echo "gvm.js not found at $src" >&2
		echo "Build the gvm-sdk first (cd ../gvm-sdk && pnpm build), then re-run:" >&2
		echo "  $0 $DEFAULT_SRC" >&2
		echo "(Without a vendored copy the plugin will load gvm.js from esm.sh.)" >&2
	fi
}

build_zip() {
	mkdir -p "$DIST"
	local stage="$DIST/$SLUG"

	rm -rf "$stage"
	mkdir -p "$stage"

	rsync -a \
		--exclude '.git' \
		--exclude '.github' \
		--exclude 'node_modules' \
		--exclude 'vendor' \
		--exclude 'dist' \
		--exclude 'wordpress-env' \
		--exclude 'docs' \
		--exclude '*.zip' \
		--exclude '.DS_Store' \
		--exclude '.gitignore' \
		--exclude 'PLAN.md' \
		--exclude 'AGENTS.md' \
		--exclude 'build.sh' \
		--exclude 'publish.sh' \
		--exclude 'dev.sh' \
		"$ROOT/" "$stage/"

	# Ensure a vendored gvm.js (if any) is included even though it is gitignored.
	if [[ -f "$ROOT/assets/gvm.js" ]]; then
		mkdir -p "$stage/assets"
		cp "$ROOT/assets/gvm.js" "$stage/assets/gvm.js"
	fi

	rm -f "$DIST/${SLUG}-${VERSION}.zip"
	(cd "$DIST" && zip -qr "${SLUG}-${VERSION}.zip" "$SLUG")
	rm -rf "$stage"

	echo "Built $DIST/${SLUG}-${VERSION}.zip"
}

case "${1:-vendor}" in
	vendor) vendor_sdk "${2:-}" ;;
	zip) build_zip ;;
	all) vendor_sdk "${2:-}"; build_zip ;;
	*.js) vendor_sdk "$1" ;;
	*) echo "usage: $0 [vendor|zip|all] [path/to/gvm.js]" >&2; exit 1 ;;
esac
