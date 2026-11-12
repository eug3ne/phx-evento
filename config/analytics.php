<?php

declare(strict_types=1);

return [
    'facebook_pixel_id' => env('ANALYTICS_FACEBOOK_PIXEL_ID', ''),
    'google_analytics_id' => env('ANALYTICS_GOOGLE_ANALYTICS_ID', ''),
    'google_tag_manager_id' => env('ANALYTICS_GOOGLE_TAG_MANAGER_ID', ''),
    'tiktok_pixel_id' => env('ANALYTICS_TIKTOK_PIXEL_ID', ''),

    'allow_gtm_in_saas' => (bool) env('ANALYTICS_ALLOW_GTM_IN_SAAS', false),

    'patterns' => [
        'facebook_pixel_id' => env('ANALYTICS_FACEBOOK_PIXEL_PATTERN', '/^\d{9,20}$/'),
        'google_analytics_id' => env('ANALYTICS_GOOGLE_ANALYTICS_PATTERN', '/^G-[A-Z0-9]{6,20}$/'),
        'google_tag_manager_id' => env('ANALYTICS_GOOGLE_TAG_MANAGER_PATTERN', '/^GTM-[A-Z0-9]{4,20}$/'),
        'tiktok_pixel_id' => env('ANALYTICS_TIKTOK_PIXEL_PATTERN', '/^[A-Za-z0-9]{6,30}$/'),
    ],

    'utm_fields' => [
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'gclid',
        'fbclid',
        'referrer_url',
    ],
];
