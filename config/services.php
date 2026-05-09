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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],
    'firebase' => [
        'credentials' => env('FIREBASE_CREDENTIALS'),
        'api_key' => env('FIREBASE_API_KEY'),
        'auth_domain' => env('FIREBASE_AUTH_DOMAIN'),
        'project_id' => env('FIREBASE_PROJECT_ID'),
        'messaging_sender_id' => env('FIREBASE_MESSAGING_SENDER_ID'),
        'app_id' => env('FIREBASE_APP_ID'),
        'vapid_key' => env('FIREBASE_VAPID_KEY'),
    ],
    'cinetpay' => [
        'api_key' => env('CINETPAY_API_KEY'),
        'site_id' => env('CINETPAY_SITE_ID'),
        'webhook_secret' => env('CINETPAY_WEBHOOK_SECRET'),
        'base_url' => env('CINETPAY_BASE_URL'),
        'password' => env('CINETPAY_PAYOUT_PASSWORD'),
    ],
    'exchangerate' => [
        'key' => env('EXCHANGERATE_API_KEY'),
    ],
    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'pispi' => [
        'base_url' => env('PISPI_BASE_URL', 'https://sandbox-api.pispi.bceao.int'),
        'client_id' => env('PISPI_CLIENT_ID'),
        'secret' => env('PISPI_SECRET'),
        'api_key' => env('PISPI_API_KEY'),
        'webhook_secret' => env('PISPI_WEBHOOK_SECRET'),
    ],

    'sms' => [
        'otp_timeout' => env('SMS_OTP_SECONDE_TIME_OUT', 120),
        'max_attempts' => env('SMS_OTP_ATTEMPT', 3),
        'max_attempt_time' => env('SMS_OTP_MAX_ATTEMPT', 120),
        'api_authorization' => env('SMS_API_AUTHORIZATION'),
        'smspro_api_authorization' => env('SMSPRO_API_AUTHORIZATION'),
        'sender_name' => env('SMS_SENDER_NAME', 'SMIL PAY'),
        'smspro_sender_name' => env('SMSPRO_SENDER_NAME', 'SMIL PAY'),
        'api_url' => env('SMS_API_URL'),
        'smspro_api_url' => env('SMSPRO_API_URL'),
        'wirepick_url' => env('WIREPICK_API_URL'),
        'wirepick_client' => env('WIREPICK_CLIENT'),
        'wirepick_password' => env('WIREPICK_PASSWORD'),
    ],


];
