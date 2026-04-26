<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'mymemory' => [
        'base_url' => env('MYMEMORY_BASE_URL', 'https://api.mymemory.translated.net'),
        'timeout' => env('MYMEMORY_TIMEOUT', 10),
        'mt' => env('MYMEMORY_MT', true),
    ],

    'notisend' => [
        'base_url' => env('NOTISEND_API_URL', 'https://api.notisend.ru/v1'),
        'reserve_base_url' => env('NOTISEND_API_RESERVE_URL', 'https://api-reserve.msndr.net/v1'),
        'api_token' => env('NOTISEND_API_TOKEN'),
        'timeout' => env('NOTISEND_TIMEOUT', 20),
        'from_email' => env('NOTISEND_FROM_EMAIL', env('MAIL_FROM_ADDRESS')),
        'from_name' => env('NOTISEND_FROM_NAME', env('MAIL_FROM_NAME', env('APP_NAME', 'Laravel'))),
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
    ],

];
