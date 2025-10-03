#!/usr/bin/env php
<?php

/**
 * Генератор тестовых данных для заказов
 *
 * Использование:
 *   docker-compose exec app php tests/generate-test-data.php [количество]
 *
 * Пример:
 *   docker-compose exec app php tests/generate-test-data.php 257
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Tests\Fixtures\OrderFixtures;
use Symfony\Component\Dotenv\Dotenv;

// Загружаем переменные окружения
if (file_exists(__DIR__ . '/../.env')) {
    (new Dotenv())->bootEnv(__DIR__ . '/../.env');
}

// Создаем Symfony kernel
$kernel = new \App\Kernel($_SERVER['APP_ENV'] ?? 'dev', (bool) ($_SERVER['APP_DEBUG'] ?? true));
$kernel->boot();

// Получаем EntityManager
$container = $kernel->getContainer();
$entityManager = $container->get('doctrine')->getManager();

// Получаем количество заказов из аргументов
$count = isset($argv[1]) ? (int)$argv[1] : 257;

if ($count < 1 || $count > 10000) {
    echo "Ошибка: количество заказов должно быть от 1 до 10000\n";
    exit(1);
}

echo "Генерация $count тестовых заказов...\n";

try {
    // Очищаем существующие данные
    echo "Очистка таблицы orders...\n";
    OrderFixtures::clearOrders($entityManager);

    // Генерируем новые данные
    echo "Создание заказов...\n";
    $startTime = microtime(true);
    OrderFixtures::generateOrders($entityManager, $count);
    $duration = microtime(true) - $startTime;

    echo sprintf("✓ Успешно создано %d заказов за %.2f секунд\n", $count, $duration);

    // Показываем статистику
    $connection = $entityManager->getConnection();
    $stats = $connection->fetchAllAssociative(
        "SELECT DATE_FORMAT(create_date, '%Y-%m') as period, COUNT(*) as count
         FROM orders
         GROUP BY period
         ORDER BY period DESC
         LIMIT 100"
    );

    echo "\nСтатистика по месяцам:\n";
    echo str_repeat('-', 30) . "\n";
    foreach ($stats as $stat) {
        echo sprintf("  %s: %3d заказов\n", $stat['period'], $stat['count']);
    }
    echo str_repeat('-', 30) . "\n";
    echo "Всего: " . array_sum(array_column($stats, 'count')) . " заказов\n";

} catch (\Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
    exit(1);
}

$kernel->shutdown();
