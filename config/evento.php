<?php

declare(strict_types=1);

return [
    'assets_url' => env('EVENTO_ASSETS_URL', ''),

    'hashids_salt' => env('APP_SALT', null),
    'legacy_hashids_salt' => env('EVENTO_LEGACY_HASHIDS_SALT', '1'),
    'hashids_length' => (int) env('EVENTO_HASHIDS_LENGTH', 6),
    'random_code_hashids_length' => (int) env('EVENTO_RANDOM_CODE_HASHIDS_LENGTH', 9),
    'ticket_prefix' => env('EVENTO_TICKET_PREFIX', 'EVENTO'),
    'random_code_prefix' => env('EVENTO_RANDOM_CODE_PREFIX', 'EVT-'),

    'defaults' => [
        'avatar_url' => env('EVENTO_DEFAULT_AVATAR_URL', 'https://i.pravatar.cc/150?u=noavatar'),
        'profile_image_url' => env('EVENTO_DEFAULT_PROFILE_IMAGE_URL', 'https://picsum.photos/1200/480'),
        'hero_image_url' => env('EVENTO_DEFAULT_HERO_IMAGE_URL', 'https://picsum.photos/1200/640'),
    ],

    'oauth' => [
        'token_name' => env('EVENTO_OAUTH_TOKEN_NAME', 'Evento Password Grant Token'),
        'password_client_name' => env('EVENTO_OAUTH_PASSWORD_CLIENT_NAME', 'Evento Password Grant Client'),
        'personal_client_name' => env('EVENTO_OAUTH_PERSONAL_CLIENT_NAME', 'Evento Personal Access Client'),
        'token_ttl_seconds' => (int) env('EVENTO_OAUTH_TOKEN_TTL_SECONDS', 31536000),
        'client_cache_ttl' => (int) env('EVENTO_OAUTH_CLIENT_CACHE_TTL', 86500),
    ],

    'stripe_key' => env('STRIPE_KEY', ''),
    'stripe_secret' => env('STRIPE_SECRET', ''),
    'stripe_webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),
    'stripe_checkout_url' => env('STRIPE_CHECKOUT_URL', 'https://checkout.stripe.com/pay/cs_test_'),
    'cashier_currency' => env('CASHIER_CURRENCY', 'aud'),

    'vendor_percentage_fee' => (float) env('EVENTO_VENDOR_PERCENTAGE_FEE', 0.05),
    'vendor_fixed_fee' => (float) env('EVENTO_VENDOR_FIXED_FEE', 0.50),
    'gateway_percentage_fee' => (float) env('EVENTO_GATEWAY_PERCENTAGE_FEE', 3.50),
    'gateway_fixed_fee' => (float) env('EVENTO_GATEWAY_FIXED_FEE', 0.99),
    'platform_fee_type' => env('EVENTO_PLATFORM_FEE_TYPE', 'evento'),
    'platform_percentage_fee' => (float) env('EVENTO_PLATFORM_PERCENTAGE_FEE', 2.00),
    'platform_fixed_fee' => (float) env('EVENTO_PLATFORM_FIXED_FEE', 0.00),

    'sendgrid_api_key' => env('SENDGRID_API_KEY', ''),
    'snipcart_secret_key' => env('SNIPCART_SECRET_KEY', ''),
    'snipcart_webhook_secret' => env('SNIPCART_WEBHOOK_SECRET', env('SNIPCART_SECRET_KEY', '')),
    'door_checkin_key' => env('EVENTO_DOOR_CHECKIN_KEY', ''),
    'webhook_timeout_seconds' => (int) env('WEBHOOK_DISPATCH_TIMEOUT_SECONDS', 10),

    'is_saas_mode' => (bool) env('EVENTO_SAAS_MODE', false),

    'rate_limits' => [
        'auth_login' => (int) env('RATE_LIMIT_AUTH_LOGIN', 10),
        'auth_register' => (int) env('RATE_LIMIT_AUTH_REGISTER', 10),
        'auth_password' => (int) env('RATE_LIMIT_AUTH_PASSWORD', 5),
        'public_checkout' => (int) env('RATE_LIMIT_PUBLIC_CHECKOUT', 30),
        'public_attendees' => (int) env('RATE_LIMIT_PUBLIC_ATTENDEES', 30),
        'public_bookings' => (int) env('RATE_LIMIT_PUBLIC_BOOKINGS', 60),
        'contact_organizer' => (int) env('RATE_LIMIT_CONTACT_ORGANIZER', 5),
        'door_checkin' => (int) env('RATE_LIMIT_DOOR_CHECKIN', 120),
    ],

    'spam_guard' => [
        'max_links' => (int) env('SPAM_GUARD_MAX_LINKS', 5),
    ],

    'features' => [
        'snipcart_enabled' => (bool) env('FEATURE_SNIPCART_ENABLED', true),
    ],

    'google_calendar' => [
        'impersonate_email' => env('GOOGLE_CALENDAR_IMPERSONATE_EMAIL', 'event@evento.com'),
        'prodid' => env('GOOGLE_CALENDAR_PRODID', '-//Evento//EN'),
    ],
];
