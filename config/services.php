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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'whatsapp' => [
        'gateway_url' => env('WHATSAPP_GATEWAY_URL', 'http://127.0.0.1:3000'),
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN', 'antigravity_token'),
        'meta_token' => env('META_WHATSAPP_TOKEN'),
        'meta_phone_id' => env('META_WHATSAPP_PHONE_NUMBER_ID'),
        'meta_waba_id' => env('META_WHATSAPP_WABA_ID'),
    ],

    'meesho' => [
        'bot_url' => env('MEESHO_BOT_URL', 'http://127.0.0.1:3001'),
        'auto_accept' => env('MEESHO_AUTO_ACCEPT', true),
        'auto_download_label' => env('MEESHO_AUTO_DOWNLOAD_LABEL', true),
    ],

];
