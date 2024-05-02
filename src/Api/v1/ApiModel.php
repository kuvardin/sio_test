<?php

declare(strict_types=1);

namespace App\Api\v1;

use App\Api\v1\Output\ApiField;

abstract class ApiModel
{
    public static function getDescription(): ?string
    {
        return null;
    }

    /**
     * @return ApiField[]
     */
    abstract public static function getFields(): array;

    final public static function getName(): string
    {
        $class_parts = explode('\\', static::class);
        return preg_replace('|ApiModel$|', '', $class_parts[count($class_parts) - 1]);
    }

    abstract public static function isMutable(): bool;
}
