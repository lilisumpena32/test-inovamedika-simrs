#!/bin/sh
set -eu

APP_DIR=/var/www/simrs

mkdir -p "$APP_DIR/runtime" "$APP_DIR/web/assets"
chown -R www-data:www-data "$APP_DIR/runtime" "$APP_DIR/web/assets"

exec "$@"
