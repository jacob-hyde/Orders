<?php

namespace JacobHyde\Orders\Models\Contracts;

interface PaymentIntentContract
{
    public function getPaymentIdentifierAttribute(): string;
}