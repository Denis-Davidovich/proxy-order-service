<?php

namespace App\Controller;

use App\Entity\Order;
use App\Service\OrderCreationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/api/v1/orders')]
class OrderRestController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OrderCreationService $orderCreationService
    )
    {
    }

    #[Route('', name: 'create_order_rest', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/orders',
        tags: ['Orders'],
        summary: 'Создание нового заказа',
        description: 'Создание нового заказа через REST API',
        requestBody: new OA\RequestBody(
            description: 'Данные для создания заказа',
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Заказ #123'),
                    new OA\Property(property: 'client_name', type: 'string', example: 'Иван Иванов'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'ivan@example.com'),
                    new OA\Property(property: 'description', type: 'string', example: 'Описание заказа')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Заказ успешно создан',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Order created successfully'),
                        new OA\Property(property: 'order_id', type: 'integer', example: 123)
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Ошибка валидации данных',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'error', type: 'string', example: 'Invalid data provided')
                    ]
                )
            )
        ]
    )]
    public function createOrder(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $result = $this->orderCreationService->createOrder($data);
        
        $statusCode = $result['statusCode'] ?? Response::HTTP_OK;
        unset($result['statusCode']);
        
        return $this->json($result, $statusCode);
    }

    #[Route('/{id}', name: 'get_order_by_id', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[OA\Get(
        path: '/api/v1/orders/{id}',
        tags: ['Orders'],
        summary: 'Получение заказа по ID',
        description: 'Получение информации о заказе по его идентификатору',
        parameters: [
            new OA\Parameter(name: 'id', description: 'ID заказа', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успешный ответ с данными заказа',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'Заказ #1'),
                                new OA\Property(property: 'client_name', type: 'string', example: 'Иван Иванов'),
                                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'ivan@example.com'),
                                new OA\Property(property: 'status', type: 'string', example: 'completed'),
                                new OA\Property(property: 'hash', type: 'string', example: 'abc123def456'),
                                new OA\Property(property: 'create_date', type: 'string', example: '2024-01-15 10:30:00'),
                                new OA\Property(property: 'update_date', type: 'string', example: '2024-01-15 10:30:00'),
                                new OA\Property(property: 'description', type: 'string', example: 'Описание заказа')
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Заказ не найден',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'error', type: 'string', example: 'Order not found')
                    ]
                )
            )
        ]
    )]
    public function getOrder(int $id): JsonResponse
    {
        $order = $this->entityManager->getRepository(Order::class)->find($id);

        if (!$order) {
            return $this->json([
                'success' => false,
                'error' => 'Order not found'
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'success' => true,
            'data' => [
                'id' => $order->getId(),
                'name' => $order->getName(),
                'client_name' => $order->getClientName(),
                'email' => $order->getEmail(),
                'status' => $order->getStatus(),
                'hash' => $order->getHash(),
                'create_date' => $order->getCreateDate()->format('Y-m-d H:i:s'),
                'update_date' => $order->getUpdateDate()?->format('Y-m-d H:i:s'),
                'description' => $order->getDescription(),
            ]
        ]);
    }
}