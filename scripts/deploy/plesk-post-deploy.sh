#!/usr/bin/env bash

set -Eeuo pipefail

APP_ROOT="${1:-$(pwd)}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
NPM_BIN="${NPM_BIN:-npm}"

cd "${APP_ROOT}"

if [[ ! -f artisan ]]; then
  echo "artisan non trovato in ${APP_ROOT}"
  exit 1
fi

echo "==> Laravel optimize:clear"
"${PHP_BIN}" artisan optimize:clear || true

echo "==> Composer install"
"${COMPOSER_BIN}" install --no-interaction --no-dev --prefer-dist --optimize-autoloader

if command -v "${NPM_BIN}" >/dev/null 2>&1; then
  echo "==> npm ci"
  "${NPM_BIN}" ci

  echo "==> npm run build"
  "${NPM_BIN}" run build
else
  echo "==> npm non disponibile, build frontend saltata"
fi

echo "==> Artisan migrate"
"${PHP_BIN}" artisan migrate --force

if [[ ! -L public/storage && ! -e public/storage ]]; then
  echo "==> Artisan storage:link"
  "${PHP_BIN}" artisan storage:link
fi

echo "==> Cache Laravel"
"${PHP_BIN}" artisan config:cache
"${PHP_BIN}" artisan route:cache
"${PHP_BIN}" artisan view:cache
"${PHP_BIN}" artisan event:cache

echo "==> Queue restart"
"${PHP_BIN}" artisan queue:restart || true

echo "Deploy post-actions completate"
