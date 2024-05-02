<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Формат налогового номера
 */
enum TaxNumberFormat: string
{
    case Germany = 'GE';
    case Italy = 'IT';
    case Greece = 'GR';
    case France = 'FR';

    /**
     * @var array<string, TaxNumberFormat> Регулярные выражения для определения формата
     */
    protected const REG_EXPRESSIONS = [
        '|^DE\d{9}$|' => self::Germany,
        '|^IT\d{11}$|' => self::Italy,
        '|^GR\d{9}$|' => self::Greece,
        '|^FR[a-zA-Z]{2}\d{9}$|' => self::France,
    ];

    /**
     * Попытка получить формат по значения налогового номера
     */
    public static function tryFromTaxNumberValue(string $tax_number_value): ?self
    {
        $tax_number_value = mb_strtoupper(trim($tax_number_value));

        foreach (self::REG_EXPRESSIONS as $regexp => $format) {
            if (preg_match($regexp, $tax_number_value)) {
                return $format;
            }
        }

        return null;
    }

    /**
     * Требование получить формат по значения налогового номера
     */
    public static function fromTaxNumberValue(string $tax_number_value): self
    {
        $result = self::tryFromTaxNumberValue($tax_number_value);

        if ($result === null) {
            throw new \RuntimeException("Format not found for tax number: $tax_number_value");
        }

        return $result;
    }

    /**
     * Получить налоговый процент
     */
    public function getTaxPercent(): float
    {
        return match ($this) {
            self::Germany => 19,
            self::Italy => 22,
            self::Greece => 24,
            self::France => 20,
        };
    }
}