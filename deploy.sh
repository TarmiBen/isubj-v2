#!/usr/bin/env bash
# Deploy de isubj (Laravel) a Hostinger.
# Requiere: llave SSH ya autorizada en la cuenta de Hostinger (sin password).
#
# NO ejecuta migraciones, NO sube vendor/, NO corre composer (ni local ni remoto).
# Se asume que vendor/ ya existe en el servidor y se mantiene tal cual.
set -euo pipefail
cd "$(dirname "$0")"

REMOTE_USER="u639191047"
REMOTE_HOST="195.179.239.54"
REMOTE_PORT="65002"
REMOTE_PATH="/home/u639191047/domains/isubj.com/public_html/sys"

echo "==> Compilando assets (vite)..."
npm install
npm run build

echo "==> Sincronizando código -> $REMOTE_HOST:$REMOTE_PATH"
rsync -avz \
  -e "ssh -p ${REMOTE_PORT}" \
  --exclude='.env' \
  --exclude='.git/' \
  --exclude='.idea/' \
  --exclude='node_modules/' \
  --exclude='vendor/' \
  --exclude='composer.lock' \
  --exclude='tests/' \
  --exclude='storage/logs/*' \
  --exclude='storage/app/public/*' \
  --exclude='storage/framework/cache/*' \
  --exclude='storage/framework/sessions/*' \
  --exclude='storage/framework/views/*' \
  --exclude='storage/framework/testing/*' \
  --exclude='public/storage' \
  ./ "${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_PATH}/"


echo "==> Ejecutando comandos remotos "
# El servidor sirve el sitio con PHP 8.4 (vía FPM), pero el 'php' del PATH en SSH resuelve a 8.2 (CLI del sistema) —
# usamos la ruta explícita para que coincida con la versión real del sitio.
REMOTE_PHP="/opt/alt/php84/usr/bin/php"
ssh -p "${REMOTE_PORT}" "${REMOTE_USER}@${REMOTE_HOST}" "cd ${REMOTE_PATH}  && ${REMOTE_PHP} artisan config:cache && ${REMOTE_PHP} artisan route:cache && ${REMOTE_PHP} artisan view:cache"

echo "==> Deploy completo."
