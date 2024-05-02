<?php

declare(strict_types=1);

namespace App\Api\v1\Exceptions;

use App\Api\v1\Input\ApiInput;
use App\Localization\Phrase;
use Exception;
use Throwable;
use RuntimeException;

class ApiException extends Exception
{
    public const INTERNAL_SERVER_ERROR = 1001;
    public const NOT_ENOUGH_RIGHTS = 2001;

    protected static ?array $descriptions = null;

    protected ?string $input_field;

    /**
     * @var string[]
     */
    protected array $vars;

    protected ?ApiInput $input;

    /**
     * @param int $code
     * @param string|null $input_field Поле ввода, в котором была найдена ошибка.
     * Если указали поле ввода, то аргумент $input обязателен
     * @param ApiInput|null $input Нужно для проверки правильности $input_field
     * @param Throwable|null $previous
     * @param string[] $vars Ассоциативный массие для подстановки значений в описание ошибок
     */
    private function __construct(
        int $code,
        string $input_field = null,
        ApiInput $input = null,
        Throwable $previous = null,
        array $vars = [],
    ) {
        if ($input_field !== null) {
            if ($input === null) {
                throw new RuntimeException('Empty input');
            } elseif (!$input->hasParameter($input_field)) {
                throw new RuntimeException("Unknown field: $input_field");
            }
        }

        $this->input = $input;
        $message = "API exception №$code";
        $this->input_field = $input_field;
        $this->vars = $vars;
        parent::__construct($message, $code, $previous);
    }

    public static function onlyCode(
        int $code,
        Throwable $previous = null,
        array $vars = [],
    ): self {
        return new self(
            code: $code,
            previous: $previous,
            vars: $vars,
        );
    }

    public static function withField(
        int $code,
        string $input_field,
        ApiInput $input,
        Throwable $previous = null,
        array $vars = [],
    ): self {
        return new self($code, $input_field, $input, $previous, $vars);
    }

    public function getDescriptions(): Phrase
    {
        $result = self::getDescriptionsByCode($this->code);
        if ($this->vars === []) {
            return new Phrase($result);
        }

        $new_result = [];

        foreach ($result as $lang_code => $description) {
            $new_description = $description;

            foreach ($this->vars as $var => $value) {
                $new_description = str_replace('{' . $var . '}', $value, $new_description);
            }

            $new_result[$lang_code] = $new_description;
        }

        return new Phrase($new_result);
    }

    public function getInputField(): ?string
    {
        return $this->input_field;
    }

    /**
     * @return string[]
     */
    public static function getDescriptionsByCode(int $code): array
    {
        self::$descriptions ??= require ROOT_DIR . '/config/api_v1_errors.php';
        return self::$descriptions[$code];
    }

    public function getVars(): array
    {
        return $this->vars;
    }

    public function getInput(): ?ApiInput
    {
        return $this->input;
    }
}
