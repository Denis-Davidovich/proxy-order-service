<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class OrderRestControllerTest extends WebTestCase
{
    /**
     * Тест успешного создания заказа
     */
    public function testCreateOrderSuccess(): void
    {
        $client = static::createClient();

        $orderData = [
            'name' => 'Test Order ' . time(),
            'client_name' => 'Иван Иванов',
            'email' => 'test@example.com',
            'description' => 'Test order description',
            'status' => 1
        ];

        $client->request(
            'POST',
            '/api/v1/orders',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($orderData)
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('id', $response['data']);
        $this->assertEquals($orderData['name'], $response['data']['name']);
        $this->assertEquals($orderData['client_name'], $response['data']['client_name']);
        $this->assertEquals($orderData['email'], $response['data']['email']);
        $this->assertEquals($orderData['status'], $response['data']['status']);
        $this->assertArrayHasKey('hash', $response['data']);
        $this->assertArrayHasKey('create_date', $response['data']);
    }

    /**
     * Тест создания заказа с минимальным набором полей
     */
    public function testCreateOrderMinimalData(): void
    {
        $client = static::createClient();

        $orderData = [
            'name' => 'Minimal Order ' . time(),
        ];

        $client->request(
            'POST',
            '/api/v1/orders',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($orderData)
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertTrue($response['success']);
        $this->assertEquals($orderData['name'], $response['data']['name']);
        $this->assertNull($response['data']['client_name']);
        $this->assertNull($response['data']['email']);
    }

    /**
     * Тест ошибки при отсутствии обязательного поля name
     */
    public function testCreateOrderMissingName(): void
    {
        $client = static::createClient();

        $orderData = [
            'client_name' => 'Иван Иванов',
            'email' => 'test@example.com',
        ];

        $client->request(
            'POST',
            '/api/v1/orders',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($orderData)
        );

        $this->assertResponseStatusCodeSame(400);

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertFalse($response['success']);
        $this->assertArrayHasKey('error', $response);
        $this->assertStringContainsString('Missing required field: name', $response['error']);
    }

    /**
     * Тест ошибки при пустом значении name
     */
    public function testCreateOrderEmptyName(): void
    {
        $client = static::createClient();

        $orderData = [
            'name' => '',
            'client_name' => 'Иван Иванов',
        ];

        $client->request(
            'POST',
            '/api/v1/orders',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($orderData)
        );

        $this->assertResponseStatusCodeSame(400);

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertFalse($response['success']);
        $this->assertStringContainsString('Missing required field: name', $response['error']);
    }

    /**
     * Тест ошибки при невалидном email
     */
    public function testCreateOrderInvalidEmail(): void
    {
        $client = static::createClient();

        $orderData = [
            'name' => 'Test Order',
            'email' => 'invalid-email',
        ];

        $client->request(
            'POST',
            '/api/v1/orders',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($orderData)
        );

        $this->assertResponseStatusCodeSame(400);

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertFalse($response['success']);
        $this->assertStringContainsString('Invalid email format', $response['error']);
    }

    /**
     * Тест ошибки при невалидном JSON
     */
    public function testCreateOrderInvalidJson(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/v1/orders',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{invalid json'
        );

        $this->assertResponseStatusCodeSame(400);

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($response['success']);
    }

    /**
     * Тест получения заказа по ID (Эндпоинт №4)
     */
    public function testGetOrderById(): void
    {
        $client = static::createClient();

        // Сначала создаем заказ
        $orderData = [
            'name' => 'Test Order for Get',
            'client_name' => 'Test Client',
            'email' => 'test@example.com',
        ];

        $client->request(
            'POST',
            '/api/v1/orders',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($orderData)
        );

        $createResponse = json_decode($client->getResponse()->getContent(), true);
        $orderId = $createResponse['data']['id'];

        // Теперь получаем созданный заказ
        $client->request('GET', '/api/v1/orders/' . $orderId);

        $this->assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success']);
        $this->assertEquals($orderId, $response['data']['id']);
        $this->assertEquals('Test Order for Get', $response['data']['name']);
        $this->assertEquals('Test Client', $response['data']['client_name']);
        $this->assertEquals('test@example.com', $response['data']['email']);
        $this->assertArrayHasKey('hash', $response['data']);
        $this->assertArrayHasKey('create_date', $response['data']);
    }

    /**
     * Тест получения несуществующего заказа
     */
    public function testGetOrderByIdNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/orders/999999');

        $this->assertResponseStatusCodeSame(404);

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($response['success']);
        $this->assertEquals('Order not found', $response['error']);
    }
}
