<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    // Social sign-in. A provider's button only shows once its client id is set.
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => '/auth/google/callback',
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => '/auth/facebook/callback',
    ],

    'microsoft' => [
        'client_id' => env('MICROSOFT_CLIENT_ID'),
        'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
        // "common" lets in both personal (Outlook, Hotmail) and work or school accounts.
        'tenant' => env('MICROSOFT_TENANT_ID', 'common'),
        'redirect' => '/auth/microsoft/callback',
    ],

    // client_id is the Services ID; the .p8 key signs a fresh client secret for each sign-in.
    'apple' => [
        'client_id' => env('APPLE_CLIENT_ID'),
        'client_secret' => null,
        'team_id' => env('APPLE_TEAM_ID'),
        'key_id' => env('APPLE_KEY_ID'),
        'private_key' => env('APPLE_PRIVATE_KEY'),
        'redirect' => '/auth/apple/callback',
    ],

    // Starting values only: the team sets the key and payment methods in /admin/payments.
    'paystack' => [
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
        // Where Paystack sends the browser after checkout; defaults to the /payments/done page.
        'callback_url' => env('PAYSTACK_CALLBACK_URL'),
    ],

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

];
