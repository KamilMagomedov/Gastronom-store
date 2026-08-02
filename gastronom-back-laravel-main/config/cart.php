<?php

return [
    'expires' => [
        'customer' => env('CART_EXPIRES_CUSTOMER', 43200),
        'guest' => env('CART_EXPIRES_GUEST', 10080),
    ],
];
