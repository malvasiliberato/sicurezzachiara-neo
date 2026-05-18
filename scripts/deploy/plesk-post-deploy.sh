#!/usr/bin/env bash

set -Eeuo pipefail

APP_ROOT="${1:-$(pwd)}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
NPM_BIN="${NPM_BIN:-npm}"
NPM_CI_FLAGS="${NPM_CI_FLAGS:---legacy-peer-deps}"
RUN_MIGRATIONS="${RUN_MIGRATIONS:-0}"
RUN_STORAGE_LINK="${RUN_STORAGE_LINK:-1}"
PLESK_PHP_BIN="${PLESK_PHP_BIN:-/opt/plesk/php/8.3/bin/php}"

cd "${APP_ROOT}"

if [[ ! -f artisan ]]; then
  echo "artisan non trovato in ${APP_ROOT}"
  exit 1
fi

if ! command -v "${PHP_BIN}" >/dev/null 2>&1; then
  if [[ -x "${PLESK_PHP_BIN}" ]]; then
    PHP_BIN="${PLESK_PHP_BIN}"
  else
    echo "php non disponibile in PATH e fallback Plesk non trovato: ${PLESK_PHP_BIN}"
    exit 1
  fi
fi

if ! command -v "${COMPOSER_BIN}" >/dev/null 2>&1; then
  echo "composer non disponibile in PATH"
  exit 1
fi

export COMPOSER_ALLOW_SUPERUSER="${COMPOSER_ALLOW_SUPERUSER:-1}"

echo "==> Laravel optimize:clear"
"${PHP_BIN}" artisan optimize:clear || true

echo "==> Composer install"
"${COMPOSER_BIN}" install --no-interaction --no-dev --prefer-dist --optimize-autoloader

if command -v "${NPM_BIN}" >/dev/null 2>&1 && [[ -f package-lock.json ]]; then
  read -r -a NPM_CI_ARGS <<< "${NPM_CI_FLAGS}"

  echo "==> npm ci ${NPM_CI_FLAGS}"
  "${NPM_BIN}" ci "${NPM_CI_ARGS[@]}"

  echo "==> npm run build"
  "${NPM_BIN}" run build
else
  echo "==> npm o package-lock.json non disponibili, build frontend saltata"
fi

if [[ "${RUN_MIGRATIONS}" == "1" ]]; then
  echo "==> Artisan migrate --force"
  "${PHP_BIN}" artisan migrate --force
else
  echo "==> Migration remote non eseguite dal post-deploy script"
  echo "==> Eseguire manualmente: ${PHP_BIN} artisan migrate --force"
fi

if [[ "${RUN_STORAGE_LINK}" == "1" && ! -L public/storage && ! -e public/storage ]]; then
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
