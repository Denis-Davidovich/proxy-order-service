# Base stage - общие зависимости
FROM php:8.4-cli-alpine AS base

# Установка системных зависимостей и PDO MySQL SOAP
RUN apk add \
    mysql-client \
    libxml2-dev \
    && docker-php-ext-install pdo pdo_mysql soap

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Рабочая директория
WORKDIR /app

EXPOSE 8080

# Production stage
FROM base AS prod

# Копирование composer файлов
COPY composer.json composer.lock* ./

# Установка production зависимостей
RUN composer install --no-dev --optimize-autoloader --no-scripts --prefer-dist --no-progress --no-interaction

# Копирование всех файлов приложения
COPY . /app

# Очистка
RUN rm -rf var/cache/* var/log/* && \
    chown -R www-data:www-data /app/var

CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]

# Development stage - с xdebug
FROM base AS dev

# Установка зависимостей для сборки xdebug
RUN apk add --virtual .build-deps \
    $PHPIZE_DEPS \
    linux-headers && \
    pecl install xdebug && \
    docker-php-ext-enable xdebug && \
    apk del .build-deps

# Конфигурация xdebug
RUN echo "xdebug.mode=debug,coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.client_port=9003" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Копирование composer файлов
COPY composer.json composer.lock* ./

# Установка dev зависимостей
RUN composer install --prefer-dist --no-scripts --no-progress --no-interaction

# Копирование всех файлов приложения
COPY . /app

CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
