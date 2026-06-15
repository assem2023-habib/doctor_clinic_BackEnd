FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    bzip2-dev \
    gmp-dev \
    oniguruma-dev \
    libxml2-dev \
    sqlite-dev \
    curl-dev \
    libxslt-dev \
    libsodium-dev \
    linux-headers \
    $PHPIZE_DEPS

RUN docker-php-ext-configure gd --with-freetype --with-jpeg

RUN docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    mysqli \
    mbstring \
    gd \
    intl \
    bcmath \
    zip \
    exif \
    sockets \
    sodium \
    bz2 \
    gmp \
    pdo_sqlite \
    soap \
    xsl \
    opcache

RUN pecl install redis && docker-php-ext-enable redis

COPY www.conf /usr/local/etc/php-fpm.d/www.conf
COPY php.ini /usr/local/etc/php/conf.d/zz-custom.ini

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
