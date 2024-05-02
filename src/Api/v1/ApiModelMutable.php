<?php

declare(strict_types=1);

namespace App\Api\v1;

use App\Api\ApiSession;

abstract class ApiModelMutable extends ApiModel
{
    final public static function isMutable(): bool
    {
        return true;
    }

    abstract public function getPublicData(ApiSession $session): array;
}
