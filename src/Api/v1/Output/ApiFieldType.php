<?php

declare(strict_types=1);

namespace App\Api\v1\Output;

enum ApiFieldType: string
{
    case String = 'string';
    case Integer = 'int';
    case Float = 'float';
    case Boolean = 'bool';
    case Uuid = 'uuid';
    case Object = 'object';
    case Timestamp = 'timestamp';
    case Phrase = 'phrase';
    case Array = 'array';
    case ScalarMixed = 'mixed';

    public function isScalar(): bool
    {
        return $this !== self::Array && $this !== self::Object;
    }

    public function getJsType(): string
    {
        return match ($this) {
            self::String, self::Uuid => 'string',
            self::Integer, self::Float => 'number',
            self::Boolean => 'boolean',
            self::Timestamp => 'Date',
            self::Phrase => 'Api.Phrase',
            self::Object => 'Object',
            self::Array => 'Array',
            self::ScalarMixed => '*',
        };
    }
}
