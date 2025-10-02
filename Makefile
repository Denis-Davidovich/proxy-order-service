.PHONY: install up test e2e

install: ## Установить зависимости
	docker-compose build
	docker-compose run --rm app composer install

up: ## Запустить приложение
	docker-compose up -d

test: ## Запустить тесты
	docker-compose exec -e APP_ENV=test app php vendor/bin/phpunit

e2e: ## Запустить E2E тесты
	docker-compose exec -e APP_ENV=test app php vendor/bin/phpunit tests/E2E/