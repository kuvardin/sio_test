<?php

declare(strict_types=1);

namespace App\Localization;

use RuntimeException;

class Locale
{
    /**
     * @var string[]
     */
    protected array $phrases = [];

    /**
     * @var string[]
     */
    protected array $phrases_alternative = [];

    public function __construct(
        public readonly LocaleCode $locale,
        public readonly bool       $test_mode = false,
    )
    {

    }

    /**
     * @return string[]
     */
    public static function getPhraseArray(
        array $data,
        string $prefix,
        string $delimiter = null,
        array $codes = null
    ): array {
        $result = [];
        foreach ($codes ?? LocaleCode::cases() as $lang_code) {
            $key = $prefix . ($delimiter ?? '_') . $lang_code->value;
            if (array_key_exists($key, $data)) {
                $result[$lang_code->value] = $data[$key];
            }
        }

        return $result;
    }

    private static function addVariables(string $string, array $vars): string
    {
        foreach ($vars as $key => $value) {
            $string = str_replace(
                '{' . $key . '}',
                is_int($value) | is_float($value) ? (string)$value : $value,
                $string
            );
        }

        return $string;
    }

    public function get(
        string $phrase_name,
        array $arguments = null,
        array $vars = null,
    ): ?string
    {
        if (!isset($this->phrases[$phrase_name])) {
            return null;
        }

        $result = $arguments === null
            ? $this->phrases[$phrase_name]
            : sprintf($this->phrases[$phrase_name], ...$arguments);


        if (!empty($vars)) {
            $result = self::addVariables($result, $vars);
        }

        return ($this->test_mode ? "$phrase_name: " : '') . $result;
    }

    public function require(string $phrase_name, array $arguments = null, array $vars = null): string
    {
        $phrase = $this->phrases[$phrase_name] ?? $this->phrases_alternative[$phrase_name];
        $result = $arguments === null ? $phrase : sprintf($phrase, ...$arguments);

        if (!empty($vars)) {
            $result = self::addVariables($result, $vars);
        }

        return ($this->test_mode ? "$phrase_name: " : '') . $result;
    }

    public function phraseExists(string $phrase_name): bool
    {
        return array_key_exists($phrase_name, $this->phrases)
            || array_key_exists($phrase_name, $this->phrases_alternative);
    }

    public function requireFrom(array $phrases, array $arguments = null): string
    {
        return $this->getFrom($phrases, $arguments);
    }

    public function getFrom(array $phrases, array $arguments = null): ?string
    {
        $phrase = null;
        if (!empty($phrases[$this->locale->value])) {
            $phrase = $phrases[$this->locale->value];
        } else {
            foreach (LocaleCode::cases() as $lang_code) {
                if (!empty($phrases[$lang_code->value])) {
                    $phrase = $phrases[$lang_code->value];
                    break;
                }
            }
        }

        if ($phrase === null) {
            return null;
        }

        return $arguments === null ? $phrase : sprintf($phrase, ...$arguments);
    }

    public function requireOrNullFrom(array $phrases, array $arguments = null): ?string
    {
        $phrase = null;
        if (!empty($phrases[$this->locale->value])) {
            $phrase = $phrases[$this->locale->value];
        } else {
            foreach (LocaleCode::cases() as $lang_code) {
                if (!empty($phrases[$lang_code->value])) {
                    $phrase = $phrases[$lang_code->value];
                    break;
                }
            }
        }

        if ($phrase === null) {
            return null;
        }

        return $arguments === null ? $phrase : sprintf($phrase, ...$arguments);
    }

    public function getPhrases(): array
    {
        return array_merge($this->phrases, $this->phrases_alternative);
    }

    public function setPhrases(array $phrases): void
    {
        foreach ($phrases as $phrase_name => $phrase_in_languages) {
            if (!empty($phrase_in_languages[$this->locale->value])) {
                if (!is_string($phrase_in_languages[$this->locale->value])) {
                    $type = gettype($phrase_in_languages[$this->locale->value]);
                    throw new RuntimeException("Incorrect phrase $phrase_name in language {$this->locale} typed $type: " .
                        print_r($phrase_in_languages[$this->locale->value], true));
                }
                $this->phrases[$phrase_name] = $phrase_in_languages[$this->locale->value];
            } else {
                $found = false;
                foreach (LocaleCode::cases() as $lang_code) {
                    if (!empty($phrase_in_languages[$lang_code->value])) {
                        if (!is_string($phrase_in_languages[$lang_code->value])) {
                            $type = gettype($phrase_in_languages[$lang_code->value]);
                            throw new RuntimeException("Incorrect phrase $phrase_name in language {$lang_code->value}" .
                                " typed $type: " . print_r($phrase_in_languages[$lang_code->value], true));
                        }

                        $this->phrases_alternative[$phrase_name] = $phrase_in_languages[$lang_code->value];
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    throw new RuntimeException("Phrase named $phrase_name not found");
                }
            }
        }
    }
}
