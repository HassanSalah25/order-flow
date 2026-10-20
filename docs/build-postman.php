<?php

/*
 * Generates docs/Order-Payment-API.postman_collection.json.
 * Run: php docs/build-postman.php
 */

function req(string $name, string $method, string $path, ?array $body = null, bool $auth = true, array $query = [], string $desc = '', array $examples = []): array
{
    $url = [
        'raw' => '{{base_url}}/'.$path.($query ? '?'.http_build_query($query) : ''),
        'host' => ['{{base_url}}'],
        'path' => explode('/', $path),
    ];
    if ($query) {
        $url['query'] = array_map(fn ($k, $v) => ['key' => $k, 'value' => (string) $v], array_keys($query), $query);
    }

    $request = [
        'method' => $method,
        'header' => [
            ['key' => 'Accept', 'value' => 'application/json'],
            ['key' => 'Content-Type', 'value' => 'application/json'],
        ],
        'url' => $url,
        'description' => $desc,
    ];
    if ($body !== null) {
        $request['body'] = ['mode' => 'raw', 'raw' => json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)];
    }
    if (! $auth) {
        $request['auth'] = ['type' => 'noauth'];
    }

    $responses = [];
    foreach ($examples as [$exName, $code, $status, $exBody, $exReqBody]) {
        $exRequest = $request;
        if ($exReqBody !== null) {
            $exRequest['body'] = ['mode' => 'raw', 'raw' => json_encode($exReqBody, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)];
        }
        $responses[] = [
            'name' => $exName,
            'originalRequest' => $exRequest,
            'status' => $status,
            'code' => $code,
            '_postman_previewlanguage' => 'json',
            'header' => [['key' => 'Content-Type', 'value' => 'application/json']],
            'body' => $exBody === null ? '' : json_encode($exBody, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        ];
    }

    $item = ['name' => $name, 'request' => $request, 'response' => $responses];

    return $item;
}

function ex(string $name, int $code, string $status, ?array $body, ?array $reqBody = null): array
{
    return [$name, $code, $status, $body, $reqBody];
}

$user = ['id' => 1, 'name' => 'Jane Doe', 'email' => 'jane@example.com', 'created_at' => '2026-10-20T19:49:37+00:00'];
$token = ['access_token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...', 'token_type' => 'bearer', 'expires_in' => 3600, 'user' => $user];
$unauth = ['message' => 'Unauthenticated.'];
$forbidden = ['message' => 'You are not authorized to access this resource.'];
$orderNotFound = ['message' => 'Order not found.'];

$items = [
    ['product_name' => 'Keyboard', 'quantity' => 2, 'price' => 150.25],
    ['product_name' => 'Mouse', 'quantity' => 1, 'price' => 49.5],
];
$orderBody = [
    'customer_name' => 'Jane Doe',
    'customer_email' => 'jane@example.com',
    'customer_phone' => '+966500000000',
    'shipping_address' => 'King Fahd Rd, Riyadh',
    'items' => $items,
];
$order = [
    'id' => 1,
    'status' => 'pending',
    'customer' => ['name' => 'Jane Doe', 'email' => 'jane@example.com', 'phone' => '+966500000000', 'shipping_address' => 'King Fahd Rd, Riyadh'],
    'items' => [
        ['id' => 1, 'product_name' => 'Keyboard', 'quantity' => 2, 'price' => 150.25, 'subtotal' => 300.5],
        ['id' => 2, 'product_name' => 'Mouse', 'quantity' => 1, 'price' => 49.5, 'subtotal' => 49.5],
    ],
    'total' => 350,
    'created_at' => '2026-10-20T19:49:37+00:00',
    'updated_at' => '2026-10-20T19:49:37+00:00',
];
$confirmed = array_merge($order, ['status' => 'confirmed', 'payments_count' => 0]);

function page(array $data, string $path, int $perPage = 15): array
{
    return [
        'data' => $data,
        'links' => ['first' => "http://localhost:8000/api/{$path}?page=1", 'last' => "http://localhost:8000/api/{$path}?page=1", 'prev' => null, 'next' => null],
        'meta' => ['current_page' => 1, 'from' => 1, 'last_page' => 1, 'path' => "http://localhost:8000/api/{$path}", 'per_page' => $perPage, 'to' => count($data), 'total' => count($data)],
    ];
}

