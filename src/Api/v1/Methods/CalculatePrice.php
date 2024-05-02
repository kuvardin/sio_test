<?php

declare(strict_types=1);

namespace App\Api\v1\Methods;

use App\Api\ApiEnvironment;
use App\Api\v1\ApiMethodImmutable;
use App\Api\v1\Exceptions\ApiException;
use App\Api\v1\Input\ApiInput;
use App\Api\v1\Input\ApiParameter;
use App\Api\v1\Input\ApiParameterType;
use App\Api\v1\Output\ApiField;
use App\Api\v1\Output\ApiFieldType;
use App\Entity\Coupon;
use App\Entity\Product;
use App\Enum\TaxNumberFormat;
use App\Repository\CouponRepository;
use App\Repository\ProductRepository;
use App\Util\ProductPriceCalculator;

class CalculatePrice extends ApiMethodImmutable
{
    private const FIELD_PRODUCT_ID = 'product';
    private const FIELD_TAX_NUMBER = 'taxNumber';
    private const FIELD_COUPON_CODE = 'couponCode';

    public static function getDescription(): ?string
    {
        return 'Расчет стоимости товара';
    }

    protected static function getParameters(): array
    {
        return [
            self::FIELD_PRODUCT_ID => ApiParameter::scalar(ApiParameterType::Integer, 3002, 'ID товара'),
            self::FIELD_TAX_NUMBER => ApiParameter::scalar(ApiParameterType::String, 3003, 'Налоговый номер'),
            self::FIELD_COUPON_CODE => ApiParameter::scalar(ApiParameterType::String, null, 'Код купона'),
        ];
    }

    public static function getResultField(): ?ApiField
    {
        return ApiField::scalar(ApiFieldType::Float, false);
    }

    public static function handle(ApiEnvironment $environment, ApiInput $input): float
    {
        $exception = null;

        /** @var ProductRepository $product_repository */
        $product_repository = $environment->entity_manager->getRepository(Product::class);

        /** @var CouponRepository $coupon_repository */
        $coupon_repository = $environment->entity_manager->getRepository(Coupon::class);

        $product_id = $input->requireInt(self::FIELD_PRODUCT_ID);
        $product = $product_repository->find($product_id);

        if ($product === null) {
            $exception = ApiException::withField(2004, self::FIELD_PRODUCT_ID, $input, $exception);
        } elseif (!$product->isAvailable()) {
            $exception = ApiException::withField(2008, self::FIELD_PRODUCT_ID, $input, $exception);
        }

        $tax_number = $input->requireString(self::FIELD_TAX_NUMBER);
        $tax_number_format = TaxNumberFormat::tryFromTaxNumberValue($tax_number);

        if ($tax_number_format === null) {
            $exception = ApiException::withField(2005, self::FIELD_COUPON_CODE, $input, $exception);
        }

        $coupon = null;
        $coupon_code = $input->getString(self::FIELD_COUPON_CODE);
        if ($coupon_code !== null) {
            $coupon = $coupon_repository->findOneByCode($coupon_code);
            if ($coupon === null) {
                $exception = ApiException::withField(2006, self::FIELD_COUPON_CODE, $input, $exception);
            } elseif (!$coupon->isActive()) {
                $exception = ApiException::withField(2007, self::FIELD_COUPON_CODE, $input, $exception);
            }
        }

        if ($exception !== null) {
            throw $exception;
        }

        return ProductPriceCalculator::calculateFinishPrice(
            price: $product->getPrice(),
            tax_number_format:  $tax_number_format,
            discount_value: $coupon->getDiscountValue(),
            discount_percent: $coupon->getDiscountPercents(),
        );
    }
}