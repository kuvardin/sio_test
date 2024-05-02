<?php

declare(strict_types=1);

namespace App\Api\v1;

use App\Api\ApiEnvironment;
use App\Api\v1\Exceptions\ApiException;
use App\Api\v1\Input\ApiInput;

abstract class ApiMethodImmutable extends ApiMethod
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

    /**
     * @throws ApiException
     */
    abstract public static function handle(
        ApiEnvironment $environment,
        ApiInput $input,
    );
}
