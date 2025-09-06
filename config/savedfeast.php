<?php

return [
    'commission' => [
        'default_rate' => (float) env('SF_COMMISSION_DEFAULT_RATE', 7.0),
    ],
    'invoicing' => [
        'period' => env('SF_INVOICE_PERIOD', 'WEEKLY'), // keep for future variants
        'timezone' => env('APP_TIMEZONE', 'Asia/Beirut'),
        'invoice_day' => env('SF_INVOICE_WEEKLY_DAY', 'monday'), // generate each Monday for previous week
    ],
];
