#!/usr/bin/env sh
set -eu

PORT="${PORT:-8080}"
APP_DIR="/var/www/html"
BUNDLED_DB="${APP_DIR}/database/database.sqlite"
TARGET_DB="${DB_DATABASE:-$BUNDLED_DB}"

echo "[GuestLink] Booting production container on port ${PORT}..."

# 1. Configure Apache to listen on the cloud platform's dynamic $PORT (Render/Railway/Fly.io)
sed -ri "s/Listen [0-9]+/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

# 2. Prepare SQLite database cleanly without losing existing presentation data
DB_DIR="$(dirname "$TARGET_DB")"
mkdir -p "$DB_DIR"

if [ "$TARGET_DB" != "$BUNDLED_DB" ] && [ ! -f "$TARGET_DB" ] && [ -f "$BUNDLED_DB" ]; then
    echo "[GuestLink] Seeding persistent volume from bundled presentation SQLite database..."
    cp "$BUNDLED_DB" "$TARGET_DB"
elif [ ! -f "$TARGET_DB" ]; then
    echo "[GuestLink] Creating fresh SQLite database at ${TARGET_DB}..."
    touch "$TARGET_DB"
    NEEDS_SEED=1
fi

chown -R www-data:www-data "$DB_DIR" "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache" "${APP_DIR}/public/uploads" 2>/dev/null || true
chmod -R 775 "$DB_DIR" "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache" "${APP_DIR}/public/uploads" 2>/dev/null || true

# 3. Ensure APP_KEY is a valid Laravel AES-256-CBC key (handles Render generateValue safely)
APP_KEY="$(php -r '
$k = getenv("APP_KEY") ?: "";
if ($k === "") {
    echo "base64:" . base64_encode(random_bytes(32));
} elseif (str_starts_with($k, "base64:") && strlen((string) base64_decode(substr($k, 7), true)) === 32) {
    echo $k;
} elseif (strlen($k) === 32) {
    echo $k;
} else {
    echo "base64:" . base64_encode(hash("sha256", $k, true));
}
')"
export APP_KEY

# 4. Run non-destructive migrations & warm Laravel production caches
cd "$APP_DIR"
php artisan optimize:clear || true
php artisan migrate --force

if [ "${NEEDS_SEED:-0}" = "1" ]; then
    php artisan db:seed --force
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "[GuestLink] Ready. Starting Apache2 on 0.0.0.0:${PORT}..."
exec apache2-foreground
