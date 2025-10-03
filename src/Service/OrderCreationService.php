<?php

namespace App\Service;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

readonly class OrderCreationService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Create a new order from provided data
     *
     * @param array $data
     * @return array
     */
    public function createOrder(array $data): array
    {
        // Validate required fields
        if (empty($data['name'])) {
            return [
                'success' => false,
                'error' => 'Missing required field: name',
                'statusCode' => Response::HTTP_BAD_REQUEST
            ];
        }

        $order = new Order();
        $order->setName($data['name']);

        if (isset($data['client_name'])) {
            $order->setClientName($data['client_name']);
        }

        if (isset($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return [
                    'success' => false,
                    'error' => 'Invalid email format',
                    'statusCode' => Response::HTTP_BAD_REQUEST
                ];
            }
            $order->setEmail($data['email']);
        }

        if (isset($data['description'])) {
            $order->setDescription($data['description']);
        }

        if (isset($data['status'])) {
            $order->setStatus((int)$data['status']);
        }

        try {
            $this->entityManager->persist($order);
            $this->entityManager->flush();

            return [
                'success' => true,
                'data' => [
                    'id' => $order->getId(),
                    'name' => $order->getName(),
                    'client_name' => $order->getClientName(),
                    'email' => $order->getEmail(),
                    'status' => $order->getStatus(),
                    'hash' => $order->getHash(),
                    'create_date' => $order->getCreateDate()->format('Y-m-d H:i:s'),
                    'description' => $order->getDescription(),
                ],
                'message' => 'Order created successfully',
                'statusCode' => Response::HTTP_CREATED
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to create order: ' . $e->getMessage(),
                'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR
            ];
        }
    }
}