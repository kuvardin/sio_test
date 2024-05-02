<?php

declare(strict_types=1);

namespace App\Api\v1;

abstract class ApiModelImmutable extends ApiModel
{
    final public static function isMutable(): bool
    {
        return false;
    }

    /**
     * @return int Время жизни кэша в секундах
     */
    public static function getCacheTtl(): int
    {
        return 0;
    }

    abstract public function getPublicData(): array;
}
