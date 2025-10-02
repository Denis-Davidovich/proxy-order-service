<?php

namespace App\Tests\Controller;

use App\Service\TileExpertPriceParser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProxyTilePriceControllerTest extends WebTestCase
{
    public function testGetPriceMissingParameters(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/endpoint-1');

        $this->assertResponseStatusCodeSame(400);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('Missing required parameters', $data['error']);
    }

    public function testGetPriceMissingFactory(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/endpoint-1?collection=manual&article=test');

        $this->assertResponseStatusCodeSame(400);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['success']);
    }

    public function testGetPriceWithValidParameters(): void
    {
        $client = static::createClient();

        // Мокируем сервис парсера
        $priceParserMock = $this->createMock(TileExpertPriceParser::class);
        $priceParserMock
            ->method('getPrice')
            ->willReturn([
                'price' => 38.99,
                'factory' => 'cobsa',
                'collection' => 'manual',
                'article' => 'manu7530bcbm-manualbaltic7-5x30',
            ]);

        // Заменяем сервис в контейнере на мок
        $client->getContainer()->set(TileExpertPriceParser::class, $priceParserMock);

        $client->request(
            'GET',
            '/api/v1/endpoint-1?factory=cobsa&collection=manual&article=manu7530bcbm-manualbaltic7-5x30'
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(38.99, $data['price']);
        $this->assertEquals('cobsa', $data['factory']);
        $this->assertEquals('manual', $data['collection']);
        $this->assertEquals('manu7530bcbm-manualbaltic7-5x30', $data['article']);
    }

    public function testGetPriceServiceException(): void
    {
        $client = static::createClient();

        // Мокируем сервис с исключением
        $priceParserMock = $this->createMock(TileExpertPriceParser::class);
        $priceParserMock
            ->method('getPrice')
            ->willThrowException(new \Exception('Failed to fetch price'));

        $client->getContainer()->set(TileExpertPriceParser::class, $priceParserMock);

        $client->request(
            'GET',
            '/api/v1/endpoint-1?factory=invalid&collection=invalid&article=invalid'
        );

        $this->assertResponseStatusCodeSame(500);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('Failed to fetch price', $data['error']);
    }
}