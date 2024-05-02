<?php

declare(strict_types=1);

namespace App\Localization;

use RuntimeException;

class Phrase
{
    /**
     * @var string[] Ограничение по кодам языков. Если коды заданы, то фразы на других языках должны игнорироваться
     */
    readonly public array $locales;

    /**
     * @var array<string,string|null> Значение фразы на различных языках
     */
    protected array $values = [];

    /**
     * @param array<string,string>|null $values
     * @param string[]|null $locales
     */
    public function __construct(
        array $values,
        array $locales = null,
    )
    {
        $this->locales = $locales ?? [];

        foreach ($values as $locale => $value) {
            if ($value === null) {
                continue;
            }

            if (LocaleCode::tryFrom($locale) === null) {
                throw new RuntimeException("LocaleCode $locale not found");
            }

            if ($this->locales !== [] && !in_array($locale, $this->locales, true)) {
                throw new RuntimeException(
                    sprintf(
                        'LocaleCode %s not found in phrase allowed locales (%s)',
                        $locale,
                        implode(', ', $this->locales),
                    ),
                );
            }

            $this->values[$locale] = $value;
        }

        if ($this->values === []) {
            throw new RuntimeException('Attempt to create empty phrase');
        }
    }

    public static function make(LocaleCode|string $locale, string $value): self
    {
        $locale = is_string($locale) ? LocaleCode::from($locale) : $locale;

        return new self([
            $locale->value => $value,
        ]);
    }

    public function require(LocaleCode|Locale|string $locale): ?string
    {
        if (is_string($locale)) {
            $locale = LocaleCode::from($locale);
        } elseif ($locale instanceof Locale) {
            $locale = $locale->locale;
        }

        $result = $this->values[$locale->value] ?? null;

        if ($result !== null) {
            return $result;
        }

        foreach ($this->values as $value) {
            if ($value !== null) {
                return $value;
            }
        }

        throw new RuntimeException('Empty phrase');
    }

    public function getValue(LocaleCode|Locale|string $locale): ?string
    {
        if (is_string($locale)) {
            $locale = LocaleCode::from($locale);
        } elseif ($locale instanceof Locale) {
            $locale = $locale->locale;
        }

        return $this->values[$locale->value] ?? null;
    }

    public function setValue(LocaleCode|Locale|string $locale, ?string $value): self
    {
        if (is_string($locale)) {
            $locale = LocaleCode::from($locale);
        } elseif ($locale instanceof Locale) {
            $locale = $locale->locale;
        }

        $this->values[$locale->value] = $value === '' ? null : $value;
        return $this;
    }

    /**
     * @return array<string,string|null>
     */
    public function toArray(): array
    {
        return $this->values;
    }

    public function getInfo(
        int $max_length = null,
    ): string
    {
        $one_max_length = null;

        if ($max_length !== null) {
            $languages = 0;
            $total_length = 0;

            foreach ($this->values as $value) {
                if ($value !== null) {
                    $languages++;
                    $total_length += mb_strlen($value);
                }
            }

            if ($total_length > $max_length) {
                $one_max_length = (int)($max_length / $languages);
            }
        }

        $result = [];
        foreach ($this->values as $locale => $value) {
            if ($value === null) {
                $result[] = $locale . ' IS NULL';
            } else {
                $result[] = $locale . ': ' .
                    ($one_max_length === null ? $value : mb_substr($value, 0, $one_max_length));
            }
        }

        return implode(' | ', $result);
    }

    /**
     * @return string[]
     */
    public function getLocales(bool $only_not_empties = false): array
    {
        if ($only_not_empties) {
            $result = [];

            foreach ($this->values as $locale => $value) {
                if ($value !== null) {
                    $result[] = $locale;
                }
            }

            return $result;
        }

        return array_keys($this->values);
    }

    public function check(LocaleCode|string $locale): bool
    {
        $locale = is_string($locale) ? $locale : $locale->value;
        return array_key_exists($locale, $this->values);
    }
}
