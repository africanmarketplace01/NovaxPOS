#!/usr/bin/env bash
#
# NovaxSuites POS & Marketplace — backend deploy script.
#
# Usage:   ./deploy.sh
# Run from the Laravel project root on the server (or via your deploy tool).
# Safe to re-run. Does NOT route:cache (this app has intentional domain-separated
# duplicate route names, so `php artisan optimize` / route:cache must be avoided).
#
set -euo pipefail

echo "▶ NovaxSuites deploy starting..."

# 1) Dependencies (skip with SKIP_COMPOSER=1 if vendor/ is already deployed)
if [ "${SKIP_COMPOSER:-0}" != "1" ]; then
  echo "▶ composer install (production)"
  composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
fi

# 2) Maintenance mode (optional; comment out for zero-downtime setups)
php artisan down --render="errors::503" --retry=15 || true
trap 'php artisan up || true' EXIT

# 3) Clear stale caches BEFORE migrating (avoids stale config during migrate)
echo "▶ clearing caches"
php artisan optimize:clear

# 4) Storage symlink (idempotent — safe to run every time)
php artisan storage:link || true

# 5) Migrations
echo "▶ migrations"
php artisan landlord:migrate --force        # central / landlord tables
php artisan migrate --force                 # default connection (if used)
# Tenant migrations (uncomment if your workflow migrates all tenants on deploy):
# php artisan tenants:migrate --force

# 6) Rebuild caches — everything EXCEPT routes (route:cache is unsupported here)
echo "▶ rebuilding caches (config, events, views — NOT routes)"
php artisan config:cache
php artisan event:cache
php artisan view:cache

# 7) Restart the queue worker so it picks up new code
echo "▶ restarting queue workers"
php artisan queue:restart || true

# 8) Bring the app back up (also handled by the trap above)
php artisan up || true
trap - EXIT

echo "✅ Deploy complete."
echo "   Reminder: keep a queue worker running (queue:work) and the scheduler cron active."
