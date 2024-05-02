<?php

declare(strict_types=1);

namespace App\Api\v1\Exceptions;

use App\Api\v1\ApiModel;
use App\Api\v1\Output\ApiField;
use Exception;

class IncorrectFieldValueException extends Exception
{
    readonly public string $field_name;
    readonly public string|ApiModel|null $model_class;
    readonly public ApiField $field;
    readonly public mixed $value;

    public function __construct(string $field_name, ApiModel|string|null $model_class, ApiField $field, mixed $value)
    {
        $this->field_name = $field_name;
        $this->model_class = $model_class;
        $this->field = $field;
        $this->value = $value;

        $message = sprintf(
            '%s model field %s must be %s (current value: %s)',
            $model_class ?? 'EMPTY',
            $field_name,
            $field->model_class ?? $field->type->value,
            is_object($value) ? get_class($value) : print_r($value, true),
        );

        parent::__construct($message);
    }
}
