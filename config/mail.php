<?php

declare(strict_types=1);

return [
    'default' => env('MAIL_MAILER', 'log'),

    'mailers' => [
        'smtp' => [
            'transport' => 'smtp',
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => env('MAIL_PORT', 2525),
            'encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],
    ],

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'no-reply@evento.com'),
        'name' => env('MAIL_FROM_NAME', 'Evento'),
    ],

    'templates' => [
        'contact_organizer' => env('SENDGRID_CONTACT_ORGANIZER_TEMPLATE_ID', 'd-contact-organizer'),
        'ticket_confirmation' => env('SENDGRID_TICKET_TEMPLATE_ID', 'd-ticket-confirmation'),
    ],
];
