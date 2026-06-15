#!/bin/sh
set -e

# Re-create storage symlink for Linux container
if [ ! -L "public/storage" ]; then
    rm -rf public/storage
    php artisan storage:link --force 2>/dev/null || \
    ln -sf ../storage/app/public public/storage
fi

# Clear cache for new environment
php artisan optimize:clear 2>/dev/null || true

exec "$@"
