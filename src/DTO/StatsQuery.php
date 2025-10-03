<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class StatsQuery
{
    #[Assert\Positive(message: 'Page must be positive')]
    public int $page = 1;

    #[Assert\Positive(message: 'Per page must be positive')]
    #[Assert\LessThanOrEqual(100, message: 'Per page cannot exceed 100')]
    public int $perPage = 10;

    #[Assert\Choice(
        choices: ['day', 'month', 'year'],
        message: 'Group by must be one of: {{ choices }}'
    )]
    public string $groupBy = 'month';

    public static function fromArray(array $data): self
    {
        $query = new self();
        $query->page = (int)($data['page'] ?? 1);
        $query->perPage = (int)($data['per_page'] ?? 10);
        $query->groupBy = $data['group_by'] ?? 'month';

        return $query;
    }
}