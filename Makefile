# Установка зависимостей
install:
	docker-compose build
	docker-compose run --rm app composer install

# Запуск приложения
up:
	docker-compose up -d

# Остановка приложения
down:
	docker-compose down

# Логи приложения
logs:
	docker-compose logs -f app

# Запуск юнит-тестов
test:
	docker-compose exec -e APP_ENV=test app php vendor/bin/phpunit

# Запуск E2E тестов
e2e:
	docker-compose exec app php tests/generate-test-data.php
	docker-compose exec -e APP_ENV=test app php vendor/bin/phpunit tests/E2E

# Открытие документации API в браузере
doc:
	open http://localhost:8080/api/doc || echo "Откройте в браузере: http://localhost:8080/api/doc"

# Генерация WSDL для SOAP сервиса
wsdl:
	docker-compose exec app php bin/console app:generate-wsdl