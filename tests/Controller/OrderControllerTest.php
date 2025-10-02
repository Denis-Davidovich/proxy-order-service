<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class OrderControllerTest extends WebTestCase
{
    public function testGetOrders(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/orders');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertIsArray($data['data']);
        $this->assertGreaterThan(0, count($data['data']));
    }

    public function testGetOrderStats(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/orders/stats?page=1&per_page=5&group_by=month');

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals(1, $data['pagination']['page']);
        $this->assertEquals(5, $data['pagination']['per_page']);
        $this->assertEquals('month', $data['group_by']);
        $this->assertIsArray($data['data']);
    }

    public function testGetOrderById(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/orders/1');

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals(1, $data['data']['id']);
        $this->assertArrayHasKey('customer', $data['data']);
        $this->assertArrayHasKey('status', $data['data']);
    }

    public function testGetOrderByIdNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/orders/999');

        $this->assertResponseStatusCodeSame(404);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Order not found', $data['error']);
    }

    public function testCreateOrderSoap(): void
    {
        $client = static::createClient();

        $soapRequest = '<?xml version="1.0"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
    <soap:Body>
        <CreateOrder>
            <Customer>Test</Customer>
        </CreateOrder>
    </soap:Body>
</soap:Envelope>';

        $client->request(
            'POST',
            '/api/v1/soap/orders',
            [],
            [],
            ['CONTENT_TYPE' => 'text/xml'],
            $soapRequest
        );

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('CreateOrderResponse', $client->getResponse()->getContent());
        $this->assertStringContainsString('created', $client->getResponse()->getContent());
    }
}