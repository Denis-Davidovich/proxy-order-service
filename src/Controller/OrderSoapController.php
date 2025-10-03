<?php

declare(strict_types=1);

namespace App\Controller;

use App\Soap\OrderService;
use SoapServer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

class OrderSoapController extends AbstractController
{
    private OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    #[Route('/api/v1/soap/order', name: 'soap_order_server')]
    #[OA\Post(
        summary: 'Создание заказа через SOAP',
        description: 'Создание заказа через SOAP запрос',
        parameters: [
            new OA\Parameter(name: 'wsdl', description: 'Получить WSDL файл', in: 'query', required: false, schema: new OA\Schema(type: 'boolean'))
        ],
        requestBody: new OA\RequestBody(
            description: 'SOAP XML запрос',
            content: new OA\MediaType(
                mediaType: 'text/xml',
                schema: new OA\Schema(
                    type: 'string',
                    example: '<?xml version="1.0" encoding="UTF-8"?><soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body><CreateOrder><product>Test Product</product><quantity>5</quantity><address>Test Address</address></CreateOrder></soap:Body></soap:Envelope>'
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'SOAP ответ',
                content: new OA\MediaType(
                    mediaType: 'text/xml',
                    schema: new OA\Schema(
                        type: 'string',
                        example: '<?xml version="1.0" encoding="UTF-8"?><soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body><CreateOrderResponse><OrderId>12345</OrderId><Status>created</Status><Message>Order created successfully</Message></CreateOrderResponse></soap:Body></soap:Envelope>'
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: 'WSDL файл не найден'
            )
        ]
    )]
    public function server(Request $request): Response
    {
        $wsdlPath = $this->getParameter('kernel.project_dir') . '/public/wsdl/orders.wsdl';

        if (!file_exists($wsdlPath)) {
            // This check is important for the test helper to know it needs to generate the file.
            throw $this->createNotFoundException('WSDL file not found. Please generate it first by running "bin/console app:generate-wsdl".');
        }

        // Handle WSDL request
        if ($request->query->has('wsdl')) {
            return new Response(file_get_contents($wsdlPath), 200, ['Content-Type' => 'application/xml']);
        }

        // Handle SOAP request
        // Using the local file path is more reliable, especially in test environments.
        $soapServer = new SoapServer($wsdlPath, ['cache_wsdl' => WSDL_CACHE_NONE]);
        $soapServer->setObject($this->orderService);

        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml; charset=ISO-8859-1');

        // Capture SOAP output using output buffering
        ob_start();
        try {
            $soapServer->handle($request->getContent());
            $response->setContent(ob_get_clean());
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return $response;
    }
}