<?php

declare(strict_types=1);

namespace App\Api\v1\Input;

use App;
use App\Api\v1\ApiSelectionData;
use App\Api\v1\ApiSelectionOptions;
use App\Api\v1\ApiSortDirection;
use App\Api\v1\Exceptions\ApiException;
use App\Localization\LocaleCode;
use App\Localization\Phrase;
use BackedEnum;
use DateTime;
use DateTimeZone;
use App\Util\DataFilter;
use JsonException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

class ApiInput
{
    public readonly Request $request;

    /**
     * @var ApiParameter[]
     */
    protected array $parameters;

    protected array $data = [];

    /**
     * @var string[] Поля, для которых задано пустое значение
     */
    protected array $empty_fields = [];

    public readonly ?ApiSelectionOptions $selection_options;

    protected ?ApiException $exception = null;

    /**
     * @param ApiParameter[] $parameters
     */
    public function __construct(
        Request $request,
        array $parameters,
        array $input_data,
        LocaleCode $locale_code,
        ?ApiSelectionOptions $selection_options,
        bool $from_json,
    ) {
        $this->request = $request;
        $this->parameters = $parameters;

        $this->selection_options = $selection_options;

        $fields_with_errors = [];

        if ($input_data !== []) {
            foreach ($input_data as $input_data_key => $input_data_value) {
                if ($input_data_value === '') {
                    $input_data_value = null;

                    if ($from_json) {
                        $this->empty_fields[] = $input_data_key;
                    }
                }

                $parameter = $this->parameters[$input_data_key] ?? null;
                if ($parameter !== null) {
                    switch ($parameter->type) {
                        case ApiParameterType::Integer:
                            if (is_int($input_data_value)) {
                                $this->data[$input_data_key] = $input_data_value;
                            } elseif (is_string($input_data_value)) {
                                if ((string)(int)$input_data_value === $input_data_value) {
                                    $this->data[$input_data_key] = (int)$input_data_value;
                                }
                            }

                            if ($input_data_value !== null && !isset($this->data[$input_data_key])) {
                                $fields_with_errors[] = $input_data_key;
                                $this->addException($input_data_key, 3025);
                            }
                            break;

                        case ApiParameterType::Enum:
                            if (is_string($input_data_value) || is_int($input_data_value)) {
                                $enum_item = $parameter->enum_class::tryFrom($input_data_value);
                                if ($enum_item === null) {
                                    $fields_with_errors[] = $input_data_key;
                                    $this->addException($input_data_key, 2016);
                                } else {
                                    $this->data[$input_data_key] = $enum_item;
                                }
                            }
                            break;

                        case ApiParameterType::Boolean:
                            if (is_bool($input_data_value)) {
                                $this->data[$input_data_key] = $input_data_value;
                            } elseif (is_string($input_data_value)) {
                                if ($input_data_value === '0' || $input_data_value === '1') {
                                    $this->data[$input_data_key] = $input_data_value === '1';
                                } else {
                                    $input_data_value_lowercase = strtolower($input_data_value);
                                    if (
                                        $input_data_value_lowercase === 'true' ||
                                        $input_data_value_lowercase === 'false'
                                    ) {
                                        $this->data[$input_data_key] = $input_data_value_lowercase === 'true';
                                    }
                                }
                            } elseif ($input_data_value === 0 || $input_data_value === 1) {
                                $this->data[$input_data_key] = $input_data_value === 1;
                            }
                            break;

                        case ApiParameterType::DateTime:
                            if (is_int($input_data_value)) {
                                $this->data[$input_data_key] = "@$input_data_value";
                            } elseif (is_string($input_data_value)) {
                                try {
                                    new DateTime($input_data_value);
                                    $this->data[$input_data_key] = $input_data_value;
                                } catch (Throwable) {
                                }
                            }

                            if ($input_data_value !== null && !isset($this->data[$input_data_key])) {
                                $this->addException($input_data_key, 3052);
                            }
                            break;

                        case ApiParameterType::String:
                            if (is_string($input_data_value)) {
                                $input_data_value = DataFilter::getStringEmptyToNull($input_data_value, true);
                                if ($input_data_value !== null) {
                                    $this->data[$input_data_key] = $input_data_value;
                                }
                            } elseif (is_int($input_data_value) || is_float($input_data_value)) {
                                $this->data[$input_data_key] = (string)$input_data_value;
                            }
                            break;

                        case ApiParameterType::Float:
                            if (is_float($input_data_value)) {
                                $this->data[$input_data_key] = $input_data_value;
                            } elseif (is_int($input_data_value)) {
                                $this->data[$input_data_key] = (float)$input_data_value;
                            } elseif (is_string($input_data_value) && is_numeric($input_data_value)) {
                                $this->data[$input_data_key] = (float)$input_data_value;
                            }
                            break;

                        case ApiParameterType::Phrase:
                            if (is_array($input_data_value)) {
                                $phrase = null;
                                foreach ($input_data_value as $phrase_key => $phrase_value) {
                                    $phrase_lang_code = LocaleCode::tryFrom($phrase_key);
                                    if (
                                        is_string($phrase_key)
                                        && $phrase_lang_code !== null
                                        && is_string($phrase_value)
                                    ) {
                                        $phrase_value = DataFilter::getStringEmptyToNull($phrase_value, true);
                                        if ($phrase_value !== null) {
                                            if ($phrase === null) {
                                                $phrase = Phrase::make($phrase_lang_code, $phrase_value);
                                            } else {
                                                $phrase->setValue($phrase_lang_code, $phrase_value);
                                            }
                                        }
                                    }
                                }

                                if ($phrase !== null) {
                                    $this->data[$input_data_key] = $phrase;
                                }
                            } elseif (is_string($input_data_value)) {
                                $input_data_value = DataFilter::getStringEmptyToNull($input_data_value, true);
                                if ($input_data_value !== null) {
                                    $this->data[$input_data_key] = Phrase::make($locale_code, $input_data_value);
                                }
                            }
                            break;

                        case ApiParameterType::Map:
                            if (is_string($input_data_value)) {
                                if ($input_data_value === '') {
                                    if ($from_json) {
                                        $this->empty_fields[] = $input_data_key;
                                    }
                                } else {
                                    try {
                                        $input_data_value = json_decode(
                                            $input_data_value,
                                            true,
                                            512,
                                            JSON_THROW_ON_ERROR
                                        );
                                    } catch (JsonException) {
                                    }
                                }
                            }

                            if ($input_data_value === [] && $from_json) {
                                $this->empty_fields[] = $input_data_key;
                            }

                            switch ($this->parameters[$input_data_key]->child_type) {
                                case ApiParameterType::String:
                                    if (is_array($input_data_value)) {
                                        $result = [];

                                        foreach ($input_data_value as $index => $input_data_value_part) {
                                            if (is_int($index)) {
                                                $result = [];
                                                break;
                                            }

                                            if (is_string($input_data_value_part)) {
                                                $input_data_value_part = trim($input_data_value_part);
                                                if ($input_data_value_part !== '') {
                                                    $result[$index] = trim($input_data_value_part);
                                                }
                                            } elseif (
                                                is_int($input_data_value_part)
                                                || is_float($input_data_value_part)
                                            ) {
                                                $result[$index] = (string)$input_data_value_part;
                                            } else {
                                                break 3;
                                            }
                                        }

                                        $this->data[$input_data_key] = $result;
                                    }
                                    break;

                                default:
                                    throw new RuntimeException('TODO');
                            }
                            break;

                        case ApiParameterType::Array:
                            if ($input_data_value === null) {
                                break;
                            }

                            if ($input_data_value === [] && $from_json) {
                                $this->empty_fields[] = $input_data_key;
                            }

                            switch ($this->parameters[$input_data_key]->child_type) {
                                case ApiParameterType::Integer:
                                    if (is_int($input_data_value)) {
                                        $this->data[$input_data_key] = [$input_data_value];
                                    } elseif (is_string($input_data_value) || is_array($input_data_value)) {
                                        $result = [];
                                        $input_data_value_parts = is_string($input_data_value)
                                            ? explode(',', $input_data_value)
                                            : $input_data_value;

                                        foreach ($input_data_value_parts as $input_data_value_part) {
                                            $input_data_value_part = trim($input_data_value_part);
                                            if ((string)(int)$input_data_value_part === $input_data_value_part) {
                                                $result[] = (int)$input_data_value_part;
                                            } else {
                                                break 3;
                                            }
                                        }

                                        $this->data[$input_data_key] = $result;
                                    }
                                    break;

                                case ApiParameterType::String:
                                    if (is_string($input_data_value)) {
                                        $this->data[$input_data_key] = [$input_data_value];
                                    } elseif (is_int($input_data_value) || is_float($input_data_value)) {
                                        $this->data[$input_data_key] = [(string)$input_data_value];
                                    } elseif (is_array($input_data_value)) {
                                        $result = [];

                                        foreach ($input_data_value as $input_data_value_part) {
                                            if (is_string($input_data_value_part)) {
                                                $input_data_value_part = trim($input_data_value_part);
                                                if ($input_data_value_part !== '') {
                                                    $result[] = $input_data_value_part;
                                                }
                                            } elseif (
                                                is_int($input_data_value_part)
                                                || is_float($input_data_value_part)
                                            ) {
                                                $result[] = (string)$input_data_value_part;
                                            } else {
                                                break 3;
                                            }
                                        }

                                        $this->data[$input_data_key] = $result;
                                    }
                                    break;

                                case null:
                                    if (is_array($input_data_value)) {
                                        $this->data[$input_data_key] = $input_data_value;
                                    }
                            }
                    }
                }
            }
        }

        ksort($this->data);

        foreach ($this->parameters as $parameter_name => $parameter) {
            if ($parameter->required_and_empty_error !== null && !isset($this->data[$parameter_name])) {
                if (!in_array($parameter_name, $fields_with_errors, true)) {
                    $this->addException($parameter_name, $parameter->required_and_empty_error);
                }
            }
        }
    }

