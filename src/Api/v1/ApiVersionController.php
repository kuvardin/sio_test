<?php

declare(strict_types=1);

namespace App\Api\v1;

use App\Api\ApiEnvironment;
use App\Api\ApiVersionController as ApiVersionControllerAbstract;
use App\Api\v1\Exceptions\ApiException;
use App\Api\v1\Exceptions\IncorrectFieldValueException;
use App\Api\v1\Input\ApiInput;
use App\Api\v1\Models\ErrorApiModel;
use App\Api\v1\Output\ApiField;
use App\Api\v1\Output\ApiFieldType;
use App\Localization\Locale;
use App\Localization\LocaleCode;
use App\Localization\Phrase;
use DateTimeInterface;
use JsonException;
use RuntimeException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

class ApiVersionController extends ApiVersionControllerAbstract
{
    public static function handle(
        ApiEnvironment $environment,
        array $route_parts,
        Request $request,
    ): JsonResponse {
        $execution_start_time = microtime(true);

        $input_is_json = false;

        $input_data = [];
        $get = $request->query->all();
        if ($get !== []) {
            $input_data = $get;
        } else {
            $input_string = $request->getContent();
            try {
                $input_data_decoded = json_decode($input_string, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($input_data_decoded)) {
                    $input_data = $input_data_decoded;
                    $input_is_json = true;
                }
            } catch (JsonException) {
            }
        }

        $language = new Locale(LocaleCode::RU);

        /** @var Throwable[] $exceptions */
        $throwables = [];

        try {
            try {
                $method_class = self::getMethodClass($route_parts);
                if ($method_class === null || !class_exists($method_class)) {
                    throw ApiException::onlyCode(1002);
                }

                $allowed_groups_codes = $method_class::getAllowedGroups();
                if ($allowed_groups_codes !== null) {
                    if ($session->user === null || !$session->hasPermissionByGroups($allowed_groups_codes)) {
                        throw ApiException::onlyCode(2001);
                    }
                }

                $input = new ApiInput(
                    request: $request,
                    parameters: $method_class::getAllParameters($language->locale),
                    input_data: $input_data,
                    locale_code: $language->locale,
                    selection_options: $method_class::getSelectionOptions($language->locale),
                    from_json: $input_is_json,
                );

                if ($input->getException() !== null) {
                    throw $input->getException();
                }

                $method_result = $method_class::handle($environment, $input);

                $method_result_field = $method_class::getResultField();

                $public_data = null;
                if ($method_result === null) {
                    if ($method_result_field !== null && !$method_result_field->nullable) {
                        throw new RuntimeException("Method $method_class returns null");
                    }
                } else {
                    if ($method_result_field === null) {
                        throw new RuntimeException("Method $method_class must return null");
                    }

                    $public_data = self::processResult($method_result_field, $method_result, null, 'result');
                }

                $result = [
                    'ok' => true,
                    'result' => $public_data,
                    'errors' => [],
                ];
            } catch (ApiException $exception) {
                throw $exception;
            } catch (Throwable $exception) {
                throw ApiException::onlyCode(ApiException::INTERNAL_SERVER_ERROR, previous: $exception);
            }
        } catch (ApiException $api_exception) {
            $exceptions_public_data = [];

            do {
                if ($api_exception instanceof ApiException) {
                    $api_exception_model = new ErrorApiModel($api_exception);
                    $exceptions_public_data[] = self::processResult(
                        ApiField::object(ErrorApiModel::class, false),
                        $api_exception_model,
                        ErrorApiModel::class,
                        'error',
                    );
                } else {
                    $throwables[] = $api_exception;
                }
            } while ($api_exception = $api_exception->getPrevious());

            $result = [
                'ok' => false,
                'result' => null,
                'errors' => $exceptions_public_data,
            ];
        }

        $throwables_data = [];
        foreach ($throwables as $throwable) {
            $throwables_data[] = [
                'class' => get_class($throwable),
                'code' => $throwable->getCode(),
                'message' => $throwable->getMessage(),
                'file' => $throwable->getFile(),
                'line' => $throwable->getLine(),
                'trace' => $throwable->getTrace(),
            ];
        }

        $memory_peak_usage = memory_get_peak_usage();
        $memory_peak_usage_unit = 'B';
        if ($memory_peak_usage > 1024) {
            $memory_peak_usage = $memory_peak_usage / 1024;
            $memory_peak_usage_unit = 'kB';

            if ($memory_peak_usage > 1024) {
                $memory_peak_usage = $memory_peak_usage / 1024;
                $memory_peak_usage_unit = 'MB';
            }
        }

        $result['service_info'] = [
            'generation_ms' => (microtime(true) - $execution_start_time) * 1000,
            'memory_peak_usage' => round($memory_peak_usage, 2) . ' ' . $memory_peak_usage_unit,
            'throwables' => $throwables_data,
        ];

//        header('Cache-Control: no-store, no-cache, must-revalidate');
//        header('Expires: ' . date('r'));
//        header('Content-Type: application/json');
        return new JsonResponse($result, $result['ok'] ? 200 : 400, []);
    }

