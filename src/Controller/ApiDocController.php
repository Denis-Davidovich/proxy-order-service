<?php

namespace App\Controller;

use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[OA\Info(
    version: '1.0.0',
    title: 'Order Service API',
    description: 'Простое приложение для работы с заказами с поддержкой REST и SOAP эндпоинтов'
)]
#[OA\Server(
    url: 'http://localhost:8080',
    description: 'Development server'
)]
#[OA\Tag(name: 'Orders', description: 'Управление заказами')]
#[OA\Tag(name: 'Statistics', description: 'Статистика заказов')]
#[OA\Tag(name: 'Pricing', description: 'Получение цен на плитку')]
class ApiDocController extends AbstractController
{
    #[Route('/api/doc.json', name: 'api_doc_json', methods: ['GET'])]
    public function getOpenApiSpec(): JsonResponse
    {
        $projectDir = $this->getParameter('kernel.project_dir');

        $openapi = \OpenApi\Generator::scan([
            $projectDir . '/src/Controller/OrderRestController.php',
            $projectDir . '/src/Controller/OrderStatsController.php',
            $projectDir . '/src/Controller/ProxyTilePriceController.php',
            $projectDir . '/src/Controller/ApiDocController.php',
        ]);

        return new JsonResponse(
            json_decode($openapi->toJson(), true),
            Response::HTTP_OK
        );
    }

    #[Route('/api/doc', name: 'api_doc', methods: ['GET'])]
    public function showSwaggerUi(): Response
    {
        return $this->render('api_doc/swagger_ui.html.twig');
    }
}