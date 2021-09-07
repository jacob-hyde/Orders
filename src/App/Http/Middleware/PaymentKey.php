<?php

namespace KnotAShell\Orders\App\Http\Middleware;

use KnotAShell\Orders\Models\Payment;
use Closure;

class PaymentKey
{
    public function handle($request, Closure $next)
    {
        if ($payment_key = $request->query('key')) {
            Payment::deletePaymentFromKey($payment_key, true, true, true);
        }
        return $next($request);
    }
}