<?php

declare(strict_types=1);

namespace App\Api\v1;

use App;
use App\Api\v1\Input\ApiParameter;
use App\Api\v1\Input\ApiParameterType;
use RuntimeException;

class ApiSelectionOptions
{
    public const FIELD_PAGE = 'page';
    public const FIELD_LIMIT = 'limit';
    public const FIELD_SORT = 'sort';

    /**
     * @var string Вариант поля сортировки по умолчанию
     */
    protected string $sort_by_default;

    /**
     * @var ApiSortDirection Направление сортировки по умолчанию
     */
    protected ApiSortDirection $sort_direction_default;

    /**
     * @var string[] Все варианты полей сортировки
     */
    protected array $sort_by_variants = [];

    /**
     * @var int|null Максимальный лимит элементов на страницу
     */
    protected ?int $limit_max;

    public function __construct(
        string $sort_by_default,
        ApiSortDirection $sort_direction_default,
        array $sort_by_variants = [],
    ) {
        $this->sort_by_default = $sort_by_default;

        $this->sort_direction_default = $sort_direction_default;

        foreach ($sort_by_variants as $sort_by_variant_alias => $sort_by_variant) {
            $this->addSortByVariant(
                $sort_by_variant,
                is_int($sort_by_variant_alias) ? null : $sort_by_variant_alias,
            );
        }

        if (!array_key_exists($sort_by_default, $this->sort_by_variants)) {
            $this->sort_by_variants[$sort_by_default] = $sort_by_default;
        }

        $this->setSortByDefault($sort_by_default);
        $this->limit_max = null;
    }

    public static function getApiParameters(): array
    {
        return [
            self::FIELD_PAGE => ApiParameter::scalar(ApiParameterType::Integer, null, 'Номер страницы'),
            self::FIELD_LIMIT => ApiParameter::scalar(
                ApiParameterType::Integer,
                null,
                'Количество элементов на одну страницу'
            ),
            self::FIELD_SORT => ApiParameter::map(ApiParameterType::String, null, 'Настройки сортировки'),
        ];
    }

    /**
     * Поле сортировки по умолчанию
     */
    public function getSortByDefault(): string
    {
        return $this->sort_by_default;
    }

    /**
     * Установка поля сортировки по умолчанию
     */
    public function setSortByDefault(string $sort_by_default): self
    {
        if (!in_array($sort_by_default, $this->sort_by_variants, true)) {
            throw new RuntimeException("Unknown ord variant: $sort_by_default");
        }

        $this->sort_by_default = $sort_by_default;
        return $this;
    }

    /**
     * Добавление варианта поля сортировки
     */
    public function addSortByVariant(string $variant, string $alias = null): self
    {
        $this->sort_by_variants[$alias ?? $variant] = $variant;
        return $this;
    }

    public function getSortByVariant(string $alias): ?string
    {
        return $this->sort_by_variants[$alias] ?? null;
    }

    /**
     * Все варианты полей сортировки
     *
     * @return string[]
     */
    public function getSortByVariants(): array
    {
        return $this->sort_by_variants;
    }

    public function setSortDirectionDefault(ApiSortDirection $sort_direction_default): self
    {
        $this->sort_direction_default = $sort_direction_default;
        return $this;
    }

    /**
     * Максимальный лимит элементов на страницу
     */
    public function getLimitMax(): ?int
    {
        return $this->limit_max;
    }

    /**
     * Максимальный лимит элементов на страницу отсюда либо из настроек
     */
    public function requireLimitMax(): int
    {
        return $this->limit_max ?? 100;
    }

    /**
     * Установка максимального лимита элементов на страницу
     */
    public function setLimitMax(?int $limit_max): self
    {
        if ($limit_max !== null && $limit_max <= 0) {
            throw new RuntimeException("Limit max must be positive number ($limit_max received)");
        }

        $this->limit_max = $limit_max;
        return $this;
    }

    public function getSortDirectionDefault(): ApiSortDirection
    {
        return $this->sort_direction_default;
    }
}
