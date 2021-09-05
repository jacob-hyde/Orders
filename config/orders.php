<?php

return [
    'service_name' => '',

    'user' => \App\Models\User::class,

    'api_client' => \App\Models\ApiClient::class,

    'route_prefix' => 'api',
    
    'middleware' => ['api'],

    'stripe_secret' => env('STRIPE_SECRET', null),

    'stripe_webhooks' => false,

    'paypal_client' => env('PAYPAL_CLIENT'. null),

    'paypal_secret' => env('PAYPAL_SECRET', null),

    'paypal_webhooks' => false,

    'seller_stripe_id_column' => 'stripe_account_id',

    'user_stripe_customer_id_column' => 'stripe_id',

    'seller_paypal_id_column' => 'paypal_merchant_id',
    
    //Should be a job
    //It should take in the Payment and a boolean value of it is a subscription
    'status_change_callback' => null,

    //Should be a class with a static @handle method. IE HandleSubscriptionCreateOrSwap::class
    //It should take in the user, subscription_plan, paymentable, and payment_method which can default to null
    //It should return a subscription model
    'create_or_swap_subscription_callback' => null,

    //A relationship to the user model for payouts which should have an attribute of paid_out_amount
    'payout_model_relationship' => null,

    //Should be a class with a static function called checkPayoutRules which takes a user and the payout amount and returns null or a error message. IE \App\Models\Curator
    'payout_rules' => null,

    'payout_email_subject' => null,

    'payout_note' => null,

    //Array of different product types and how to calculate the coupon code price
    'coupon_calculations' => [],

    'invoice_bucket' => 's3_invoices',

    'capi' => [
        'lambda_key' => env('CAPI_LAMBDA_KEY'),
        'lambda_api' => env('CAPI_LAMBDA_API'),
    ],
];