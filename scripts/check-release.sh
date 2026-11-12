#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
for binary in php composer node npm; do
    command -v "$binary" >/dev/null || { echo "Missing prerequisite: $binary" >&2; exit 1; }
done
if [ ! -f vendor/autoload.php ]; then
    echo 'Run composer install (including dev dependencies) in an isolated test workspace.' >&2
    exit 1
fi
composer check-platform-reqs
find app bootstrap config database/migrations routes tests -type f -name '*.php' -print0 | xargs -0 -n1 php -l
npm ci
npm run build
# Refuse cached application config: the test suite must use phpunit.xml's in-memory DB.
if [ -f bootstrap/cache/config.php ]; then
    echo 'Cached config found. Run php artisan config:clear in this TEST workspace, then retry.' >&2
    exit 1
fi
APP_ENV=testing php artisan test
APP_ENV=testing php artisan view:cache
APP_ENV=testing php artisan view:clear
APP_ENV=testing php artisan route:cache
APP_ENV=testing php artisan route:clear
printf '%s\n' 'Automated release checks passed. Browser, SMTP, HTTPS and production DB smoke checks still required.'
