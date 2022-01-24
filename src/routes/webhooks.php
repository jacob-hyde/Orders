<?php

use Illuminate\Support\Facades\Route;
use JacobHyde\Orders\App\Http\Controllers\CashierWebhookController;

foreach (config('webhook-client.configs') as $config) {
    Route::webhooks('webhook/' . $config['name'], $config['name']);
}

if (config('orders.stripe_webhooks')) {
    Route::stripeWebhooks('webhook/stripe');
}

Route::post('stripe/webhook', [CashierWebhookController::class, 'handleWebhook']);
