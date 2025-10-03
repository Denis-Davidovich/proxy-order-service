<?php

namespace App\Controller;

use App\DTO\StatsQuery;
use App\Service\OrderStatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

#[Route('/api/v1')]
class OrderStatsController extends AbstractController
{
    #[Route('/orders/stats', name: 'orders_stats', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/orders/stats',
        tags: ['Statistics'],
        summary: 'Статистика заказов с пагинацией и группировкой',
        description: 'Получение статистики заказов с группировкой по дням, месяцам или годам и с пагинацией',
        parameters: [
            new OA\Parameter(name: 'page', description: 'Номер страницы', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', description: 'Количество элементов на странице', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10)),
            new OA\Parameter(name: 'group_by', description: 'Группировка (day|month|year)', in: 'query', required: false, schema: new OA\Schema(type: 'string', default: 'month', enum: ['day', 'month', 'year']))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успешный ответ со статистикой',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'pagination',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'page', type: 'integer', example: 1),
                                new OA\Property(property: 'per_page', type: 'integer', example: 10),
                                new OA\Property(property: 'total_items', type: 'integer', example: 10),
                                new OA\Property(property: 'total_pages', type: 'integer', example: 1),
                                new OA\Property(property: 'has_next', type: 'boolean', example: false),
                                new OA\Property(property: 'has_prev', type: 'boolean', example: false)
                            ]
                        ),
                        new OA\Property(property: 'group_by', type: 'string', example: 'month'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'period', type: 'string', example: '2024-01'),
                                    new OA\Property(property: 'count', type: 'integer', example: 15)
                                ]
                            )
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Ошибка валидации параметров',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'errors', type: 'object')
                    ]
                )
            )
        ]
    )]
    public function getOrderStats(
        Request            $request,
        OrderStatsService  $statsService,
        ValidatorInterface $validator
    ): JsonResponse
    {
        $query = StatsQuery::fromArray($request->query->all());

        // Валидация входных данных
        $errors = $validator->validate($query);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }

            return $this->json([
                'success' => false,
                'errors' => $errorMessages
            ], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($statsService->getStats($query, $request));
    }
}