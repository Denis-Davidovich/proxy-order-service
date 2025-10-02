<?php

namespace App\Tests\E2E;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * E2E тесты для проверки реального парсинга цен с tile.expert
 * Эти тесты делают настоящие HTTP запросы к внешнему API
 */
class TilePriceE2ETest extends WebTestCase
{
    /**
     * Провайдер тестовых кейсов с реальными товарами
     *
     * @return array<string, array{factory: string, collection: string, article: string, expectedPrice: float, description: string, url: string}>
     */
    public static function realProductProvider(): array
    {
        return [
            'Cobsa Manual Baltic Blue 7.5x30' => [
                'factory' => 'cobsa',
                'collection' => 'manual',
                'article' => 'manu7530bcbm-manualbaltic7-5x30',
                'expectedPrice' => 34.58,
                'description' => 'Blue ceramic tile 7.5x30 cm',
                'originalUrl' => 'https://tile.expert/fr/tile/cobsa/manual/a/manu7530bcbm-manualbaltic7-5x30',
            ],
            'Cobsa Manual White 7.5x30' => [
                'factory' => 'cobsa',
                'collection' => 'manual',
                'article' => 'manu7530whbm-manualwhite7-5x30',
                'expectedPrice' => 31.85,
                'description' => 'White ceramic tile 7.5x30 cm',
                'originalUrl' => 'https://tile.expert/fr/tile/cobsa/manual/a/manu7530whbm-manualwhite7-5x30',
            ],
            'Cobsa Manual Sky Light Blue 7.5x15' => [
                'factory' => 'cobsa',
                'collection' => 'manual',
                'article' => 'manu7515skbm-manualsky7-5x15',
                'expectedPrice' => 36.40,
                'description' => 'Light blue ceramic tile 7.5x15 cm',
                'originalUrl' => 'https://tile.expert/fr/tile/cobsa/manual/a/manu7515skbm-manualsky7-5x15',
            ],
            'Ape Arts Turquoise 20x50' => [
                'factory' => 'ape',
                'collection' => 'arts',
                'article' => '370018271',
                'expectedPrice' => 26.60,
                'description' => 'Turquoise ceramic tile 20x50 cm',
                'originalUrl' => 'https://tile.expert/fr/tile/ape/arts/a/370018271',
            ],
            'Ape Arts Blue 20x50' => [
                'factory' => 'ape',
                'collection' => 'arts',
                'article' => '370018310',
                'expectedPrice' => 26.60,
                'description' => 'Blue ceramic tile 20x50 cm',
                'originalUrl' => 'https://tile.expert/fr/tile/ape/arts/a/370018310',
            ],
        ];
    }

    /**
     * Тест реального парсинга цен с tile.expert
     *
     * @dataProvider realProductProvider
     */
    public function testRealPriceFetching(
        string $factory,
        string $collection,
        string $article,
        float $expectedPrice,
        string $description,
        string $originalUrl
    ): void {
        $client = static::createClient();

        $apiUrl = sprintf(
            '/api/v1/endpoint-1?factory=%s&collection=%s&article=%s',
            urlencode($factory),
            urlencode($collection),
            urlencode($article)
        );

        $client->request('GET', $apiUrl);

        // Проверяем успешный ответ
        $this->assertResponseIsSuccessful(
            sprintf('Failed to fetch price for: %s (source: %s)', $description, $originalUrl)
        );

        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);

        // Проверяем структуру ответа
        $this->assertIsArray($data);
        $this->assertArrayHasKey('price', $data);
        $this->assertArrayHasKey('factory', $data);
        $this->assertArrayHasKey('collection', $data);
        $this->assertArrayHasKey('article', $data);

        // Проверяем корректность данных
        $this->assertEquals($expectedPrice, $data['price'], sprintf(
            'Price mismatch for %s. Expected: €%.2f, Got: €%.2f',
            $description,
            $expectedPrice,
            $data['price']
        ));

        $this->assertEquals($factory, $data['factory']);
        $this->assertEquals($collection, $data['collection']);
        $this->assertEquals($article, $data['article']);

        // Проверяем что цена в разумных пределах
        $this->assertGreaterThan(0, $data['price'], 'Price should be greater than 0');
        $this->assertLessThan(1000, $data['price'], 'Price should be less than 1000 EUR');
    }

    /**
     * Тест обработки несуществующего товара
     */
    public function testNonExistentProduct(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/endpoint-1?factory=invalid&collection=invalid&article=invalid-article-123');

        // Ожидаем ошибку 500 (Internal Server Error)
        $this->assertResponseStatusCodeSame(500);

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('Failed to fetch price', $data['error']);
    }

    /**
     * Тест отсутствующих параметров
     */
    public function testMissingParameters(): void
    {
        $client = static::createClient();

        // Тест без factory
        $client->request('GET', '/api/v1/endpoint-1?collection=manual&article=test');
        $this->assertResponseStatusCodeSame(400);

        // Тест без collection
        $client->request('GET', '/api/v1/endpoint-1?factory=cobsa&article=test');
        $this->assertResponseStatusCodeSame(400);

        // Тест без article
        $client->request('GET', '/api/v1/endpoint-1?factory=cobsa&collection=manual');
        $this->assertResponseStatusCodeSame(400);

        // Тест без всех параметров
        $client->request('GET', '/api/v1/endpoint-1');
        $this->assertResponseStatusCodeSame(400);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('Missing required parameters', $data['error']);
    }

    /**
     * Тест специальных символов в параметрах
     */
    public function testSpecialCharactersInParameters(): void
    {
        $client = static::createClient();

        // URL encoding должен корректно обрабатываться
        $client->request(
            'GET',
            '/api/v1/endpoint-1?factory=cobsa&collection=manual&article=' .
            urlencode('manu7530bcbm-manualbaltic7-5x30')
        );

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('manu7530bcbm-manualbaltic7-5x30', $data['article']);
    }
}