<?php

declare(strict_types=1);

namespace App\PaymentProcessor;

abstract class PaymentProcessorAbstract
{
    abstract static function getCode(): string;

    abstract function pay(float $sum): bool;
}