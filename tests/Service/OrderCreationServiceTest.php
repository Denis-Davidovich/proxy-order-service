<?php

namespace App\Tests\Service;

use App\Entity\Order;
use App\Service\OrderCreationService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class OrderCreationServiceTest extends TestCase
{
    private $entityManager;
    private $orderRepository;
    private $orderCreationService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->orderRepository = $this->createMock(EntityRepository::class);
        
        // Configure EntityManager to return our mock repository
        $this->entityManager
            ->method('getRepository')
            ->with(Order::class)
            ->willReturn($this->orderRepository);
            
        $this->orderCreationService = new OrderCreationService($this->entityManager);
    }

    public function testCreateOrderWithMissingName()
    {
        $data = [];
        $result = $this->orderCreationService->createOrder($data);
        
        $this->assertFalse($result['success']);
        $this->assertEquals('Missing required field: name', $result['error']);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $result['statusCode']);
    }

    public function testCreateOrderWithInvalidEmail()
    {
        $data = [
            'name' => 'Test Order',
            'email' => 'invalid-email'
        ];
        
        $result = $this->orderCreationService->createOrder($data);
        
        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid email format', $result['error']);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $result['statusCode']);
    }

    public function testCreateOrderSuccessfully()
    {
        $data = [
            'name' => 'Test Order',
            'client_name' => 'John Doe',
            'email' => 'john@example.com',
            'description' => 'Test description',
            'status' => 2
        ];
        
        // Mock the EntityManager persistence methods
        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Order::class));
            
        $this->entityManager
            ->expects($this->once())
            ->method('flush');
        
        $result = $this->orderCreationService->createOrder($data);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('Order created successfully', $result['message']);
        $this->assertEquals(Response::HTTP_CREATED, $result['statusCode']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('Test Order', $result['data']['name']);
        $this->assertEquals('John Doe', $result['data']['client_name']);
        $this->assertEquals('john@example.com', $result['data']['email']);
        $this->assertEquals('Test description', $result['data']['description']);
        $this->assertEquals(2, $result['data']['status']);
    }
}