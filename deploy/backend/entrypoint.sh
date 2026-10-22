#!/bin/sh
set -eu
# Railway mounts volumes at runtime as root, replacing build-time directory ownership.
mkdir -p "$(dirname "${CHITS_DB_PATH:-/data/chits.sqlite}")"
chown -R www-data:www-data "$(dirname "${CHITS_DB_PATH:-/data/chits.sqlite}")"
exec apache2-foreground
