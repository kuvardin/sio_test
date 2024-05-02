<?php

declare(strict_types=1);

namespace App\Util;

use RuntimeException;
use Throwable;
use DateTime;
use DateTimeZone;

class DataFilter
{
    private function __construct()
    {
    }

    public static function requireInt(mixed $var): int
    {
        $result = self::getInt($var);
        if ($result === null) {
            $type = gettype($var);
            throw new RuntimeException("Unexpected var type: $type (expected int)");
        }

        return $result;
    }

    public static function getInt(mixed $var, bool $zero_to_null = false): ?int
    {
        if ($var === null) {
            return null;
        }

        $result = null;
        if (is_int($var)) {
            $result = $var;
        } elseif (is_string($var) && preg_match('/^(-|\+)?\d+$/', $var)) {
            $result = (int)$var;
        }

        if ($result !== null && !($zero_to_null && $result === 0)) {
            return $result;
        }

        return null;
    }

    public static function requireIntZeroToNull(mixed $var): ?int
    {
        $result = self::getInt($var);
        if ($result === null) {
            $type = gettype($var);
            throw new RuntimeException("Unexpected var type: $type (expected int)");
        }

        return $result === 0 ? null : $result;
    }

    public static function getBoolByValues(mixed $var, mixed $true_value, mixed $false_value): ?bool
    {
        return $var !== $true_value && $var !== $false_value ? null : $var === $true_value;
    }

    public static function requireBoolByValues(mixed $var, mixed $true_value, mixed $false_value): bool
    {
        if ($var !== $true_value && $var !== $false_value) {
            throw new RuntimeException("Unknown value: $var (must be $true_value or $false_value)");
        }

        return $var === $true_value;
    }

    public static function getBool(mixed $var): ?bool
    {
        return is_bool($var) ? $var : null;
    }

    public static function requireBool(mixed $var): bool
    {
        if (is_bool($var)) {
            return $var;
        }

        $type = gettype($var);
        throw new RuntimeException("Unexpected var type: $type (expected bool)");
    }

    public static function getString(mixed $var, bool $filter = false): ?string
    {
        if (is_string($var)) {
            return $filter ? self::filterString($var) : $var;
        }

        return null;
    }

    public static function requireString(mixed $var, bool $filter = false): string
    {
        if (is_string($var)) {
            return $filter ? self::filterString($var) : $var;
        }

        $type = gettype($var);
        throw new RuntimeException("Unexpected var type: $type (expected string)");
    }

    public static function filterString(string $var): string
    {
        return trim(preg_replace("/[  \t]+/u", ' ', $var));
    }

    public static function getStringEmptyToNull(mixed $var, bool $filter = false): ?string
    {
        if (is_int($var) || is_float($var)) {
            $var = (string)$var;
        }

        if (is_string($var)) {
            if ($filter) {
                $var = self::filterString($var);
            }

            return $var === '' ? null : $var;
        }

        return null;
    }

    public static function requireStringEmptyToNull(mixed $var, bool $filter = false): ?string
    {
        if (is_string($var)) {
            if ($filter) {
                $var = self::filterString($var);
            }

            return $var === '' ? null : $var;
        }

        $type = gettype($var);
        throw new RuntimeException("Unexpected var type: $type (expected string)");
    }

    public static function requireNotEmptyString(mixed $var, bool $filter = false): string
    {
        if (is_string($var)) {
            $var = $filter ? self::filterString($var) : $var;
            if ($var === '') {
                throw new RuntimeException('The string is empty');
            }
            return $var;
        }

        $type = gettype($var);
        throw new RuntimeException("Unexpected var type: $type (expected string)");
    }

    public static function requireDateTime(mixed $var, DateTimeZone $timezone = null): DateTime
    {
        try {
            if (is_string($var)) {
                return new DateTime($var, $timezone);
            }

            if (is_int($var)) {
                return new DateTime('@' . $var, $timezone);
            }
        } catch (Throwable $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }

        $type = gettype($var);
        throw new RuntimeException("Unexpected var type: $type (expected int or string)");
    }

    public static function searchUnknownFields(array &$data, array $known_keys): void
    {
        $exception = null;
        $keys = array_keys($data);
        $unknown_keys = array_diff($keys, $known_keys);
        if ($unknown_keys !== []) {
            foreach ($unknown_keys as $unknown_key) {
                $type = gettype($data[$unknown_key]);
                $value_string = is_object($data[$unknown_key])
                    ? get_class($data[$unknown_key])
                    : print_r($data[$unknown_key], true);
                $message = "Unknown field $unknown_key typed $type with value $value_string";
                $exception = new RuntimeException($message, 0, $exception);
            }
            throw $exception;
        }
    }

    public static function getDateTime(mixed $var, DateTimeZone $timezone = null): ?DateTime
    {
        if ($var === null || $var === '' || $var === '0' || $var === 0) {
            return null;
        }

        try {
            return self::requireDateTime($var, $timezone);
        } catch (Throwable $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }

    public static function getFloat(mixed $var): ?float
    {
        if (is_int($var) || is_float($var) || (is_string($var) && is_numeric($var))) {
            return (float)$var;
        }

        return null;
    }

    public static function requireFloat(mixed $var): float
    {
        if (is_int($var) || is_float($var) || (is_string($var) && is_numeric($var))) {
            return (float)$var;
        }

        $type = gettype($var);
        throw new RuntimeException("Unexpected var type: $type (expected float or string)");
    }

    /**
     * @return string[]
     */
    public static function requireArrayOfString(mixed $var): array
    {
        if (is_array($var)) {
            $result = [];
            foreach ($var as $value) {
                if (!is_string($value)) {
                    $value_string = is_object($value) ? get_class($value) : print_r($value, true);
                    throw new RuntimeException("Incorrect array value: $value_string");
                }

                $result[] = $value;
            }

            return $result;
        }

        $type = gettype($var);
        throw new RuntimeException("Unexpected var type: $type (expected float or string)");
    }

    /**
     * Массив будет считаться ассоциативным, если индекс хотябы одного элемента - строка
     */
    public static function isAssociativeArray(array &$array): bool
    {
        foreach ($array as $key => $value) {
            if (is_string($key)) {
                return true;
            }
        }

        return false;
    }
}
