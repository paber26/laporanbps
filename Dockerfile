FROM php:8.3-fpm-alpine

# Instal utilitas dasar dan ekstensi PHP melalui extension installer resmi
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN apk add --no-cache \
    git \
    curl \
    zip \
    unzip \
    bash \
    nodejs \
    npm

RUN install-php-extensions \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    opcache

# Salin Composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Atur working directory
WORKDIR /var/www/html

# Salin script entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Salin kode aplikasi (jika image di-build tanpa mount)
COPY . /var/www/html

# Buat izin folder storage dan cache
RUN mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache && \
    chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
