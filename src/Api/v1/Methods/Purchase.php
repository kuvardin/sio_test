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
use App\Entity\Coupon;
use App\Entity\Payment;
use App\Entity\Product;
use App\Enum\TaxNumberFormat;
use App\Repository\CouponRepository;
use App\Repository\ProductRepository;
use App\Util\PaymentController;
use App\Util\ProductPriceCalculator;

class Purchase extends ApiMethodImmutable
{
    private const FIELD_PRODUCT_ID = 'product';
    private const FIELD_TAX_NUMBER = 'taxNumber';
    private const FIELD_COUPON_CODE = 'couponCode';
    private const FIELD_PAYMENT_PROCESSOR_CODE = 'paymentProcessor';

    public static function getDescription(): ?string
    {
        return 'Оплата покупки';
    }

    protected static function getParameters(): array
    {
        return [
            self::FIELD_PRODUCT_ID => ApiParameter::scalar(ApiParameterType::Integer, 3002, 'ID товара'),
            self::FIELD_TAX_NUMBER => ApiParameter::scalar(ApiParameterType::String, 3003, 'Налоговый номер'),
            self::FIELD_COUPON_CODE => ApiParameter::scalar(ApiParameterType::String, null, 'Код купона'),
            self::FIELD_PAYMENT_PROCESSOR_CODE => ApiParameter::scalar(ApiParameterType::String, 3004,
                'Код обработчика платежа'),
        ];
    }

    public static function getResultField(): ?ApiField
    {
        return null;
    }

    public static function handle(ApiEnvironment $environment, ApiInput $input)
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
            $exception = ApiException::withField(2005, self::FIELD_TAX_NUMBER, $input, $exception);
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

        $payment_processor_code = $input->requireString(self::FIELD_PAYMENT_PROCESSOR_CODE);
        $payment_processor = PaymentController::getPaymentProcessorByCode($payment_processor_code);
        if ($payment_processor === null) {
            $exception = ApiException::withField(2009, self::FIELD_PAYMENT_PROCESSOR_CODE, $input, $exception);
        }

        if ($exception !== null) {
            throw $exception;
        }

        $finish_price = ProductPriceCalculator::calculateFinishPrice(
            price: $product->getPrice(),
            tax_number_format:  $tax_number_format,
            discount_value: $coupon?->getDiscountValue(),
            discount_percent: $coupon?->getDiscountPercents(),
        );

        $purchase_status = $payment_processor->pay($finish_price);
        if (!$purchase_status) {
            throw ApiException::onlyCode(1003);
        }

        $payment = new Payment(
            product: $product,
            tax_number: $tax_number,
            tax_percent: $tax_number_format->getTaxPercent(),
            coupon: $coupon,
            payment_system_code: $payment_processor_code,
        );

        if ($coupon !== null) {
            $coupon->setActivationsNumber($coupon->getActivationsNumber() + 1);
        }

        $environment->entity_manager->persist($payment);
        $environment->entity_manager->flush();

        return null;
    }
}