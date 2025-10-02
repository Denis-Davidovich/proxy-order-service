<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json([
            'service' => 'Order Service API',
            'description' => 'Простой сервис для работы с заказами (REST + SOAP)',
            'base_url' => 'http://localhost:8080',
            'endpoints' => [
                [
                    'number' => 1,
                    'method' => 'GET',
                    'path' => '/api/v1/endpoint-1',
                    'description' => 'Получение цены плитки с tile.expert',
                    'parameters' => [
                        'factory' => 'string (required) - название фабрики',
                        'collection' => 'string (required) - название коллекции',
                        'article' => 'string (required) - артикул товара'
                    ],
                    'example' => 'curl "http://localhost:8080/api/v1/endpoint-1?factory=cobsa&collection=manual&article=manu7530bcbm-manualbaltic7-5x30"'
                ],
                [
                    'number' => 2,
                    'method' => 'GET',
                    'path' => '/api/v1/orders/stats',
                    'description' => 'Статистика заказов с пагинацией и группировкой',
                    'parameters' => [
                        'page' => 'int (default: 1) - номер страницы',
                        'per_page' => 'int (default: 10) - количество на странице',
                        'group_by' => 'string (default: month) - группировка: day, month, year'
                    ],
                    'example' => 'curl "http://localhost:8080/api/v1/orders/stats?page=1&per_page=5&group_by=month"'
                ],
                [
                    'number' => 3,
                    'method' => 'POST',
                    'path' => '/api/v1/soap/orders',
                    'description' => 'Создание заказа через SOAP запрос',
                    'content_type' => 'text/xml',
                    'example' => 'curl -X POST http://localhost:8080/api/v1/soap/orders -H "Content-Type: text/xml" -d \'<?xml version="1.0"?><soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body><CreateOrder><Customer>Test</Customer></CreateOrder></soap:Body></soap:Envelope>\''
                ],
                [
                    'number' => 4,
                    'method' => 'GET',
                    'path' => '/api/v1/orders/{id}',
                    'description' => 'Получение одного заказа по ID',
                    'example' => 'curl http://localhost:8080/api/v1/orders/1'
                ]
            ],
            'management' => [
                'install' => 'make install',
                'start' => 'make up',
                'test' => 'make test'
            ],
            'configuration' => [
                'port' => 'Настраивается через переменную SERVER_PORT в .env файле'
            ]
        ]);
    }
}