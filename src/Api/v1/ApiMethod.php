<?php

declare(strict_types=1);

namespace App\Api\v1;

use App\Api\v1\Input\ApiParameter;
use App\Api\v1\Output\ApiField;
use App\Localization\LocaleCode;

abstract class ApiMethod
{
    private function __construct()
    {
    }

    /**
     * @return ApiParameter[]
     */
    protected static function getParameters(): array
    {
        return [];
    }

    final public static function getAllParameters(LocaleCode $localeCode, bool $required = null): array
    {
        $parameters = static::getParameters();

        if (static::getSelectionOptions($localeCode) !== null) {
            $parameters = array_merge(ApiSelectionOptions::getApiParameters(), $parameters);
        }

        if ($required !== null) {
            $result = [];

            foreach ($parameters as $parameter_name => $parameter) {
                if ($parameter->isRequired() === $required) {
                    $result[$parameter_name] = $parameter;
                }
            }

            return $result;
        }

        return $parameters;
    }

    /**
     * @return ApiSelectionOptions|null Только если в методе используется пагинация
     */
    public static function getSelectionOptions(LocaleCode $locale_code): ?ApiSelectionOptions
    {
        return null;
    }

    public static function getDescription(): ?string
    {
        return null;
    }

    abstract public static function getResultField(): ?ApiField;

    abstract public static function isMutable(): bool;

    /**
     * Ограничение доступа по группам:
     * - null - без ограничений;
     * - [] - для авторизованных пользователей
     * - ['user', 'admin', ...] - для пользователей определенных групп
     *
     * @return string[]|null
     */
    public static function getAllowedGroups(): ?array
    {
        return null;
    }
}
