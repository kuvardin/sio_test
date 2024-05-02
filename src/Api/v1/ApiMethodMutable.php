<?php

declare(strict_types=1);

namespace App\Api\v1;

use App\Api\ApiEnvironment;
use App\Api\ApiSession;
use App\Api\v1\Exceptions\ApiException;
use App\Api\v1\Input\ApiInput;

abstract class ApiMethodMutable extends ApiMethod
{
    final public static function isMutable(): bool
    {
        return true;
    }

    /**
     * @throws ApiException
     */
    abstract public static function handle(
        ApiEnvironment $environment,
        ApiInput $input,
        ApiSession $session,
    );
}
