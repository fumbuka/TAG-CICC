#!/usr/bin/env bash
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PUBLIC_HTML_DIR="${1:-"$APP_DIR/../public_html"}"

if [ ! -d "$PUBLIC_HTML_DIR" ]; then
    echo "Public HTML directory not found: $PUBLIC_HTML_DIR" >&2
    echo "Usage: bash scripts/hostinger-sync-public-assets.sh /path/to/public_html" >&2
    exit 1
fi

mkdir -p "$PUBLIC_HTML_DIR/build"
cp -a "$APP_DIR/public/build/." "$PUBLIC_HTML_DIR/build/"

if [ -d "$APP_DIR/public/images" ]; then
    mkdir -p "$PUBLIC_HTML_DIR/images"
    cp -a "$APP_DIR/public/images/." "$PUBLIC_HTML_DIR/images/"
fi

for file in favicon.ico robots.txt; do
    if [ -f "$APP_DIR/public/$file" ]; then
        cp -a "$APP_DIR/public/$file" "$PUBLIC_HTML_DIR/$file"
    fi
done

echo "Public assets synced to $PUBLIC_HTML_DIR"
