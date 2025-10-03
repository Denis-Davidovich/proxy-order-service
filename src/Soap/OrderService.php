<?php

declare(strict_types=1);

namespace App\Soap;

use App\Service\OrderCreationService;

class OrderService
{
    public function __construct(
        private readonly OrderCreationService $orderCreationService
    ) {
    }

    /**
     * Creates a new order.
     *
     * @param string $product
     * @param int $quantity
     * @param string $address
     * @return object
     */
    public function createOrder(string $product, int $quantity, string $address): object
    {
        // Handle the quantity validation as expected by the SOAP interface
        if ($quantity <= 0) {
            return (object)[
                'success' => false,
                'orderId' => null,
                'message' => 'Quantity must be positive.',
            ];
        }

        // Convert SOAP parameters to REST-style data array
        $data = [
            'name' => $product,
            'description' => "Order for $quantity of \"$product\" to be shipped to \"$address\"",
            'client_name' => $address, // Using address as client name for simplicity
            'status' => 1 // Active status
        ];

        $result = $this->orderCreationService->createOrder($data);

        if ($result['success']) {
            return (object)[
                'success' => true,
                'orderId' => $result['data']['id'],
                'message' => sprintf(
                    'Order #%d for %d of "%s" to be shipped to "%s" has been created.',
                    $result['data']['id'],
                    $quantity,
                    $product,
                    $address
                ),
            ];
        } else {
            return (object)[
                'success' => false,
                'orderId' => null,
                'message' => $result['error'],
            ];
        }
    }
}