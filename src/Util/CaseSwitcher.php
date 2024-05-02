<?php

declare(strict_types=1);

namespace App\Util;

use RuntimeException;

class CaseSwitcher
{
    public static function snakeToCamel(string $string, bool $ucfirst = false): string
    {
        $words = explode('_', $string);
        $result = [];

        foreach ($words as $word) {
            $result[] = $result !== [] || $ucfirst ? ucfirst($word) : $word;
        }

        return implode('', $result);
    }

    public static function camelToSnake(string $string, bool $upper = null): string
    {
        if (!preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $string, $matches)) {
            throw new RuntimeException('Regexp execution error');
        }

        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match === strtoupper($match) ? strtolower($match) : lcfirst($match);
        }

        $result = implode('_', $ret);

        if ($upper) {
            return strtoupper($result);
        }

        if ($upper === false) {
            return strtolower($result);
        }

        return $result;
    }
}