<?php

declare(strict_types=1);

namespace App\Api\v1\Input;

use RuntimeException;

enum ApiParameterType: string
{
    case String = 'string';
    case Integer = 'int';
    case Float = 'float';
    case Boolean = 'bool';
    case Phrase = 'phrase';
    case DateTime = 'date_time';
    case Date = 'date';
    case Uuid = 'uuid';
    case Array = 'array';
    case Map = 'map';
    case Enum = 'enum';

    public function isScalar(): bool
    {
        return $this !== self::Array && $this !== self::Map;
    }

    public function getJsType(): string
    {
        switch ($this) {
            case self::String:
            case self::Uuid:
                return 'string';

            case self::Integer:
            case self::Float:
                return 'number';

            case self::Boolean:
                return 'boolean';

            case self::Phrase:
                return 'Api.Phrase';

            case self::DateTime:
            case self::Date:
                return 'Date';

            case self::Array:
            case self::Map:
                return 'Array';

            case self::Enum:
                return 'Enum';
        }

        throw new RuntimeException("Unexpected API parameter type: {$this->value}");
    }
}
