<?php

return [
    'api_base'      => env('PASSKIT_API_BASE', 'https://api.pub1.passkit.io'),
    'program_id'    => env('PASSKIT_PROGRAM_ID', ''),
    'tier_id'       => env('PASSKIT_TIER_ID', 'gift_card'),
    'public_base'   => env('PASSKIT_PUBLIC_BASE', 'https://pub1.pskt.io/'),
    'debug'         => (bool) env('PASSKIT_DEBUG_LOG', true),

    // Credentials for HS256 JWT
    'api_key'       => env('PASSKIT_API_KEY', ''),
    'api_secret'    => env('PASSKIT_API_SECRET', ''),

    'default_email' => env('PASSKIT_DEFAULT_EMAIL', 'kristo@tactica.is'),

];

