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

    'sms' => [
        // "log" writes messages to the log (local/testing only); "smartping" sends real SMS.
        // Once a super admin saves Admin → SMS Settings, its on/off switch decides instead.
        'driver' => env('SMS_DRIVER', 'log'),
    ],

    // Fallbacks for Admin → SMS Settings; values saved there take precedence.
    'smartping' => [
        // `?:` so a blank KEY= line in .env falls back to the default instead of ''.
        'base_url' => env('SMARTPING_BASE_URL') ?: 'https://pgapi.sparc.smartping.io/fe/api/v1/send',
        'username' => env('SMARTPING_USERNAME') ?: null,
        'password' => env('SMARTPING_PASSWORD') ?: null,
        'sender_id' => env('SMARTPING_SENDER_ID') ?: 'INSARS',
        'dlt_content_id' => env('SMARTPING_DLT_CONTENT_ID') ?: '1707177677498830200',
        'dlt_principal_entity_id' => env('SMARTPING_DLT_PRINCIPAL_ID') ?: '1701166126846262605',
        // DLT-registered template: must be sent character for character; only {otp} changes.
        'otp_message' => env('SMARTPING_OTP_MESSAGE') ?: 'Hi! Your verification code is {otp}. It is valid for 10 minutes. Please keep it confidential. - Sars Infotech Pvt Ltd',
        'timeout' => (int) (env('SMARTPING_TIMEOUT') ?: 15),
    ],

];
