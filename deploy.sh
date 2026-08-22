#!/usr/bin/env bash
# Deploy de isubj (Laravel) a Hostinger.
# Requiere: llave SSH ya autorizada en la cuenta de Hostinger (sin password).
#
# NO corre migraciones en general (hay un backlog de migraciones antiguas rotas/pendientes
# que rompen un `migrate` normal), NO sube vendor/, NO corre composer (ni local ni remoto).
# Las migraciones nuevas que sí se necesitan en el servidor se corren una por una con --path.
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
ssh -p "${REMOTE_PORT}" "${REMOTE_USER}@${REMOTE_HOST}" "cd ${REMOTE_PATH} \
  && ${REMOTE_PHP} \$(which composer) require rap2hpoutre/laravel-log-viewer:v3.1.0 --no-interaction --no-scripts \
  && ${REMOTE_PHP} \$(which composer) require laravel/sanctum --no-interaction --no-scripts \
  && ${REMOTE_PHP} artisan migrate --path=database/migrations/2026_07_18_165913_create_settings_table.php --force \
  && ${REMOTE_PHP} artisan migrate --path=database/migrations/2026_08_08_180000_add_surcharge_to_payment_orders_table.php --force \
  && ${REMOTE_PHP} artisan migrate --path=database/migrations/2026_08_22_192859_create_personal_access_tokens_table.php --force \
  && ${REMOTE_PHP} artisan filament:cache-components \
  && ${REMOTE_PHP} artisan config:cache \
  && ${REMOTE_PHP} artisan route:cache \
  && ${REMOTE_PHP} artisan view:cache"

echo "==> IMPORTANTE: agrega esta línea al .env remoto (no se sube por rsync) antes de este deploy:"
echo "    STUDENT_APP_CORS_ORIGINS=https://estudiantes.isubj.com"

echo "==> Deploy completo."





