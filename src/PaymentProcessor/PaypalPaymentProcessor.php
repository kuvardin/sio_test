<?php

declare(strict_types=1);

namespace App\PaymentProcessor;

class PaypalPaymentProcessor extends PaymentProcessorAbstract
{
    public static function getCode(): string
    {
        return 'PAYPAL';
    }

    public function pay(float $sum): bool
    {
        return true;
    }
}