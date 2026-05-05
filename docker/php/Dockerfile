FROM node:22-alpine AS node_build

WORKDIR /app
COPY package.json package-lock.json vite.config.js ./
COPY resources ./resources
RUN npm ci --no-audit --no-fund
RUN npm run build

FROM composer:2 AS composer_deps

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress

FROM php:8.3-apache-bookworm AS app

WORKDIR /var/www/html

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN apt-get update \
  && apt-get install -y --no-install-recommends \
    fontconfig \
    fonts-dejavu-core \
    libicu-dev \
    libpq-dev \
    libzip-dev \
    wkhtmltopdf \
  && docker-php-ext-install -j"$(nproc)" \
    intl \
    mbstring \
    pdo_pgsql \
    zip \
  && a2enmod rewrite \
  && sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf \
  && sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
  && sed -ri -e 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf /etc/apache2/sites-available/000-default.conf \
  && rm -rf /var/lib/apt/lists/*

COPY --from=composer_deps /app/vendor /var/www/html/vendor
COPY --from=node_build /app/public/build /var/www/html/public/build

COPY . /var/www/html
COPY docker/php/entrypoint.sh /entrypoint.sh

RUN chmod +x /entrypoint.sh \
  && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

ENTRYPOINT ["/entrypoint.sh"]
CMD ["apache2-foreground"]
