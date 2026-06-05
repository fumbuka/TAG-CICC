<?php

return [
    'beem' => [
        'base_url' => env('BEEM_SMS_BASE_URL', 'https://apisms.beem.africa'),
        'api_key' => env('BEEM_SMS_API_KEY'),
        'secret_key' => env('BEEM_SMS_SECRET_KEY'),
        'sender_id' => env('BEEM_SMS_SENDER_ID'),
        'callback_token' => env('BEEM_SMS_CALLBACK_TOKEN'),
    ],
];
