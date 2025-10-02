<?php

namespace App\Controller;

use App\Service\TileExpertPriceParser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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