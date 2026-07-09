#!/bin/bash
# ═══════════════════════════════════════════════════════════════
# ADA Backend Auto-Deploy Script (ogni ~10 secondi)
# ═══════════════════════════════════════════════════════════════
# Il cron lo lancia ogni minuto. Internamente fa 6 check
# a intervalli di 10 secondi, ottenendo un deploy quasi istantaneo.
#
# Cron:
#   * * * * * /var/www/html/ADABackend/scripts/deploy.sh >> /var/www/html/ADABackend/storage/logs/deploy.log 2>&1
# ═══════════════════════════════════════════════════════════════

set -e

# ── Config ──
APP_DIR="/var/www/html/ADABackend"
BRANCH="feature/factoring"
PHP="/usr/bin/php"
LOCK_FILE="${APP_DIR}/storage/framework/deploy.lock"

# ── Lock: evita esecuzioni parallele ──
if [ -f "$LOCK_FILE" ]; then
    if [ "$(find "$LOCK_FILE" -mmin +2 2>/dev/null)" ]; then
        rm -f "$LOCK_FILE"
    else
        exit 0
    fi
fi

trap "rm -f $LOCK_FILE" EXIT
touch "$LOCK_FILE"

cd "$APP_DIR"

# ── Funzione deploy ──
do_deploy() {
    local LOG_PREFIX="[$(date '+%Y-%m-%d %H:%M:%S')]"

    git fetch origin "$BRANCH" --quiet 2>/dev/null

    LOCAL=$(git rev-parse HEAD)
    REMOTE=$(git rev-parse "origin/${BRANCH}")

    if [ "$LOCAL" = "$REMOTE" ]; then
        return 0
    fi

    echo "${LOG_PREFIX} 🚀 Nuovi commit! ${LOCAL:0:7} → ${REMOTE:0:7}"

    git pull origin "$BRANCH" --no-edit 2>&1

    echo "${LOG_PREFIX} 🔧 Post-deploy..."
    $PHP artisan migrate --force 2>&1 || true
    $PHP artisan config:clear 2>&1
    $PHP artisan route:clear 2>&1
    $PHP artisan view:clear 2>&1
    $PHP artisan cache:clear 2>&1
    $PHP artisan config:cache 2>&1 || true
    $PHP artisan route:cache 2>&1 || true
    $PHP artisan view:cache 2>&1 || true
    $PHP artisan storage:link 2>&1 || true

    echo "${LOG_PREFIX} ✅ Deploy OK → $(git rev-parse --short HEAD)"
    echo "───────────────────────────────────────"
}

# ── Loop: 6 check × 10 secondi = 60 secondi ──
for i in 1 2 3 4 5 6; do
    do_deploy
    [ "$i" -lt 6 ] && sleep 10
done