    private static function getDateTimeFromString(string $value, DateTimeZone $time_zone): DateTime
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $datetime = new DateTime($value, $time_zone);
        $datetime->setTimezone($time_zone);
        return $datetime;
    }

    public function getInt(string $name): ?int
    {
        return $this->getScalar($name, ApiParameterType::Integer);
    }

    public function requireInt(string $name): int
    {
        return $this->getScalar($name, ApiParameterType::Integer, true);
    }

    public function getString(string $name): ?string
    {
        return $this->getScalar($name, ApiParameterType::String);
    }

    public function requireString(string $name): string
    {
        return $this->getScalar($name, ApiParameterType::String, true);
    }

    public function getBool(string $name): ?bool
    {
        return $this->getScalar($name, ApiParameterType::Boolean);
    }

    public function requireBool(string $name): bool
    {
        return $this->getScalar($name, ApiParameterType::Boolean, true);
    }

    public function getEnum(string $name): ?BackedEnum
    {
        return $this->getScalar($name, ApiParameterType::Enum);
    }

    public function requireEnum(string $name): BackedEnum
    {
        return $this->getScalar($name, ApiParameterType::Enum, true);
    }

    public function getPhrase(string $name): ?Phrase
    {
        return $this->getScalar($name, ApiParameterType::Phrase);
    }

    public function requirePhrase(string $name): Phrase
    {
        return $this->getScalar($name, ApiParameterType::Phrase, true);
    }

    protected function getScalar(string $name, ApiParameterType $type, bool $require = false): mixed
    {
        if (!array_key_exists($name, $this->parameters)) {
            throw new RuntimeException("Parameter with name $name not found");
        }

        if ($this->parameters[$name]->type !== $type) {
            throw new RuntimeException(
                "Unable get parameter \"$name\" with type {$type->value} " .
                "(type must be {$this->parameters[$name]->type->value})"
            );
        }

        if ($require && !$this->parameters[$name]->isRequired()) {
            throw new RuntimeException("Unable require parameter \"$name\"");
        }

        return $this->data[$name] ?? null;
    }

    public function getFloat(string $field_name): ?float
    {
        return $this->getScalar($field_name, ApiParameterType::Float);
    }

    public function requireFloat(string $field_name): float
    {
        return $this->getScalar($field_name, ApiParameterType::Float, true);
    }

    public function getDateTime(string $field_name, DateTimeZone $time_zone): ?DateTime
    {
        $value = $this->getScalar($field_name, ApiParameterType::DateTime);
        return $value === null ? null : self::getDateTimeFromString($value, $time_zone);
    }

    public function requireDateTime(string $field_name, DateTimeZone $time_zone): DateTime
    {
        $value = $this->getScalar($field_name, ApiParameterType::DateTime, true);
        return self::getDateTimeFromString($value, $time_zone);
    }

    protected function getMap(string $name, ApiParameterType $child_type, bool $require = false): mixed
    {
        if (!array_key_exists($name, $this->parameters)) {
            throw new RuntimeException("Parameter with name $name not found");
        }

        if ($this->parameters[$name]->type !== ApiParameterType::Map) {
            throw new RuntimeException(
                "Unable get parameter \"$name\" with type Map " .
                "(type must be {$this->parameters[$name]->type->value})"
            );
        }

        if ($this->parameters[$name]->child_type !== $child_type) {
            throw new RuntimeException(
                "Unable get parameter \"$name\" with child type {$child_type->value} " .
                "(type must be {$this->parameters[$name]->child_type->value})"
            );
        }

        if ($require && !$this->parameters[$name]->isRequired()) {
            throw new RuntimeException("Unable require parameter \"$name\"");
        }

        return $this->data[$name] ?? null;
    }

    /**
     * @return array<string,string>|null
     */
    public function getMapOfString(string $name): ?array
    {
        return $this->getMap($name, ApiParameterType::String);
    }

    /**
     * @return array<string,string>
     */
    public function requireMapOfString(string $name): array
    {
        return $this->getMap($name, ApiParameterType::String, true);
    }

    public function getArrayOfMixed(string $name): ?array
    {
        return $this->getArray($name, null);
    }

    public function requireArrayOfMixed(string $name): array
    {
        return $this->getArray($name, null, true);
    }

    protected function getArray(string $name, ?ApiParameterType $child_type, bool $require = false): ?array
    {
        if (!array_key_exists($name, $this->parameters)) {
            throw new RuntimeException("Parameter with name $name not found");
        }

        if ($this->parameters[$name]->type !== ApiParameterType::Array) {
            throw new RuntimeException(
                "Unable get parameter \"$name\" with type Array " .
                "(type must be {$this->parameters[$name]->type->value})"
            );
        }

        if ($this->parameters[$name]->child_type !== $child_type) {
            throw new RuntimeException(
                "Unable get parameter \"$name\" with child type {$child_type->value} " .
                "(type must be {$this->parameters[$name]->child_type->value})"
            );
        }

        if ($require && !$this->parameters[$name]->isRequired()) {
            throw new RuntimeException("Unable require parameter \"$name\"");
        }

        return isset($this->data[$name]) && is_array($this->data[$name]) ? $this->data[$name] : null;
    }

    /**
     * @return int[]|null
     */
    public function getArrayOfInt(string $name): ?array
    {
        return $this->getArray($name, ApiParameterType::Integer);
    }

    /**
     * @return int[]
     */
    public function requireArrayOfInt(string $name): array
    {
        return $this->getArray($name, ApiParameterType::Integer, true);
    }

    /**
     * @return string[]|null
     */
    public function getArrayOfString(string $name): ?array
    {
        return $this->getArray($name, ApiParameterType::String);
    }

    /**
     * @return string[]
     */
    public function requireArrayOfString(string $name): array
    {
        return $this->getArray($name, ApiParameterType::String, true);
    }

    public function __toString(): string
    {
        return http_build_query($this->data);
    }

    public function requireSelectionData(int $total_amount): ApiSelectionData
    {
        $old_offset = isset($this->data['start']) && (is_int($this->data['start']) ||
            (is_string($this->data['start']) && preg_match('|^\d+$|', $this->data['start'])))
            ? (int)$this->data['start']
            : null;

        $old_limit = isset($this->data['count']) && (is_int($this->data['count']) ||
            (is_string($this->data['count']) && preg_match('|^\d+$|', $this->data['count'])))
            ? (int)$this->data['count']
            : null;

        if ($this->selection_options === null) {
            throw new RuntimeException('Selection options are empty');
        }

        $selection_data = new ApiSelectionData(
            limit_max: $this->selection_options->requireLimitMax(),
            sort_by_variants: $this->selection_options->getSortByVariants(),
            total_amount: $total_amount,
        );

        $limit = $this->getInt(ApiSelectionOptions::FIELD_LIMIT) ?? $old_limit;
        if ($limit === null || $limit < 1 || $limit > $selection_data->limit_max) {
            $limit = $this->selection_options->requireLimitMax();
        }

        $selection_data->setLimit($limit);

        $sort = $this->getMapOfString(ApiSelectionOptions::FIELD_SORT);
        if (!empty($sort)) {
            foreach ($sort as $sort_by_alias => $sort_direction_value) {
                $sort_direction_value = strtoupper($sort_direction_value);
                $sort_direction = ApiSortDirection::tryFrom($sort_direction_value);
                if ($sort_direction === null) {
                    continue;
                }

                if (!$selection_data->checkSortByAlias($sort_by_alias)) {
                    continue;
                }

                $selection_data->addSortSetting($sort_by_alias, $sort_direction);
            }
        }

        if ($selection_data->getSortSettings() === []) {
            $selection_data->addSortSetting(
                $this->selection_options->getSortByDefault(),
                $this->selection_options->getSortDirectionDefault(),
            );
        }

        if ($old_offset !== null && $old_offset > 0) {
            $page = (int)($old_offset / $limit);
        } else {
            $page = $this->getInt(ApiSelectionOptions::FIELD_PAGE) ?? 1;
        }

        $selection_data->setPage($page);

        return $selection_data;
    }

    public function addException(string $field_name, int $code): void
    {
        if (!array_key_exists($field_name, $this->parameters)) {
            throw new RuntimeException("API parameter with name $field_name not found");
        }

        $this->exception = ApiException::withField($code, $field_name, $this, $this->exception);
    }

    public function getException(): ?ApiException
    {
        return $this->exception;
    }

    public function hasParameter(string $parameter_name): bool
    {
        return array_key_exists($parameter_name, $this->parameters);
    }

    public function isEmptyField(string $field_name): bool
    {
        if (!array_key_exists($field_name, $this->parameters)) {
            throw new RuntimeException("API parameter with name $field_name not found");
        }

        return in_array($field_name, $this->empty_fields, true);
    }

    /**
     * @return string[]
     */
    public function getEmptyFields(): array
    {
        return $this->empty_fields;
    }
}
