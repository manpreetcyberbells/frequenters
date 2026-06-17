<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Twilio Credentials
    |--------------------------------------------------------------------------
    | These are read from your .env file. NEVER hard-code them here.
    */
    'twilio' => [
        'account_sid'         => env('TWILIO_ACCOUNT_SID'),
        'auth_token'          => env('TWILIO_AUTH_TOKEN'),
        'default_from_number' => env('TWILIO_DEFAULT_FROM_NUMBER'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Behaviour
    |--------------------------------------------------------------------------
    | Per-business values in connect_business_settings override these.
    */
    'defaults' => [
        'duplicate_window_hours'             => (int) env('CONNECT_DEFAULT_DUPLICATE_WINDOW_HOURS', 24),
        'active_conversation_window_minutes' => (int) env('CONNECT_DEFAULT_ACTIVE_CONVERSATION_WINDOW_MINUTES', 30),
        'require_code_when_multiple_active'  => true,
        'append_stop_footer'                 => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    | When true, every incoming Twilio webhook must carry a valid
    | X-Twilio-Signature header or it is rejected (HTTP 403).
    | Turn this OFF only for local testing.
    */
    'validate_twilio_signature' => (bool) env('CONNECT_VALIDATE_TWILIO_SIGNATURE', true),

    /*
    |--------------------------------------------------------------------------
    | Default Region for Phone Normalisation
    |--------------------------------------------------------------------------
    | Used when a number has no country code. "1" = North America (+1).
    */
    'default_country_code' => env('CONNECT_DEFAULT_COUNTRY_CODE', '1'),
];