$cardPayload = ['method' => 'credit_card', 'payment_details' => [
    'card_number' => '4242424242424242', 'card_holder' => 'Jane Doe', 'expiry_month' => 12, 'expiry_year' => 2030, 'cvv' => '123',
]];
$paypalPayload = ['method' => 'paypal', 'payment_details' => ['paypal_email' => 'buyer@example.com']];
$payment = [
    'id' => 1, 'order_id' => 1, 'method' => 'credit_card', 'status' => 'successful', 'amount' => 350,
    'transaction_reference' => 'cc_8TQK2M4ZP1XWJ7RA', 'failure_reason' => null, 'meta' => ['card_last4' => '4242'],
    'created_at' => '2026-10-20T19:49:39+00:00',
];
$failedPayment = array_merge($payment, ['id' => 2, 'status' => 'failed', 'failure_reason' => 'Card declined by issuer.', 'meta' => ['card_last4' => '0002'], 'transaction_reference' => 'cc_3NWE9BK0Q5DLZ8YT']);
$paypalPayment = array_merge($payment, ['method' => 'paypal', 'transaction_reference' => 'pp_RRNRPUPOCBLFKPUW', 'meta' => ['payer_email' => 'buyer@example.com', 'mode' => 'sandbox']]);

$loginTest = [[
    'listen' => 'test',
    'script' => ['type' => 'text/javascript', 'exec' => [
        'if (pm.response.code === 200 || pm.response.code === 201) {',
        '    pm.collectionVariables.set("token", pm.response.json().access_token);',
        '}',
    ]],
]];

$register = req('Register', 'POST', 'auth/register', [
    'name' => 'Jane Doe', 'email' => 'jane@example.com', 'password' => 'secret123', 'password_confirmation' => 'secret123',
], false, [], 'Creates a user and returns a JWT. The token is saved to the `token` collection variable automatically.', [
    ex('201 Created', 201, 'Created', $token),
    ex('422 Validation error', 422, 'Unprocessable Content', [
        'message' => 'The email has already been taken. (and 1 more error)',
        'errors' => ['email' => ['The email has already been taken.'], 'password' => ['The password field must be at least 8 characters.']],
    ], ['name' => 'Jane Doe', 'email' => 'jane@example.com', 'password' => 'short', 'password_confirmation' => 'short']),
]);
$register['event'] = $loginTest;

$login = req(
    'Login',
    'POST',
    'auth/login',
    ['email' => 'jane@example.com', 'password' => 'secret123'],
    false,
    [],
    'Returns a JWT. Saved to `token` automatically. Rate limited to 10 requests/minute per IP.',
    [
        ex('200 OK', 200, 'OK', $token),
        ex('401 Invalid credentials', 401, 'Unauthorized', ['message' => 'Invalid email or password.'], ['email' => 'jane@example.com', 'password' => 'wrong']),
        ex('422 Validation error', 422, 'Unprocessable Content', ['message' => 'The email field is required.', 'errors' => ['email' => ['The email field is required.']]], ['password' => 'secret123']),
    ]
);
$login['event'] = $loginTest;

