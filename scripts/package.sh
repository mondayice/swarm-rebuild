#!/usr/bin/env bash
# Package the plugin for distribution: dist/swarm-rebuild-<version>.zip with
# the plugin files inside a swarm-rebuild/ folder (WordPress install format).
set -euo pipefail
cd "$(dirname "$0")/.."

version="$(grep -m1 '^ \* Version:' swarm-rebuild.php | awk '{print $3}')"
mkdir -p dist
out="dist/swarm-rebuild-${version}.zip"
git archive --prefix="swarm-rebuild/" -o "${out}" HEAD
echo "wrote ${out}"
