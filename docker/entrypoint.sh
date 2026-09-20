#!/bin/sh
set -e

echo "==> [Entrypoint] Inisialisasi lingkungan Laporan BPS container..."

# Pastikan direktori framework Laravel tersedia dan memiliki izin tulis
mkdir -p /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs \
         /var/www/html/storage/app/public \
         /var/www/html/bootstrap/cache

chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache

# Jika .env belum ada, buat dari .env.example
if [ ! -f /var/www/html/.env ]; then
    echo "==> [Entrypoint] .env tidak ditemukan, membuat dari .env.example..."
    cp /var/www/html/.env.example /var/www/html/.env
fi

# Pastikan dependensi composer terpasang jika vendor belum ada
if [ ! -d /var/www/html/vendor ]; then
    echo "==> [Entrypoint] Direktori vendor belum ada, menjalankan composer install..."
    composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
fi

# Generate APP_KEY jika belum ada
if ! grep -q "APP_KEY=base64:" /var/www/html/.env; then
    echo "==> [Entrypoint] Membuat APP_KEY baru..."
    php artisan key:generate --force
fi

# Tunggu database MySQL siap jika DB_HOST dikonfigurasi
if [ -n "$DB_HOST" ]; then
    echo "==> [Entrypoint] Menunggu database di $DB_HOST:${DB_PORT:-3306} siap..."
    max_tries=30
    count=0
    until php -r "try { new PDO('mysql:host=' . getenv('DB_HOST') . ';port=' . (getenv('DB_PORT') ?: '3306'), getenv('DB_USERNAME'), getenv('DB_PASSWORD')); exit(0); } catch (Exception \$e) { exit(1); }" 2>/dev/null; do
        count=$((count + 1))
        if [ $count -ge $max_tries ]; then
            echo "==> [Entrypoint] Warning: Timeout menunggu database, proses dilanjutkan..."
            break
        fi
        sleep 2
    done
    echo "==> [Entrypoint] Koneksi database berhasil!"

    # Jalankan migrasi
    echo "==> [Entrypoint] Menjalankan migrasi database..."
    php artisan migrate --force
fi

# Pastikan symlink storage terhubung
rm -f /var/www/html/public/storage
php artisan storage:link --force 2>/dev/null || true

# Jika build frontend belum ada, lakukan build
if [ ! -d /var/www/html/public/build ]; then
    echo "==> [Entrypoint] public/build belum ada, menjalankan npm install & npm run build..."
    npm install
    npm run build
fi

echo "==> [Entrypoint] Container siap! Menjalankan: $@"
exec "$@"
