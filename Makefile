.PHONY: install up test

install: ## Установить зависимости
	docker-compose build
	docker-compose run --rm app composer install

up: ## Запустить приложение
	docker-compose up -d

test: ## Запустить тесты
	@echo "Tests not configured yet"