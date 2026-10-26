FROM php:8.5-cli
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libzip-dev libssl-dev libcurl4-openssl-dev \
    && docker-php-ext-install pdo_mysql \
    && pecl install mongodb redis \
    && docker-php-ext-enable mongodb redis \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
