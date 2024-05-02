<?php

declare(strict_types=1);

namespace App\Api\v1\Output;

use App\Api\v1\ApiModel;
use RuntimeException;

class ApiField
{
    readonly public ApiFieldType $type;
    readonly public bool $nullable;
    readonly public ?string $description;

    readonly public string|ApiModel|null $model_class;
    readonly public ApiFieldType|null $array_child_type;
    readonly public ApiModel|string|null $array_child_model_class;

    private function __construct(
        ApiFieldType $type,
        bool $nullable,
        ApiModel|string|null $model_class = null,
        ApiFieldType|null $array_child_type = null,
        ApiModel|string|null $array_child_model_class = null,
        string $description = null,
    ) {
        if ($type === ApiFieldType::Object) {
            if ($model_class === null) {
                throw new RuntimeException("Empty class for field with type {$type->value}");
            }
        } else {
            if ($model_class !== null) {
                throw new RuntimeException("Not empty class for field with type {$type->value}");
            }
        }

        if ($type === ApiFieldType::Array) {
            if ($array_child_type === ApiFieldType::Object) {
                if ($array_child_model_class === null) {
                    throw new RuntimeException("Empty class for field with type {$array_child_type->value}");
                }
            } else {
                if ($array_child_model_class !== null) {
                    throw new RuntimeException("Not empty class for field with type {$array_child_type->value}");
                }
            }

//            if ($array_child_type === ApiFieldType::Array) {
//                throw new RuntimeException('Array of array denied');
//            }
        }

        if ($model_class !== null) {
            if (!class_exists($model_class)) {
                throw new RuntimeException("API model class $model_class not found");
            }

            if (!is_subclass_of($model_class, ApiModel::class)) {
                throw new RuntimeException("API model class $model_class must be extend for ApiModel");
            }
        }

        if ($nullable && $type === ApiFieldType::Array) {
            throw new RuntimeException('Array cannot be nullable');
        }

        $this->type = $type;
        $this->nullable = $nullable;
        $this->model_class = $model_class;
        $this->description = $description;
        $this->array_child_type = $array_child_type;
        $this->array_child_model_class = $array_child_model_class;
    }

    public static function scalar(
        ApiFieldType $type,
        bool $nullable,
        string $description = null,
    ): self {
        if (!$type->isScalar()) {
            throw new RuntimeException("Field type must be scalar, not {$type->name}");
        }

        return new self(
            type: $type,
            nullable: $nullable,
            description: $description,
        );
    }

    public static function object(
        ApiModel|string $model_class,
        bool $nullable,
        string $description = null,
    ): self {
        return new self(
            type: ApiFieldType::Object,
            nullable: $nullable,
            model_class: $model_class,
            description: $description,
        );
    }

    /**
     * Массив без описания вложенных данных (использование нежелательно)
     */
    public static function array(
        string $description = null,
    ): self {
        return new self(
            type: ApiFieldType::Array,
            nullable: false,
            array_child_type: null,
            array_child_model_class: null,
            description: $description,
        );
    }

    /**
     * Массив данных скалярного типа
     */
    public static function arrayOfScalar(
        ApiFieldType $child_type,
        string $description = null,
    ): self {
        if (!$child_type->isScalar()) {
            throw new RuntimeException('Array child must be scalar');
        }

        return new self(
            type: ApiFieldType::Array,
            nullable: false,
            array_child_type: $child_type,
            array_child_model_class: null,
            description: $description,
        );
    }

    /**
     * Массив объектов
     */
    public static function arrayOfObjects(
        ApiModel|string|null $child_model_class = null,
        string $description = null,
    ): self {
        return new self(
            type: ApiFieldType::Array,
            nullable: false,
            array_child_type: ApiFieldType::Object,
            array_child_model_class: $child_model_class,
            description: $description,
        );
    }

    public function getJsType(string $api_alias = 'Api'): string
    {
        if ($this->type->isScalar()) {
            return $this->type->getJsType();
        }

        switch ($this->type) {
            case ApiFieldType::Array:
                if ($this->array_child_type !== null) {
                    if ($this->array_child_type->isScalar()) {
                        return "{$this->array_child_type->getJsType()}[]";
                    }

                    if ($this->array_child_type === ApiFieldType::Object) {
                        return "$api_alias.{$this->array_child_model_class::getName()}[]";
                    }
                } else {
                    return 'Array';
                }
                break;

            case ApiFieldType::Object:
                return "$api_alias.{$this->model_class::getName()}";
        }

        throw new RuntimeException("Unexpected API field type: {$this->type->value}");
    }
}
