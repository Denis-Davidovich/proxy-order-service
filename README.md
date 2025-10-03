# Order Service API

Простое приложение для работы с заказами с поддержкой REST и SOAP эндпоинтов, а также парсинг цен плитки с tile.expert.

## Требования

- Docker
- Docker Compose

## Установка и запуск

```bash
# Установка зависимостей и сборка
make install

# Запуск приложения
make up

# Юнит-тесты
make test

# E2E тесты (с реальными запросами к tile.expert)
make e2e
```

Приложение будет доступно по адресу: `http://localhost:8080`

Порт можно изменить в файле `.env`:
```
SERVER_PORT=8080
```

## Документация API

Проект включает автоматически сгенерированную документацию API с использованием NelmioApiDocBundle и OpenAPI 3.0.

### Доступ к документации

1. **Swagger UI** (интерактивная документация): `http://localhost:8080/api/doc`
2. **JSON формат** (для машинной обработки): `http://localhost:8080/api/doc.json`

### Особенности документации

- Документация генерируется автоматически из аннотаций в коде
- Включает примеры запросов и ответов
- Поддерживает интерактивное тестирование API
- Обновляется автоматически при изменении кода

## API Endpoints

### Эндпоинт №1 - Получение цены плитки с tile.expert

**GET** `/api/v1/endpoint-1`

Получение цены плитки в евро со страницы tile.expert.

**Параметры:**
- `factory` (string, required) - название фабрики
- `collection` (string, required) - название коллекции
- `article` (string, required) - артикул товара

**Пример запроса:**
```bash
curl "http://localhost:8080/api/v1/endpoint-1?factory=cobsa&collection=manual&article=manu7530bcbm-manualbaltic7-5x30"
```

**Пример ответа:**
```json
{
  "price": 38.99,
  "factory": "cobsa",
  "collection": "manual",
  "article": "manu7530bcbm-manualbaltic7-5x30"
}
```

**Источник данных:**
https://tile.expert/fr/tile/cobsa/manual/a/manu7530bcbm-manualbaltic7-5x30

**Дополнительные примеры:**

```bash
# Cobsa Manual White - €38.99
curl "http://localhost:8080/api/v1/endpoint-1?factory=cobsa&collection=manual&article=manu7530whbm-manualwhite7-5x30"

# Cobsa Manual Sky - €40.50
curl "http://localhost:8080/api/v1/endpoint-1?factory=cobsa&collection=manual&article=manu7515skbm-manualsky7-5x15"

# Ape Arts Turquoise - €20.99
curl "http://localhost:8080/api/v1/endpoint-1?factory=ape&collection=arts&article=370018271"

# Ape Arts Blue - €20.99
curl "http://localhost:8080/api/v1/endpoint-1?factory=ape&collection=arts&article=370018310"
```

**Обработка ошибок:**

Отсутствуют параметры (400):
```json
{
  "success": false,
  "error": "Missing required parameters: factory, collection, article"
}
```

Товар не найден (500):
```json
{
  "success": false,
  "error": "Failed to fetch price: HTTP error: 404"
}
```

### Эндпоинт №2 - Статистика с пагинацией

**GET** `/api/v1/orders/stats`

Получение статистики заказов с группировкой и пагинацией.

**Параметры:**
- `page` (int, default: 1) - номер страницы
- `per_page` (int, default: 10) - количество элементов на странице
- `group_by` (string, default: month) - группировка (day|month|year)

**Пример запроса:**
```
GET /api/v1/orders/stats?page=1&per_page=10&group_by=month
```

**Пример ответа:**
```json
{
  "success": true,
  "pagination": {
    "page": 1,
    "per_page": 10,
    "total_items": 10,
    "total_pages": 1,
    "has_next": false,
    "has_prev": false
  },
  "group_by": "month",
  "data": [
    {
      "period": "2024-01",
      "count": 15
    }
  ]
}
```

### Эндпоинт №3 - Создание заказа через SOAP

**POST** `/api/v1/soap/orders`

Создание заказа через SOAP запрос.

**Headers:**
```
Content-Type: text/xml
```

**Пример SOAP запроса:**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
    <soap:Body>
        <CreateOrder>
            <Customer>Иван Иванов</Customer>
            <Amount>1500</Amount>
        </CreateOrder>
    </soap:Body>
</soap:Envelope>
```

**Пример ответа:**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
    <soap:Body>
        <CreateOrderResponse>
            <OrderId>12345</OrderId>
            <Status>created</Status>
            <Message>Order created successfully</Message>
        </CreateOrderResponse>
    </soap:Body>
</soap:Envelope>
```

### Эндпоинт №4 - Получение заказа по ID

**GET** `/api/v1/orders/{id}`

Получение одного заказа по ID.

**Пример запроса:**
```
GET /api/v1/orders/1
```

**Пример ответа:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "customer": "Иван Иванов",
    "amount": 1500,
    "date": "2024-01-15",
    "status": "completed"
  }
}
```

**Ошибка (404):**
```json
{
  "success": false,
  "error": "Order not found"
}
```