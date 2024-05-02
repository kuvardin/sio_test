<?php

declare(strict_types=1);

namespace App\Api\v1;

// TODO: заменить на SortDirection
use App\Etl\Architecture\Enum\SortDirection;
use RuntimeException;

enum ApiSortDirection: string
{
    case ASC = 'ASC';
    case DESC = 'DESC';

    public static function make(string $sort_direction): ?self
    {
        return self::tryFrom(strtoupper($sort_direction));
    }

    public function getSortDirection(): SortDirection
    {
        return match ($this) {
            self::ASC => SortDirection::ASC,
            self::DESC => SortDirection::DESC,
            default => throw new RuntimeException("Unknown api sort direction: {$this->value}"),
        };
    }
}
