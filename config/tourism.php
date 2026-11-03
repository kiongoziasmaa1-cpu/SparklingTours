<?php

return [
    'timezone' => 'Africa/Dar_es_Salaam',
    'captcha' => [
        'enabled' => env('CAPTCHA_ENABLED', true),
        'site_key' => env('TURNSTILE_SITE_KEY', ''),
        'secret' => env('TURNSTILE_SECRET_KEY', ''),
        'hostnames' => array_filter(explode(',', env('TURNSTILE_HOSTNAMES', 'localhost,127.0.0.1'))),
    ],
    'document_link_hours' => 72,
];
