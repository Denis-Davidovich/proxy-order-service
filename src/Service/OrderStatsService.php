<?php

namespace App\Service;

use App\DTO\StatsQuery;
use App\Repository\OrderRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

readonly class OrderStatsService
{
    public function __construct(
        private OrderRepository $orderRepository,
        private CacheInterface  $cache
    )
    {
    }

    /**
     * Получить статистику заказов с пагинацией и группировкой
     */
    public function getStats(StatsQuery $query, Request $request): array
    {
        $cacheKey = sprintf(
            'order_stats_%s_p%d_pp%d',
            $query->groupBy,
            $query->page,
            $query->perPage
        );

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($query, $request) {
            $item->expiresAfter(300); // 5 минут кэша

            $offset = ($query->page - 1) * $query->perPage;
            $stats = $this->orderRepository->getStatsByPeriod(
                $query->groupBy,
                $offset,
                $query->perPage
            );

            $total = $this->orderRepository->countStatsByPeriod($query->groupBy);
            $totalPages = (int)ceil($total / $query->perPage);

            return [
                'success' => true,
                'pagination' => $this->buildPagination($query, $total, $totalPages, $request),
                'group_by' => $query->groupBy,
                'data' => $stats
            ];
        });
    }

    /**
     * Построить объект пагинации с HATEOAS links
     */
    private function buildPagination(StatsQuery $query, int $total, int $totalPages, Request $request): array
    {
        $baseUrl = $request->getSchemeAndHttpHost() . $request->getPathInfo();

        $buildLink = function (int $page) use ($baseUrl, $query): string {
            return sprintf(
                '%s?page=%d&per_page=%d&group_by=%s',
                $baseUrl,
                $page,
                $query->perPage,
                $query->groupBy
            );
        };

        $links = [
            'self' => $buildLink($query->page),
            'first' => $buildLink(1),
            'last' => $buildLink($totalPages),
        ];

        if ($query->page > 1) {
            $links['prev'] = $buildLink($query->page - 1);
        }

        if ($query->page < $totalPages) {
            $links['next'] = $buildLink($query->page + 1);
        }

        return [
            'page' => $query->page,
            'per_page' => $query->perPage,
            'total_items' => $total,
            'total_pages' => $totalPages,
            'has_next' => $query->page < $totalPages,
            'has_prev' => $query->page > 1,
            'links' => $links
        ];
    }
}