    /**
     * @throws IncorrectFieldValueException
     */
    protected static function processResult(
        ApiField $field,
        mixed $value,
        ApiModel|string|null $model_class,
        string $field_name,
    ): mixed {
        $field_name_full = ($model_class === null ? '' : "$model_class -> ") . $field_name;

        if ($value === null) {
            if (!$field->nullable) {
                throw new RuntimeException("Field $field_name_full are null but not nullable");
            }

            return null;
        }

        switch ($field->type) {
            case ApiFieldType::String:
                if (!is_string($value)) {
                    throw new IncorrectFieldValueException($field_name, $model_class, $field, $value);
                }

                return $value;

            case ApiFieldType::Timestamp:
                if ($value instanceof DateTimeInterface) {
                    return $value->getTimestamp();
                }

                if (is_int($value)) {
                    return $value;
                }

                throw new IncorrectFieldValueException($field_name, $model_class, $field, $value);

            case ApiFieldType::Integer:
                if (!is_int($value)) {
                    throw new IncorrectFieldValueException($field_name, $model_class, $field, $value);
                }

                return $value;

            case ApiFieldType::Float:
                if (!is_float($value)) {
                    throw new IncorrectFieldValueException($field_name, $model_class, $field, $value);
                }

                return $value;

            case ApiFieldType::Boolean:
                if (!is_bool($value)) {
                    throw new IncorrectFieldValueException($field_name, $model_class, $field, $value);
                }

                return $value;

            case ApiFieldType::Object:
                if (!is_object($value) && !($value instanceof $field->model_class)) {
                    throw new IncorrectFieldValueException($field_name, $model_class, $field, $value);
                }

                $model_fields = $field->model_class::getFields();

                $result = [];
                $public_data = $value->getPublicData();
                foreach ($public_data as $public_data_field => $public_data_value) {
                    if (str_starts_with($public_data_field, '_')) {
                        $result[$public_data_field] = $public_data_value;
                        continue;
                    }

                    if (!array_key_exists($public_data_field, $model_fields)) {
                        throw new RuntimeException("Unknown {$field->model_class} field named $public_data_field");
                    }

                    $result[$public_data_field] = self::processResult(
                        $model_fields[$public_data_field],
                        $public_data_value,
                        $field->model_class,
                        $public_data_field,
                    );
                }

                foreach ($model_fields as $model_field_name => $model_field) {
                    if (str_starts_with($model_field_name, '_')) {
                        continue;
                    }

                    if (!array_key_exists($model_field_name, $public_data)) {
                        throw new RuntimeException("Field $model_field_name not found in {$field->model_class}");
                    }
                }

                return $result;

            case ApiFieldType::Phrase:
                if ($value instanceof Phrase) {
                    return $value->toArray();
                }

                throw new IncorrectFieldValueException($field_name, $model_class, $field, $value);

            case ApiFieldType::Array:
                if ($field->array_child_type === null) {
                    return $value;
                }

                $result = [];
                $child_field = $field->array_child_type === ApiFieldType::Object
                    ? ApiField::object($field->array_child_model_class, false)
                    : ApiField::scalar($field->array_child_type, false);

                foreach ($value as $array_item_key => $array_item_value) {
                    if (!is_int($array_item_key)) {
                        throw new RuntimeException("Array $field_name_full must be not associative");
                    }

                    $result[] = self::processResult(
                        $child_field,
                        $array_item_value,
                        $field->array_child_model_class,
                        "[array_item:$field_name]",
                    );
                }

                return $result;

            case ApiFieldType::ScalarMixed:
                if (!is_scalar($value)) {
                    $value_type = getType($value);
                    throw new RuntimeException("Field value must be scalar (received $value_type)");
                }

                return $value;

            default:
                throw new RuntimeException("Unknown type {$field->type->value}");
        }
    }

    public static function getMethodClass(array $route_parts): string|ApiMethod|null
    {
        $result = 'App\\Api\\v1\\Methods';
        foreach ($route_parts as $route_part) {
            if ($route_part === '') {
                continue;
            }

            $route_part_ucfirst = ucfirst($route_part);
            if ($route_part_ucfirst === $route_part) {
                return null;
            }

            $result .= '\\' . $route_part_ucfirst;
        }

        return $result;
    }
}
