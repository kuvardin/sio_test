<?php

declare(strict_types=1);

namespace App\Api\v1\Models;

use App\Api\v1\ApiModelImmutable;
use App\Api\v1\Output\ApiField;
use App\Api\v1\Output\ApiFieldType;
use App\Entity\Promocode;

class PromocodeApiModel extends ApiModelImmutable
{
    public function __construct(
        protected Promocode $promocode,
    )
    {

    }

    public static function getDescription(): ?string
    {
        return 'Промокод';
    }

    public static function getFields(): array
    {
        return [
            'id' => ApiField::scalar(ApiFieldType::Integer, false, 'ID'),
            'value' => ApiField::scalar(ApiFieldType::String, false, 'Значение промокода'),
            'discount_percent' => ApiField::scalar(ApiFieldType::Integer, true, 'Скидка в процентах'),
            'discount_value' => ApiField::scalar(ApiFieldType::Float, true, 'Скидка в деньгах'),
            'active_until' => ApiField::scalar(ApiFieldType::Timestamp, true, 'Дата истечения промокода'),
            'created_at' => ApiField::scalar(ApiFieldType::Timestamp, false, 'Дата создания'),
        ];
    }

    public function getPublicData(): array
    {
        return [
            'id' => $this->promocode->getId(),
            'value' => $this->promocode->getValue(),
            'discount_percent' => $this->promocode->getDiscountPercents(),
            'discount_value' => $this->promocode->getDiscountValue(),
            'active_until' => $this->promocode->getActiveUntil(),
            'created_at' => $this->promocode->getCreatedAt(),
        ];
    }
}