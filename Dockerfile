FROM php:8.4-cli-alpine

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Рабочая директория
WORKDIR /app

# Копирование файлов приложения
COPY . /app

# Установка зависимостей
RUN composer install --no-dev --optimize-autoloader --no-scripts

EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
