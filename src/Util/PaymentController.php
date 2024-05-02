<?php

declare(strict_types=1);

namespace App\Util;

use App\PaymentProcessor\PaymentProcessorAbstract;
use App\PaymentProcessor\PaypalPaymentProcessor;
use App\PaymentProcessor\StripePaymentProcessor;

class PaymentController
{
    public static function getPaymentProcessorByCode(string $code): ?PaymentProcessorAbstract
    {
        return match ($code) {
            PaypalPaymentProcessor::getCode() => new PaypalPaymentProcessor(),
            StripePaymentProcessor::getCode() => new StripePaymentProcessor(),
            default => null,
        };
    }
}