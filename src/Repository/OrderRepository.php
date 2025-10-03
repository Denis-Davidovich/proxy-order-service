<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * Получить статистику заказов по периодам с пагинацией
     *
     * @param string $groupBy Период группировки: 'day', 'month', 'year'
     * @param int $offset Смещение для пагинации
     * @param int $limit Количество записей
     * @return array<int, array{period: string, count: int}>
     */
    public function getStatsByPeriod(string $groupBy, int $offset, int $limit): array
    {
        $dateFormat = match($groupBy) {
            'day' => '%Y-%m-%d',
            'month' => '%Y-%m',
            'year' => '%Y',
        };

        // DQL не поддерживает DATE_FORMAT, используем native query с ResultSetMapping
        $rsm = new \Doctrine\ORM\Query\ResultSetMapping();
        $rsm->addScalarResult('period', 'period');
        $rsm->addScalarResult('count', 'count', 'integer');

        $sql = "
            SELECT
                DATE_FORMAT(create_date, '$dateFormat') as period,
                COUNT(*) as count
            FROM orders
            GROUP BY period
            ORDER BY period DESC
            LIMIT :limit OFFSET :offset
        ";

        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $query->setParameter('limit', $limit);
        $query->setParameter('offset', $offset);

        return $query->getResult();
    }

    /**
     * Подсчитать общее количество периодов для пагинации
     *
     * @param string $groupBy Период группировки: 'day', 'month', 'year'
     * @return int
     */
    public function countStatsByPeriod(string $groupBy): int
    {
        $dateFormat = match($groupBy) {
            'day' => '%Y-%m-%d',
            'month' => '%Y-%m',
            'year' => '%Y',
        };

        $connection = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT COUNT(DISTINCT DATE_FORMAT(create_date, '$dateFormat'))
            FROM orders
        ";

        // DBAL 4: fetchOne() может вернуть null вместо false
        return (int) ($connection->fetchOne($sql) ?? 0);
    }

    /**
     * Получить заказы по периоду с лимитом
     *
     * @param string $period Период в формате 'YYYY-MM-DD', 'YYYY-MM' или 'YYYY'
     * @param string $groupBy Тип группировки: 'day', 'month', 'year'
     * @param int $limit Максимальное количество заказов
     * @return array<int, Order>
     */
    public function getOrdersByPeriod(string $period, string $groupBy, int $limit = 5): array
    {
        $dateFormat = match($groupBy) {
            'day' => '%Y-%m-%d',
            'month' => '%Y-%m',
            'year' => '%Y',
        };

        $qb = $this->createQueryBuilder('o');
        $qb->where($qb->expr()->eq("DATE_FORMAT(o.createDate, '$dateFormat')", ':period'))
            ->setParameter('period', $period)
            ->orderBy('o.createDate', 'DESC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }
}