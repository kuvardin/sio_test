<?php

declare(strict_types=1);

namespace App\Api\v1\Models;

use App\Api\v1\ApiModelImmutable;
use App\Api\v1\Output\ApiField;
use App\Api\v1\Output\ApiFieldType;
use App\Entity\Product;

class ProductApiModel extends ApiModelImmutable
{
    public function __construct(
        protected Product $product,
    )
    {

    }

    public static function getDescription(): ?string
    {
        return 'Товар';
    }

    public static function getFields(): array
    {
        return [
            'id' => ApiField::scalar(ApiFieldType::Integer, false, 'ID'),
            'name' => ApiField::scalar(ApiFieldType::String, false, 'Наименование'),
            'price' => ApiField::scalar(ApiFieldType::Float, false, 'Цена'),
            'available' => ApiField::scalar(ApiFieldType::Boolean, false, 'Флаг "Доступно для покупки"'),
            'created_at' => ApiField::scalar(ApiFieldType::Timestamp, false, 'Дата создания'),
        ];
    }

    public function getPublicData(): array
    {
        return [
            'id' => $this->product->getId(),
            'name' => $this->product->getName(),
            'price' => $this->product->getPrice(),
            'available' => $this->product->isAvailable(),
            'created_at' => $this->product->getCreatedAt(),
        ];
    }
}