<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TileExpertPriceParser
{
    private const string BASE_URL = 'https://tile.expert/fr/tile';
    private const int TIMEOUT = 10; // секунды

    public function __construct(
        private readonly HttpClientInterface $httpClient
    )
    {
    }

    /**
     * Получение цены товара с tile.expert
     *
     * @param string $factory Название фабрики
     * @param string $collection Название коллекции
     * @param string $article Артикул товара
     * @return array{price: float, factory: string, collection: string, article: string}
     * @throws \Exception
     */
    public function getPrice(string $factory, string $collection, string $article): array
    {
        $url = sprintf(
            '%s/%s/%s/a/%s',
            self::BASE_URL,
            $factory,
            $collection,
            $article
        );

        try {
            $response = $this->httpClient->request('GET', $url, [
                'timeout' => self::TIMEOUT,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                throw new \Exception(sprintf('HTTP error: %d', $statusCode));
            }

            $html = $response->getContent();

            // Ищем JSON данные в скриптах страницы
            $price = $this->extractPriceFromHtml($html);

            if ($price === null) {
                throw new \Exception('Price not found on the page');
            }

            return [
                'price' => $price,
                'factory' => $factory,
                'collection' => $collection,
                'article' => $article,
            ];
        } catch (TransportExceptionInterface $e) {
            throw new \Exception(
                sprintf('Network error: %s', $e->getMessage()),
                0,
                $e
            );
        } catch (\Exception $e) {
            throw new \Exception(
                sprintf('Failed to fetch price: %s', $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * Извлечение цены из HTML страницы
     *
     * @param string $html HTML содержимое страницы
     * @return float|null Цена в евро или null если не найдена
     */
    private function extractPriceFromHtml(string $html): ?float
    {
        // Метод 1: Поиск базовой цены в JSON-LD Schema.org структуре
        // Содержит точную цену конкретного товара
        if (preg_match('/<script type="application\/ld\+json">\s*({[^<]+})\s*<\/script>/', $html, $matches)) {
            $jsonData = json_decode($matches[1], true);
            if (isset($jsonData['offers']['price'])) {
                $priceStr = str_replace(',', '.', $jsonData['offers']['price']);
                return (float)$priceStr;
            }
        }

        // Метод 2: Поиск базовой цены через специфический контекст
        // Ищем priceEuroFr который идет первым в основном блоке товара
        if (preg_match_all('/priceEuroFr["\']?\s*:\s*["\']?(\d+(?:[,.]\d+)?)["\']?/', $html, $matches, PREG_SET_ORDER)) {
            // Берем предпоследнюю цену (обычно это базовая цена товара до скидки)
            if (count($matches) >= 5) {
                $priceStr = str_replace(',', '.', $matches[count($matches) - 4][1]);
                return (float)$priceStr;
            }
        }

        return null;
    }
}