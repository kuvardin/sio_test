<?php

declare(strict_types=1);

namespace App\Util;

use App\Enum\TaxNumberFormat;

/**
 * Калькулятор конечной цены
 */
class ProductPriceCalculator
{
    public static function calculateFinishPrice(
        float $price,
        TaxNumberFormat $tax_number_format,
        float $discount_value = 0,
        float $discount_percent = 0,
    ): float
    {
        $price -= $price * $discount_percent / 100;
        $price -= $discount_value;

        return $price < 0.00001
            ? $price * $tax_number_format->getTaxPercent() / 100
            : 0;
    }
}