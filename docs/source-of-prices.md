# Примеры использования API для получения цен плитки

## Структура URL на tile.expert

Структура ссылки на товар:
```
https://tile.expert/fr/tile/{factory}/{collection}/a/{article}
```

Где:
- `{factory}` - название фабрики (производителя)
- `{collection}` - название коллекции
- `{article}` - уникальный артикул товара

## Примеры фабрик (Manufacturers)

На сайте tile.expert доступно 156+ фабрик. Вот некоторые из них:

- cobsa
- fap-ceramiche
- ape
- cerdomus
- harmony
- tuscania
- ascot
- newker
- castelvetro
- mutina
- peronda

## Примеры запросов к API

### Пример 1: Cobsa - Manual - Baltic

**URL товара:**
```
https://tile.expert/fr/tile/cobsa/manual/a/manu7530bcbm-manualbaltic7-5x30
```

**API запрос:**
```bash
curl "http://localhost:8080/api/v1/endpoint-1?factory=cobsa&collection=manual&article=manu7530bcbm-manualbaltic7-5x30"
```

**Ожидаемый ответ:**
```json
{
  "price": 38.99,
  "factory": "cobsa",
  "collection": "manual",
  "article": "manu7530bcbm-manualbaltic7-5x30"
}
```

**Информация о товаре:**
- Название: Manual Baltic
- Размер: 7.5 × 30 cm
- Цвет: Blue
- Цена: €38.99 за м²

### Пример 2: Другие артикулы из коллекции Cobsa Manual

Коллекция Manual от Cobsa содержит 18 товаров с различными цветами (серый, зеленый, голубой, бежевый, белый) и размерами (7.5x30 см и 7.5x15 см).

Примеры ID артикулов в этой коллекции:
- 523816734
- 523816735
- 523816736 (Baltic - из примера выше)
- 523816737
- 523816744
- 523816750

### Пример 3: FAP Ceramiche

**Доступные коллекции FAP Ceramiche:**
- Roma
- Nuances
- Maku
- Lumina
- Color Line
- Mat&More
- Nest
- Sheer
- Milano Mood
- Glim

**Примерный ценовой диапазон:** от €28 до €55 за м²

## Структура артикула

Артикул обычно содержит информацию о товаре в виде кодированной строки:

Пример: `manu7530bcbm-manualbaltic7-5x30`

Возможная расшифровка:
- `manu` - префикс коллекции (manual)
- `7530` - размер (7.5x30 см)
- `bcbm` - возможно код цвета (baltic blue)
- `manualbaltic` - название товара
- `7-5x30` - размер с дефисом

## Особенности парсинга

Цена на странице товара извлекается из JSON данных, встроенных в HTML:

```javascript
{
  "priceEuroFr": "38,99",
  "priceMqEuroFr": "38,99"
}
```

Цена может отображаться в двух форматах:
- Обычная цена: €38.99 за м²
- Цена со скидкой: €35.99 за м² (при покупке определенного количества)

## Тестирование API

Для тестирования рекомендуется использовать проверенный пример:

```bash
# Проверенный рабочий пример
curl "http://localhost:8080/api/v1/endpoint-1?factory=cobsa&collection=manual&article=manu7530bcbm-manualbaltic7-5x30"

# Ожидаемый результат
{
  "price": 38.99,
  "factory": "cobsa",
  "collection": "manual",
  "article": "manu7530bcbm-manualbaltic7-5x30"
}
```

## Обработка ошибок

### Отсутствуют параметры
```bash
curl "http://localhost:8080/api/v1/endpoint-1"

# Ответ: 400 Bad Request
{
  "success": false,
  "error": "Missing required parameters: factory, collection, article"
}
```

### Товар не найден
```bash
curl "http://localhost:8080/api/v1/endpoint-1?factory=invalid&collection=invalid&article=invalid"

# Ответ: 404 Not found
{
  "success": false,
  "error": "Failed to fetch price: ..."
}
```