$collection = [
    'info' => [
        'name' => 'Order & Payment API',
        'description' => "Extendable Order and Payment Management API (Laravel + JWT).\n\n1. Run **Auth → Register** or **Login**; the token is stored in `{{token}}` automatically.\n2. All other requests use Bearer auth with `{{token}}`.\n3. Payments require the order to be `confirmed` first (Orders → Update Order).\n\nSimulation test data:\n- Card `4000000000000002` is always declined.\n- PayPal emails ending in `@decline.test` are always rejected.",
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
    ],
    'auth' => ['type' => 'bearer', 'bearer' => [['key' => 'token', 'value' => '{{token}}', 'type' => 'string']]],
    'variable' => [
        ['key' => 'base_url', 'value' => 'http://localhost:8000/api'],
        ['key' => 'token', 'value' => ''],
        ['key' => 'order_id', 'value' => '1'],
        ['key' => 'payment_id', 'value' => '1'],
    ],
    'item' => [
        [
            'name' => 'Authentication',
            'item' => [
                $register,
                $login,
                req('Me', 'GET', 'auth/me', null, true, [], 'Returns the authenticated user.', [
                    ex('200 OK', 200, 'OK', ['data' => $user]),
                    ex('401 Unauthenticated', 401, 'Unauthorized', $unauth),
                ]),
                req('Refresh Token', 'POST', 'auth/refresh', null, true, [], 'Issues a new token and invalidates the current one.', [
                    ex('200 OK', 200, 'OK', $token),
                ]),
                req('Logout', 'POST', 'auth/logout', null, true, [], 'Invalidates the current token.', [
                    ex('200 OK', 200, 'OK', ['message' => 'Successfully logged out.']),
                ]),
            ],
        ],
        [
            'name' => 'Orders',
            'item' => [
                req(
                    'List Orders',
                    'GET',
                    'orders',
                    null,
                    true,
                    ['status' => 'confirmed', 'per_page' => 15, 'page' => 1],
                    "Lists the authenticated user's orders, newest first, paginated.\n\nQuery: `status` (pending|confirmed|cancelled, optional), `per_page` (1-100, default 15), `page`.",
                    [
                        ex('200 OK', 200, 'OK', page([$confirmed], 'orders')),
                        ex('422 Invalid status filter', 422, 'Unprocessable Content', ['message' => 'Status must be one of: pending, confirmed, cancelled.', 'errors' => ['status' => ['Status must be one of: pending, confirmed, cancelled.']]]),
                    ]
                ),
                req(
                    'Create Order',
                    'POST',
                    'orders',
                    $orderBody,
                    true,
                    [],
                    'Creates an order. `total` is always calculated server-side from items (any client-sent total is ignored). Status defaults to `pending`.',
                    [
                        ex('201 Created', 201, 'Created', ['data' => $order]),
                        ex('422 Validation error', 422, 'Unprocessable Content', [
                            'message' => 'The customer name field is required. (and 3 more errors)',
                            'errors' => [
                                'customer_name' => ['The customer name field is required.'],
                                'customer_email' => ['The customer email field must be a valid email address.'],
                                'items.0.quantity' => ['Each item quantity must be at least 1.'],
                                'items.0.price' => ['Each item price must be greater than zero.'],
                            ],
                        ], ['customer_email' => 'not-an-email', 'items' => [['product_name' => 'Keyboard', 'quantity' => 0, 'price' => -5]]]),
                        ex('401 Unauthenticated', 401, 'Unauthorized', $unauth),
                    ]
                ),
                req('Get Order', 'GET', 'orders/{{order_id}}', null, true, [], 'Returns one order with its items.', [
                    ex('200 OK', 200, 'OK', ['data' => array_merge($order, ['payments_count' => 0])]),
                    ex('403 Not your order', 403, 'Forbidden', $forbidden),
                    ex('404 Not found', 404, 'Not Found', $orderNotFound),
                ]),
                req(
                    'Update Order',
                    'PATCH',
                    'orders/{{order_id}}',
                    ['status' => 'confirmed', 'customer_phone' => '+966511111111'],
                    true,
                    [],
                    "Partially updates an order. All fields optional. Sending `items` replaces all items and recalculates the total; this is blocked (409) once the order has payments.\n\nSet `status` to `confirmed` to allow payment.",
                    [
                        ex('200 OK', 200, 'OK', ['data' => array_merge($confirmed, ['customer' => array_merge($order['customer'], ['phone' => '+966511111111'])])]),
                        ex('409 Items locked', 409, 'Conflict', ['message' => 'Order items cannot be changed once payments exist for the order.'], ['items' => [['product_name' => 'Monitor', 'quantity' => 1, 'price' => 900]]]),
                        ex('422 Validation error', 422, 'Unprocessable Content', ['message' => 'The selected status is invalid.', 'errors' => ['status' => ['The selected status is invalid.']]], ['status' => 'shipped']),
                    ]
                ),
                req('Delete Order', 'DELETE', 'orders/{{order_id}}', null, true, [], 'Deletes an order and its items. Rejected with 409 if the order has any payments.', [
                    ex('204 No Content', 204, 'No Content', null),
                    ex('409 Has payments', 409, 'Conflict', ['message' => 'Orders with associated payments cannot be deleted.']),
                    ex('403 Not your order', 403, 'Forbidden', $forbidden),
                ]),
            ],
        ],
        [
            'name' => 'Payments',
            'item' => [
                req('List Payment Methods', 'GET', 'payment-methods', null, true, [], 'Lists enabled gateways (driven by config/payments.php).', [
                    ex('200 OK', 200, 'OK', ['data' => ['credit_card', 'paypal']]),
                ]),
                req(
                    'Process Payment (Credit Card)',
                    'POST',
                    'orders/{{order_id}}/payments',
                    $cardPayload,
                    true,
                    [],
                    "Processes a simulated payment. The order must be `confirmed` and not already paid.\n\nA declined charge still returns **201** with `status: failed`, because the attempt is recorded. Use card `4000000000000002` to simulate a decline.\n\n`payment_details` rules depend on the gateway.",
                    [
                        ex('201 Successful', 201, 'Created', ['data' => $payment]),
                        ex('201 Declined (recorded as failed)', 201, 'Created', ['data' => $failedPayment], array_merge($cardPayload, ['payment_details' => array_merge($cardPayload['payment_details'], ['card_number' => '4000000000000002'])])),
                        ex('422 Order not confirmed', 422, 'Unprocessable Content', ['message' => 'Payments can only be processed for orders in the confirmed status.']),
                        ex('409 Already paid', 409, 'Conflict', ['message' => 'This order has already been paid successfully.']),
                        ex('422 Gateway validation error', 422, 'Unprocessable Content', [
                            'message' => 'The payment details.card number field must be between 13 and 19 digits. (and 2 more errors)',
                            'errors' => [
                                'payment_details.card_number' => ['The payment details.card number field must be between 13 and 19 digits.'],
                                'payment_details.card_holder' => ['The payment details.card holder field is required.'],
                                'payment_details.cvv' => ['The payment details.cvv field is required.'],
                            ],
                        ], ['method' => 'credit_card', 'payment_details' => ['card_number' => 'abc', 'expiry_month' => 12, 'expiry_year' => 2030]]),
                        ex('422 Unsupported method', 422, 'Unprocessable Content', [
                            'message' => 'The selected payment method is not supported. Supported methods: credit_card, paypal.',
                            'errors' => ['method' => ['The selected payment method is not supported. Supported methods: credit_card, paypal.']],
                        ], ['method' => 'bitcoin', 'payment_details' => []]),
                    ]
                ),
                req(
                    'Process Payment (PayPal)',
                    'POST',
                    'orders/{{order_id}}/payments',
                    $paypalPayload,
                    true,
                    [],
                    'PayPal simulation. Emails ending in `@decline.test` are rejected.',
                    [
                        ex('201 Successful', 201, 'Created', ['data' => $paypalPayment]),
                        ex('201 Rejected (recorded as failed)', 201, 'Created', ['data' => array_merge($paypalPayment, ['status' => 'failed', 'failure_reason' => 'PayPal account rejected the payment.', 'meta' => ['payer_email' => 'x@decline.test', 'mode' => 'sandbox']])], ['method' => 'paypal', 'payment_details' => ['paypal_email' => 'x@decline.test']]),
                    ]
                ),
                req('List Payments for Order', 'GET', 'orders/{{order_id}}/payments', null, true, ['per_page' => 15], 'Paginated payments (all attempts) for one order.', [
                    ex('200 OK', 200, 'OK', page([$failedPayment, $payment], 'orders/1/payments')),
                    ex('404 Not found', 404, 'Not Found', $orderNotFound),
                ]),
                req(
                    'List All Payments',
                    'GET',
                    'payments',
                    null,
                    true,
                    ['status' => 'successful', 'method' => 'credit_card', 'per_page' => 15],
                    "All payments across the user's orders, paginated.\n\nQuery: `status` (pending|successful|failed), `method`, `per_page`, `page`.",
                    [
                        ex('200 OK', 200, 'OK', page([$payment], 'payments')),
                    ]
                ),
                req('Get Payment', 'GET', 'payments/{{payment_id}}', null, true, [], 'Returns a single payment.', [
                    ex('200 OK', 200, 'OK', ['data' => $payment]),
                    ex('403 Not your payment', 403, 'Forbidden', $forbidden),
                    ex('404 Not found', 404, 'Not Found', ['message' => 'Payment not found.']),
                ]),
            ],
        ],
    ],
];

file_put_contents(
    __DIR__.'/Order-Payment-API.postman_collection.json',
    json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n",
);

echo "Collection written.\n";
