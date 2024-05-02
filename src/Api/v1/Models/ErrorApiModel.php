<?php

declare(strict_types=1);

namespace App\Api\v1\Models;

use App\Api\v1\ApiModelImmutable;
use App\Api\v1\Exceptions\ApiException;
use App\Api\v1\Output\ApiField;
use App\Api\v1\Output\ApiFieldType;

class ErrorApiModel extends ApiModelImmutable
{
    public function __construct(
        protected ApiException $exception,
    )
    {
    }

    public static function getDescription(): ?string
    {
        return 'Информация об ошибке';
    }

    public static function getFields(): array
    {
        return [
            'code' => ApiField::scalar(ApiFieldType::Integer, false),
            'input_field' => ApiField::scalar(ApiFieldType::String, true),
            'description' => ApiField::scalar(ApiFieldType::Phrase, false),
        ];
    }

    public function getPublicData(): array
    {
        return [
            'code' => $this->exception->getCode(),
            'input_field' => $this->exception->getInputField(),
            'description' => $this->exception->getDescriptions(),
        ];
    }
}