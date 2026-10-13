#!/usr/bin/env bash
# Copies the Supertext SDK (PHP) into lib/supertext-sdk/, pinned to a tag or commit of
# https://github.com/Supertext/Supertext-API-SDK-and-Guide. Vendored until the package is on Packagist;
# then require supertext/supertext-sdk in composer.json and drop the folder (keep registration.php's
# autoloader for app/code installs, or bundle it in the zip).
#   tools/sync-supertext-sdk.sh [ref]     (default: main)
set -euo pipefail
ref="${1:-main}"
root="$(cd "$(dirname "$0")/.." && pwd)"
dest="$root/lib/supertext-sdk"
tmp="$(mktemp -d)"
trap 'rm -rf "$tmp"' EXIT
git clone -q https://github.com/Supertext/Supertext-API-SDK-and-Guide "$tmp/sdk"
git -C "$tmp/sdk" checkout -q "$ref"
commit="$(git -C "$tmp/sdk" rev-parse HEAD)"
# All SDKs of the repository share one version number; the TypeScript package carries it.
version="$(php -r 'echo json_decode(file_get_contents($argv[1]), true)["version"] ?? "";' "$tmp/sdk/sdk/typescript/package.json")"
rm -rf "$dest"
mkdir -p "$dest"
cp -R "$tmp/sdk/sdk/php/src" "$dest/src"
cp "$tmp/sdk/sdk/php/autoload.php" "$tmp/sdk/sdk/php/LICENSE" "$dest/"
cat > "$dest/VERSION.md" <<MD
# Vendored Supertext SDK

- Package: supertext/supertext-sdk (PHP) $version
- Source: https://github.com/Supertext/Supertext-API-SDK-and-Guide/tree/$commit/sdk/php
- Commit: $commit

Loaded by \`registration.php\` (\`autoload.php\`), so it works for Composer and app/code installs alike.
Don't edit these files: fix the SDK upstream, then run \`tools/sync-supertext-sdk.sh <tag or commit>\`.
MD
echo "Vendored supertext/supertext-sdk $version ($commit) into lib/supertext-sdk/"
