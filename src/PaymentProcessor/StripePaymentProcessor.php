<?php

declare(strict_types=1);

namespace App\PaymentProcessor;

class StripePaymentProcessor extends PaymentProcessorAbstract
{
    public static function getCode(): string
    {
        return 'STRIPE';
    }

    public function pay(float $sum): bool
    {
        return true;
    }
}