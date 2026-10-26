#!/usr/bin/env bash
# Production build & optimisation for CareSenda. Run from the project root.
set -euo pipefail

composer install --no-dev --optimize-autoloader --classmap-authoritative --no-interaction
npm ci && npm run build

php artisan migrate --force
php artisan caresenda:import            # idempotent; preserves admin edits to prices/schedules

php artisan optimize                    # config, routes, events and views cached
php artisan storage:link 2>/dev/null || true

echo "CareSenda deployed."
