<?php

namespace App\Tests\Fixtures;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory as FakerFactory;

/**
 * Генератор тестовых данных для заказов
 */
class OrderFixtures
{
    /**
     * Генерация тестовых заказов с различными датами для тестирования группировки
     *
     * @param EntityManagerInterface $entityManager
     * @param int $count Общее количество заказов
     */
    public static function generateOrders(EntityManagerInterface $entityManager, int $count = 100): void
    {
        $faker = FakerFactory::create('ru_RU');

        // Генерируем заказы за последние 4 года с разным распределением по месяцам
        $distributions = [
            // 2021
            '2021-01' => 45, '2021-02' => 38, '2021-03' => 52, '2021-04' => 61,
            '2021-05' => 73, '2021-06' => 58, '2021-07' => 67, '2021-08' => 71,
            '2021-09' => 63, '2021-10' => 69, '2021-11' => 55, '2021-12' => 48,
            // 2022
            '2022-01' => 82, '2022-02' => 91, '2022-03' => 88, '2022-04' => 95,
            '2022-05' => 102, '2022-06' => 89, '2022-07' => 97, '2022-08' => 105,
            '2022-09' => 93, '2022-10' => 99, '2022-11' => 87, '2022-12' => 78,
            // 2023
            '2023-01' => 115, '2023-02' => 123, '2023-03' => 118, '2023-04' => 131,
            '2023-05' => 127, '2023-06' => 119, '2023-07' => 125, '2023-08' => 129,
            '2023-09' => 122, '2023-10' => 120, '2023-11' => 116, '2023-12' => 112,
            // 2024
            '2024-01' => 135, '2024-02' => 143, '2024-03' => 138, '2024-04' => 151,
            '2024-05' => 147, '2024-06' => 139, '2024-07' => 145, '2024-08' => 149,
            '2024-09' => 142, '2024-10' => 140, '2024-11' => 136, '2024-12' => 132,
        ];

        $statuses = [1, 2, 3, 4, 5]; // различные статусы заказов

        foreach ($distributions as $period => $orderCount) {
            [$year, $month] = explode('-', $period);

            for ($i = 0; $i < $orderCount; $i++) {
                $day = rand(1, 28); // Используем до 28 дня для простоты
                $hour = rand(0, 23);
                $minute = rand(0, 59);
                $second = rand(0, 59);

                $dateTime = new \DateTime(sprintf(
                    '%s-%s-%02d %02d:%02d:%02d',
                    $year,
                    $month,
                    $day,
                    $hour,
                    $minute,
                    $second
                ));

                $clientName = $faker->name();
                $companyName = $faker->company();
                $orderType = $faker->randomElement(['товар', 'услуга', 'консультация', 'проект']);

                $order = new Order();
                $order->setName(sprintf('Заказ "%s" от %s', $orderType, $companyName));
                $order->setClientName($clientName);
                $order->setEmail($faker->safeEmail());
                $order->setStatus($statuses[array_rand($statuses)]);
                $order->setDescription($faker->sentence(rand(5, 15)));

                // Устанавливаем дату через Reflection, так как createDate readonly
                $reflection = new \ReflectionClass($order);
                $property = $reflection->getProperty('createDate');
                $property->setAccessible(true);
                $property->setValue($order, $dateTime);

                $entityManager->persist($order);

                // Flush каждые 100 записей для оптимизации памяти
                if (($i + 1) % 100 === 0) {
                    $entityManager->flush();
                    $entityManager->clear();
                }
            }
        }

        $entityManager->flush();
        $entityManager->clear();
    }

    /**
     * Очистка всех заказов из БД
     */
    public static function clearOrders(EntityManagerInterface $entityManager): void
    {
        $connection = $entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();

        // Отключаем проверку внешних ключей
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');

        // Очищаем таблицу
        $connection->executeStatement($platform->getTruncateTableSQL('orders', true));

        // Включаем проверку внешних ключей обратно
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
    }
}
