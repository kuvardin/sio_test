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
        float $discount_value = null,
        float $discount_percent = null,
    ): float
    {
        if (!empty($discount_percent)) {
            $price -= $price * $discount_percent / 100;
        }

        if (!empty($discount_value)) {
            $price -= $discount_value;
        }

        return $price < 0.00001
            ? 0
            : ($price - $price * $tax_number_format->getTaxPercent() / 100);
    }
}