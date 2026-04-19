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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', env('APP_URL', 'http://localhost').'/auth/google/callback'),
    ],

    'qr_wallets' => [
        'merchant_name' => env('QR_MERCHANT_NAME', 'The Grand Lion Hotel'),
        'instapay' => [
            'label' => 'InstaPay',
            'holder_name' => env('INSTAPAY_HOLDER_NAME', env('GCASH_HOLDER_NAME', env('QR_MERCHANT_NAME', 'The Grand Lion Hotel'))),
            'number' => env('INSTAPAY_NUMBER', env('GCASH_NUMBER', '0917-123-4567')),
            'qr_image_url' => env('INSTAPAY_QR_IMAGE_URL', env('GCASH_QR_IMAGE_URL')),
            'qr_payload' => env('INSTAPAY_QR_PAYLOAD', env('GCASH_QR_PAYLOAD')),
            'app_link' => env('INSTAPAY_APP_LINK', 'https://www.bsp.gov.ph/PaymentAndSettlement/Instapay'),
        ],
        'credit_debit_card' => [
            'label' => 'Credit/Debit Card',
            'holder_name' => env('CARD_HOLDER_NAME', env('QR_MERCHANT_NAME', 'The Grand Lion Hotel')),
            'acceptance_note' => env('CARD_ACCEPTANCE_NOTE', 'Accepted at hotel front desk terminal.'),
        ],
    ],

];
