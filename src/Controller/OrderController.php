<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1')]
class OrderController extends AbstractController
{
    /**
     * Эндпоинт №2 - GET запрос с пагинацией и группировкой
     * Параметры: page, per_page, group_by (day|month|year)
     */
    #[Route('/orders/stats', name: 'orders_stats', methods: ['GET'])]
    public function getOrderStats(Request $request): JsonResponse
    {
        $page = (int)$request->query->get('page', 1);
        $perPage = (int)$request->query->get('per_page', 10);
        $groupBy = $request->query->get('group_by', 'month');

        // Тестовые данные - группировка заказов
        $allStats = [
            ['period' => '2024-01', 'count' => 15],
            ['period' => '2024-02', 'count' => 23],
            ['period' => '2024-03', 'count' => 18],
            ['period' => '2024-04', 'count' => 31],
            ['period' => '2024-05', 'count' => 27],
            ['period' => '2024-06', 'count' => 19],
            ['period' => '2024-07', 'count' => 25],
            ['period' => '2024-08', 'count' => 29],
            ['period' => '2024-09', 'count' => 22],
            ['period' => '2024-10', 'count' => 20],
        ];

        $total = count($allStats);
        $totalPages = (int)ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;
        $stats = array_slice($allStats, $offset, $perPage);

        return $this->json([
            'success' => true,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total_items' => $total,
                'total_pages' => $totalPages,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1
            ],
            'group_by' => $groupBy,
            'data' => $stats
        ]);
    }

    /**
     * Эндпоинт №4 - получение одного заказа по ID
     */
    #[Route('/orders/{id}', name: 'order_get', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getOrder(int $id): JsonResponse
    {
        // Тестовые данные
        $orders = [
            1 => ['id' => 1, 'customer' => 'Иван Иванов', 'amount' => 1500, 'date' => '2024-01-15', 'status' => 'completed'],
            2 => ['id' => 2, 'customer' => 'Петр Петров', 'amount' => 2300, 'date' => '2024-02-20', 'status' => 'pending'],
            3 => ['id' => 3, 'customer' => 'Сидор Сидоров', 'amount' => 1800, 'date' => '2024-03-10', 'status' => 'completed'],
        ];

        if (!isset($orders[$id])) {
            return $this->json([
                'success' => false,
                'error' => 'Order not found'
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'success' => true,
            'data' => $orders[$id]
        ]);
    }

    /**
     * Эндпоинт №3 - SOAP запрос для создания заказа
     */
    #[Route('/soap/orders', name: 'order_create_soap', methods: ['POST'])]
    public function createOrderSoap(Request $request): Response
    {
        $contentType = $request->headers->get('Content-Type', '');

        if (strpos($contentType, 'text/xml') === false && strpos($contentType, 'application/soap+xml') === false) {
            return $this->json(['error' => 'Expected SOAP/XML request'], Response::HTTP_BAD_REQUEST);
        }

        $soapRequest = $request->getContent();

        // Парсим SOAP запрос
        $xml = simplexml_load_string($soapRequest);
        if ($xml === false) {
            return $this->json(['error' => 'Invalid XML'], Response::HTTP_BAD_REQUEST);
        }

        // Простая заглушка - возвращаем SOAP ответ
        $responseXml = '<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
    <soap:Body>
        <CreateOrderResponse>
            <OrderId>12345</OrderId>
            <Status>created</Status>
            <Message>Order created successfully</Message>
        </CreateOrderResponse>
    </soap:Body>
</soap:Envelope>';

        return new Response($responseXml, Response::HTTP_OK, [
            'Content-Type' => 'text/xml; charset=utf-8'
        ]);
    }
}