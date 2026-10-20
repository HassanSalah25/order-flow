<?php

use App\Payments\Gateways\CreditCardGateway;
use App\Payments\Gateways\PaypalGateway;

return [

    /*
    |--------------------------------------------------------------------------
    | Payment Gateways
    |--------------------------------------------------------------------------
    |
    | Each key is a payment "method" accepted by the API. "driver" is a class
    | implementing App\Payments\Contracts\PaymentGatewayInterface; the whole
    | block is passed to its constructor, so credentials live here (via .env).
    | To add a gateway: create the class and register it below. Nothing else.
    |
    */

    'gateways' => [

        'credit_card' => [
            'driver' => CreditCardGateway::class,
            'enabled' => env('CREDIT_CARD_ENABLED', true),
            'api_key' => env('CREDIT_CARD_API_KEY'),
            'secret' => env('CREDIT_CARD_SECRET'),
        ],

        'paypal' => [
            'driver' => PaypalGateway::class,
            'enabled' => env('PAYPAL_ENABLED', true),
            'client_id' => env('PAYPAL_CLIENT_ID'),
            'secret' => env('PAYPAL_SECRET'),
            'mode' => env('PAYPAL_MODE', 'sandbox'),
        ],

    ],

];
