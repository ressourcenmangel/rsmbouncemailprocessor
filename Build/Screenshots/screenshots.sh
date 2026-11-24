#!/usr/bin/env bash
# Creates the documentation screenshots with Playwright in Docker against the DDEV instance v14.
# Prepare the data first, see README.md.
set -euo pipefail
cd "$(dirname "$0")/../.."

PLAYWRIGHT_VERSION=1.63.0

# --network ddev_default: the DDEV router answers there for the project hostnames
docker run --rm --network ddev_default \
  -v "$PWD/Build/Screenshots/screenshots.mjs:/work/screenshots.mjs:ro" \
  -v "$PWD/Documentation/Images:/out" \
  -e BASE_URL -e BE_USER -e BE_PASSWORD -e OUT_DIR=/out \
  "mcr.microsoft.com/playwright:v${PLAYWRIGHT_VERSION}-noble" \
  bash -c "mkdir -p /tmp/pw && cd /tmp/pw && cp /work/screenshots.mjs . && npm init -y >/dev/null && npm i --silent playwright@${PLAYWRIGHT_VERSION} && node screenshots.mjs"
