<?php

return [
    'default_driver' => env('WHATSAPP_DEFAULT_DRIVER', 'evolution'),

    'session_timeout_minutes' => (int) env('WHATSAPP_SESSION_TIMEOUT_MINUTES', 30),

    'meta' => [
        'verify_token' => env('META_WHATSAPP_VERIFY_TOKEN'),
        'app_secret' => env('META_WHATSAPP_APP_SECRET'),
        'access_token' => env('META_WHATSAPP_ACCESS_TOKEN'),
        'phone_number_id' => env('META_WHATSAPP_PHONE_NUMBER_ID'),
        'graph_version' => env('META_WHATSAPP_GRAPH_VERSION', 'v21.0'),
    ],

    'evolution' => [
        'base_url' => env('EVOLUTION_API_BASE_URL'),
        'api_key' => env('EVOLUTION_API_KEY'),
        'instance' => env('EVOLUTION_API_INSTANCE'),
        'webhook_token' => env('EVOLUTION_WEBHOOK_TOKEN'),
    ],

    'twilio' => [
        'account_sid' => env('TWILIO_ACCOUNT_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'from' => env('TWILIO_WHATSAPP_FROM'),
    ],
];
