<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Тест для проверки генерации API документации через Nelmio API Doc
 */
class ApiDocTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Тест доступности Swagger JSON документации
     */
    public function testSwaggerJsonEndpoint(): void
    {
        $this->client->request('GET', '/api/doc.json');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $content = $this->client->getResponse()->getContent();
        $this->assertJson($content);

        $data = json_decode($content, true);

        // Проверяем основные поля OpenAPI спецификации
        $this->assertArrayHasKey('openapi', $data);
        $this->assertArrayHasKey('info', $data);
        $this->assertArrayHasKey('paths', $data);

        // Проверяем информацию о API
        $this->assertEquals('Order Service API', $data['info']['title']);
        $this->assertEquals('1.0.0', $data['info']['version']);

        // Проверяем наличие наших эндпоинтов
        $this->assertArrayHasKey('/api/v1/orders/stats', $data['paths']);
        $this->assertArrayHasKey('/api/v1/orders', $data['paths']);
        $this->assertArrayHasKey('/api/v1/orders/{id}', $data['paths']);
    }

    /**
     * Тест доступности Swagger UI
     */
    public function testSwaggerUiEndpoint(): void
    {
        $this->client->request('GET', '/api/doc');

        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();

        // Проверяем что это HTML страница со Swagger UI
        $this->assertStringContainsString('<!DOCTYPE html>', $content);
        $this->assertStringContainsString('swagger-ui', $content);
    }

    /**
     * Тест документации эндпоинта статистики заказов
     */
    public function testOrdersStatsEndpointDocumentation(): void
    {
        $this->client->request('GET', '/api/doc.json');
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $statsPath = $data['paths']['/api/v1/orders/stats'];

        // Проверяем наличие GET метода
        $this->assertArrayHasKey('get', $statsPath);

        $getMethod = $statsPath['get'];

        // Проверяем summary и description
        $this->assertArrayHasKey('summary', $getMethod);
        $this->assertArrayHasKey('description', $getMethod);

        // Проверяем параметры запроса
        $this->assertArrayHasKey('parameters', $getMethod);
        $parameters = $getMethod['parameters'];

        $parameterNames = array_column($parameters, 'name');
        $this->assertContains('page', $parameterNames);
        $this->assertContains('per_page', $parameterNames);
        $this->assertContains('group_by', $parameterNames);

        // Проверяем описание responses
        $this->assertArrayHasKey('responses', $getMethod);
        $this->assertArrayHasKey('200', $getMethod['responses']);
        $this->assertArrayHasKey('400', $getMethod['responses']);
    }

    /**
     * Тест документации эндпоинта создания заказа
     */
    public function testCreateOrderEndpointDocumentation(): void
    {
        $this->client->request('GET', '/api/doc.json');
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $ordersPath = $data['paths']['/api/v1/orders'];

        // Проверяем наличие POST метода
        $this->assertArrayHasKey('post', $ordersPath);

        $postMethod = $ordersPath['post'];

        // Проверяем requestBody
        $this->assertArrayHasKey('requestBody', $postMethod);
        $this->assertArrayHasKey('content', $postMethod['requestBody']);
        $this->assertArrayHasKey('application/json', $postMethod['requestBody']['content']);

        // Проверяем responses
        $this->assertArrayHasKey('responses', $postMethod);
        $this->assertArrayHasKey('201', $postMethod['responses']);
        $this->assertArrayHasKey('400', $postMethod['responses']);
    }

    /**
     * Тест что все эндпоинты задокументированы
     */
    public function testAllEndpointsAreDocumented(): void
    {
        $this->client->request('GET', '/api/doc.json');
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $expectedEndpoints = [
            '/api/v1/orders/stats',
            '/api/v1/orders',
            '/api/v1/orders/{id}',
            '/api/v1/endpoint-1',
        ];

        foreach ($expectedEndpoints as $endpoint) {
            $this->assertArrayHasKey(
                $endpoint,
                $data['paths'],
                "Endpoint $endpoint должен быть задокументирован в OpenAPI спецификации"
            );
        }
    }

    /**
     * Тест валидности JSON Schema
     */
    public function testOpenApiSchemaIsValid(): void
    {
        $this->client->request('GET', '/api/doc.json');
        $data = json_decode($this->client->getResponse()->getContent(), true);

        // Проверяем версию OpenAPI
        $this->assertMatchesRegularExpression('/^3\.\d+\.\d+$/', $data['openapi']);

        // Проверяем обязательные поля
        $this->assertIsArray($data['info']);
        $this->assertIsArray($data['paths']);

        // Проверяем структуру info
        $this->assertArrayHasKey('title', $data['info']);
        $this->assertArrayHasKey('version', $data['info']);

        // Проверяем что все paths содержат валидные HTTP методы
        $validMethods = ['get', 'post', 'put', 'patch', 'delete', 'options', 'head', 'trace'];
        foreach ($data['paths'] as $path => $methods) {
            $this->assertIsArray($methods);
            foreach ($methods as $method => $details) {
                $this->assertContains(
                    strtolower($method),
                    $validMethods,
                    "Метод $method в пути $path должен быть валидным HTTP методом"
                );
            }
        }
    }
}