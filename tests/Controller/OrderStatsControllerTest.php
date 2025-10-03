<?php

namespace App\Tests\Controller;

use App\Tests\Fixtures\OrderFixtures;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class OrderStatsControllerTest extends WebTestCase
{
    /**
     * Данные будут создаваться через SQL при необходимости
     * Для упрощения используем встроенную тестовую БД
     */

    /**
     * Тест группировки по месяцам с пагинацией
     */
    public function testGetOrderStatsGroupByMonth(): void
    {
        $client = static::createClient();

        // Подготовка тестовых данных
        $em = $client->getContainer()->get('doctrine')->getManager();
        OrderFixtures::clearOrders($em);
        OrderFixtures::generateOrders($em, 257);

        $client->request('GET', '/api/v1/orders/stats?page=1&per_page=5&group_by=month');

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals(1, $data['pagination']['page']);
        $this->assertEquals(5, $data['pagination']['per_page']);
        $this->assertEquals('month', $data['group_by']);
        $this->assertIsArray($data['data']);
        $this->assertCount(5, $data['data']); // 5 элементов на странице
        $this->assertGreaterThan(0, $data['pagination']['total_items']);

        // Проверяем HATEOAS links
        $this->assertArrayHasKey('links', $data['pagination']);
        $this->assertArrayHasKey('self', $data['pagination']['links']);
        $this->assertArrayHasKey('first', $data['pagination']['links']);
        $this->assertArrayHasKey('last', $data['pagination']['links']);

        // Проверяем структуру данных
        foreach ($data['data'] as $stat) {
            $this->assertArrayHasKey('period', $stat);
            $this->assertArrayHasKey('count', $stat);
            $this->assertIsInt($stat['count']);
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}$/', $stat['period']); // формат YYYY-MM
        }
    }

    /**
     * Тест группировки по годам
     */
    public function testGetOrderStatsGroupByYear(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/orders/stats?page=1&per_page=10&group_by=year');

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('year', $data['group_by']);

        // Проверяем формат периода для года
        if (!empty($data['data'])) {
            $this->assertMatchesRegularExpression('/^\d{4}$/', $data['data'][0]['period']); // формат YYYY
        }
    }

    /**
     * Тест группировки по дням
     */
    public function testGetOrderStatsGroupByDay(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/orders/stats?page=1&per_page=10&group_by=day');

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('day', $data['group_by']);

        // Проверяем формат периода для дня
        if (!empty($data['data'])) {
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $data['data'][0]['period']); // формат YYYY-MM-DD
        }
    }

    /**
     * Тест валидации параметра group_by
     */
    public function testGetOrderStatsInvalidGroupBy(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/orders/stats?group_by=invalid');

        $this->assertResponseStatusCodeSame(400);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('errors', $data);
    }

    /**
     * Тест граничного случая: пустая БД
     */
    public function testGetOrderStatsEmptyDatabase(): void
    {
        $client = static::createClient();
        $em = $client->getContainer()->get('doctrine')->getManager();
        OrderFixtures::clearOrders($em);

        $client->request('GET', '/api/v1/orders/stats?page=1&per_page=10&group_by=month');

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEmpty($data['data']);
        $this->assertEquals(0, $data['pagination']['total_items']);
        $this->assertEquals(0, $data['pagination']['total_pages']);
    }

    /**
     * Тест граничного случая: page > total_pages
     */
    public function testGetOrderStatsPageOutOfBounds(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/orders/stats?page=999&per_page=10&group_by=month');

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEmpty($data['data']);
    }

    /**
     * Тест граничного случая: отрицательные значения
     */
    public function testGetOrderStatsNegativeValues(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/orders/stats?page=-1&per_page=-10&group_by=month');

        $this->assertResponseStatusCodeSame(400);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('errors', $data);
    }

    /**
     * Тест граничного случая: per_page > 100
     */
    public function testGetOrderStatsPerPageTooLarge(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/orders/stats?page=1&per_page=999&group_by=month');

        $this->assertResponseStatusCodeSame(400);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('errors', $data);
    }

    /**
     * Тест пагинации - вторая страница
     */
    public function testGetOrderStatsPaginationSecondPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/orders/stats?page=2&per_page=5&group_by=month');

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(2, $data['pagination']['page']);
        $this->assertTrue($data['pagination']['has_prev']);
    }
}