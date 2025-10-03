<?php

namespace App\Controller;

use App\Service\TileExpertPriceParser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;

#[Route('/api/v1')]
class ProxyTilePriceController extends AbstractController
{
    public function __construct(
        private readonly TileExpertPriceParser $priceParser
    )
    {
    }

    /**
     * Получение цены плитки с tile.expert
     *
     * GET /api/v1/endpoint-1?factory=cobsa&collection=manual&article=manu7530bcbm-manualbaltic7-5x30
     */
    #[Route('/endpoint-1', name: 'tile_price', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/endpoint-1',
        tags: ['Pricing'],
        summary: 'Получение цены плитки с tile.expert',
        description: 'Получение цены плитки в евро со страницы tile.expert по указанным параметрам',
        parameters: [
            new OA\Parameter(name: 'factory', description: 'Название фабрики', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'collection', description: 'Название коллекции', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'article', description: 'Артикул товара', in: 'query', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успешный ответ с ценой',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'price', type: 'number', format: 'float', example: 38.99),
                        new OA\Property(property: 'factory', type: 'string', example: 'cobsa'),
                        new OA\Property(property: 'collection', type: 'string', example: 'manual'),
                        new OA\Property(property: 'article', type: 'string', example: 'manu7530bcbm-manualbaltic7-5x30')
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Отсутствуют обязательные параметры',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'error', type: 'string', example: 'Missing required parameters: factory, collection, article')
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Ошибка при получении цены',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'error', type: 'string', example: 'Failed to fetch price: HTTP error: 404')
                    ]
                )
            )
        ]
    )]
    public function getPrice(Request $request): JsonResponse
    {
        $factory = $request->query->get('factory');
        $collection = $request->query->get('collection');
        $article = $request->query->get('article');

        // Валидация параметров
        if (!$factory || !$collection || !$article) {
            return $this->json([
                'success' => false,
                'error' => 'Missing required parameters: factory, collection, article'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->priceParser->getPrice($factory, $collection, $article);

            return $this->json($result);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}