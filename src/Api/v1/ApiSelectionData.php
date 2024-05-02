<?php

declare(strict_types=1);

namespace App\Api\v1;

use Doctrine\ORM\QueryBuilder;
use RuntimeException;
use App\Aspans\PassportApi\Models\SelectionData as PassportSelectionData;

class ApiSelectionData
{
    protected ?int $page = null;

    /** @var array<string,string>  */
    protected array $sort_settings = [];
    public array $sort_by_variants = [];

    public function __construct(
        protected ?int $limit = null,
        public ?int $limit_max = null,
        protected ?int $offset = null,
        ?array $sort_by_variants = null,
        public ?int $total_amount = null,
    ) {
        $this->setLimit($this->limit);

        if (!empty($sort_by_variants)) {
            foreach ($sort_by_variants as $sort_by_variant_alias => $sort_by_variant) {
                if (is_int($sort_by_variant_alias)) {
                    $this->sort_by_variants[$sort_by_variant] = $sort_by_variant;
                } else {
                    $this->sort_by_variants[$sort_by_variant_alias] = $sort_by_variant;
                }
            }
        }
    }

    public function checkSortByAlias(string $alias): bool
    {
        return array_key_exists($alias, $this->sort_by_variants);
    }

    public function addSortSetting(string $sort_by_alias, ApiSortDirection $sort_direction): self
    {
        if (!array_key_exists($sort_by_alias, $this->sort_by_variants)) {
            throw new RuntimeException("Unknown sort by alias: $sort_by_alias");
        }

        $sort_by = $this->sort_by_variants[$sort_by_alias];
        $this->sort_settings[$sort_by] = $sort_direction->value;
        return $this;
    }

    public static function makeByPassportSelectionData(PassportSelectionData $passport_selection_data): self
    {
        $sort_by_variants = [];
        foreach ($passport_selection_data->sort_variants as $sort_variant) {
            $sort_by_variants[$sort_variant] = $sort_variant;
        }

        $result = new self(
            limit: $passport_selection_data->limit,
            sort_by_variants: $sort_by_variants,
            total_amount: $passport_selection_data->total_amount,
        );

        foreach ($passport_selection_data->sort as $sort_by => $sort_direction) {
            $sort_direction = strtoupper($sort_direction);
            $result->addSortSetting(
                $sort_by,
                ApiSortDirection::tryFrom($sort_direction),
            );
        }

        $result->setPage($passport_selection_data->page);

        return $result;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function setLimit(?int $limit, bool $no_limit = false): self
    {
        if ($limit !== null && $limit <= 0) {
            throw new RuntimeException("Incorrect limit: $limit");
        }

        if ($no_limit) {
            $this->limit = null;
        } elseif ($limit !== null && $this->limit_max !== null && $limit > $this->limit_max) {
            $this->limit = $this->limit_max;
        } else {
            $this->limit = $limit;
        }

        return $this;
    }

    public function getOffset(): ?int
    {
        return $this->offset;
    }

    public function setOffset(?int $offset): self
    {
        if ($offset !== null && $offset < 0) {
            throw new RuntimeException("Incorrect offset: $offset");
        }

        $this->offset = $offset;
        return $this;
    }

    public function getSortSettings(bool $with_aliases = false): array
    {
        if ($with_aliases) {
            $result = [];
            foreach ($this->sort_settings as $sort_by => $sort_direction) {
                $sort_by_alias = array_search($sort_by, $this->sort_by_variants);
                if ($sort_by_alias === false) {
                    throw new RuntimeException("Sort alias for $sort_by not found");
                }

                $result[$sort_by_alias] = $sort_direction;
            }

            return $result;
        }

        return $this->sort_settings;
    }

    public function bindQueryBuilder(QueryBuilder $qb, string $alias = null): void
    {
        foreach ($this->sort_settings as $sort_by => $sort_direction) {
            $qb->orderBy(
                $alias === null ? $sort_by : "$alias.$sort_by",
                $sort_direction,
            );
        }

        $qb->setFirstResult($this->offset);
        $qb->setMaxResults($this->limit);
    }

    public function getSortByFull(string $prefix = null): ?array
    {
        if ($this->sort_settings !== []) {
            if ($prefix === null) {
                return $this->sort_settings;
            }

            $result = [];
            foreach ($this->sort_settings as $sort_by => $sort_direction) {
                $result[$prefix . $sort_by] = $sort_direction;
            }

            return $result;
        }

        return null;
    }

    public function getPagesNumber(): int
    {
        if ($this->limit === null) {
            throw new RuntimeException('Limit must be not null');
        }

        if ($this->total_amount === null) {
            throw new RuntimeException('Total amount must not be null');
        }

        $result = (int)($this->total_amount / $this->limit);
        if ($this->total_amount % $this->limit) {
            $result++;
        }

        return $result;
    }

    public function getPage(): ?int
    {
        return $this->page;
    }

    public function setPage(int $page): int
    {
        if ($page < 1) {
            $page = 1;
        }

        if ($this->limit === null) {
            throw new RuntimeException('Limit must be not null');
        }

        if ($this->total_amount === null) {
            throw new RuntimeException('Total amount must not be null');
        }

        if ($this->total_amount === 0) {
            $this->offset = 0;
        } else {
            $offset = $this->limit * ($page - 1);
            if ($offset >= $this->total_amount) {
                $page = (int)($this->total_amount / $this->limit) + 1;
                $this->offset = $this->limit * ($page - 1);
            } else {
                $this->offset = $offset;
            }
        }

        $this->page = $page;
        return $page;
    }
}
