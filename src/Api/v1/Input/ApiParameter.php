<?php

declare(strict_types=1);

namespace App\Api\v1\Input;

use RuntimeException;
use UnitEnum;

class ApiParameter
{
    readonly public ApiParameterType $type;
    readonly public ?ApiParameterType $child_type;
    readonly public ?int $required_and_empty_error;
    readonly public ?string $description;

    readonly public ?bool $number_positive;
    readonly public ?int $integer_min_value;
    readonly public ?int $integer_max_value;
    readonly public ?int $string_min_length;
    readonly public ?int $string_max_length;
    readonly public ?float $float_min_value;
    readonly public ?float $float_max_value;
    readonly public UnitEnum|string|null $enum_class;

    private function __construct(
        ApiParameterType $type,
        ?ApiParameterType $child_type,
        ?int $required_and_empty_error,
        string $description = null,
        bool $number_positive = null,
        int $integer_min_value = null,
        int $integer_max_value = null,
        int $string_min_length = null,
        int $string_max_length = null,
        float $float_min_value = null,
        float $float_max_value = null,
        UnitEnum|string $enum_class = null,
    ) {
        if ($type === ApiParameterType::Array) {
            if ($child_type === ApiParameterType::Array) {
                throw new RuntimeException('Array of array are denied');
            }
        } elseif ($type === ApiParameterType::Map) {
            if ($child_type === null) {
                throw new RuntimeException('Field with type map must have child type');
            }
        } elseif ($type === ApiParameterType::Enum) {
            if ($child_type !== ApiParameterType::String && $child_type !== ApiParameterType::Integer) {
                throw new RuntimeException('Field with type enum must have child type (string or integer)');
            }
        } elseif ($child_type !== null) {
            throw new RuntimeException('Field with type scalar must not have child type');
        }

        if ($type === ApiParameterType::Enum) {
            $enum_cases_string = implode(
                ', ',
                array_map(static fn(UnitEnum $unit_enum) => $unit_enum->value, $enum_class::cases()),
            );
            if ($description === null) {
                $description = "One of $enum_cases_string";
            } else {
                $description .= " (one of $enum_cases_string)";
            }
        }

        $this->type = $type;
        $this->child_type = $child_type;
        $this->required_and_empty_error = $required_and_empty_error;
        $this->description = $description;

        $this->number_positive = $number_positive;
        $this->integer_min_value = $integer_min_value;
        $this->integer_max_value = $integer_max_value;
        $this->string_min_length = $string_min_length;
        $this->string_max_length = $string_max_length;
        $this->float_min_value = $float_min_value;
        $this->float_max_value = $float_max_value;

        $this->enum_class = $enum_class;
    }


    public static function integer(
        ?int $required_and_empty_error,
        string $description = null,
        ?int $min_value = null,
        ?int $max_value = null,
        ?bool $positive = null,
    ): self {
        return new self(
            ApiParameterType::Integer,
            null,
            $required_and_empty_error,
            $description,
            number_positive: $positive,
            integer_min_value: $min_value,
            integer_max_value: $max_value,
        );
    }

    public static function string(
        ?int $required_and_empty_error,
        string $description = null,
        ?int $min_length = null,
        ?int $max_length = null,
    ): self {
        return new self(
            ApiParameterType::String,
            null,
            $required_and_empty_error,
            $description,
            string_min_length: $min_length,
            string_max_length: $max_length,
        );
    }

    public static function float(
        ?int $required_and_empty_error,
        string $description = null,
        ?float $positive = null,
        ?float $min_value = null,
        ?float $max_value = null,
    ): self {
        return new self(
            ApiParameterType::Float,
            null,
            $required_and_empty_error,
            $description,
            number_positive: $positive,
            float_min_value: $min_value,
            float_max_value: $max_value,
        );
    }

    public static function boolean(
        ?int $required_and_empty_error,
        string $description = null,
    ): self {
        return new self(
            ApiParameterType::Boolean,
            null,
            $required_and_empty_error,
            $description,
        );
    }

    public static function uuid(
        ?int $required_and_empty_error,
        string $description = null,
    ): self {
        return new self(
            ApiParameterType::Uuid,
            null,
            $required_and_empty_error,
            $description,
        );
    }

    public static function scalar(
        ApiParameterType $type,
        ?int $required_and_empty_error,
        string $description = null,
    ): self {
        return new self($type, null, $required_and_empty_error, $description);
    }

    public static function array(
        ?ApiParameterType $child_type,
        ?int $required_and_empty_error,
        string $description = null,
    ): self {
        return new self(ApiParameterType::Array, $child_type, $required_and_empty_error, $description);
    }

    public static function map(
        ApiParameterType $child_type,
        ?int $required_and_empty_error,
        string $description = null,
    ): self {
        return new self(ApiParameterType::Map, $child_type, $required_and_empty_error, $description);
    }

    public function isRequired(): bool
    {
        return $this->required_and_empty_error !== null;
    }

    public function getJsType(): string
    {
        if ($this->type === ApiParameterType::Array) {
            return $this->child_type === null
                ? 'Array'
                : "{$this->child_type->getJsType()}[]";
        }

        if ($this->type === ApiParameterType::Map) {
            return "Map<String,{$this->child_type->getJsType()}>";
        }

        if ($this->type === ApiParameterType::Enum) {
            return $this->child_type->getJsType();
        }

        if ($this->type->isScalar()) {
            return $this->type->getJsType();
        }

        throw new RuntimeException("Unexpected API parameter type: {$this->type->value}");
    }

    public static function enum(
        UnitEnum|string $enum_class,
        ApiParameterType $child_type,
        ?int $required_and_empty_error,
        string $description = null,
    ): self {
        if (!enum_exists($enum_class)) {
            $enum_class_name = is_string($enum_class) ? $enum_class : $enum_class::class;
            throw new RuntimeException("Enum $enum_class_name not found");
        }

        if ($child_type !== ApiParameterType::String && $child_type !== ApiParameterType::Integer) {
            throw new RuntimeException("Incorrect enum child type: {$child_type->value}");
        }

        return new self(
            ApiParameterType::Enum,
            child_type: $child_type,
            required_and_empty_error: $required_and_empty_error,
            description: $description,
            enum_class: $enum_class,
        );
    }
}
