<?php

return [
    'pickup_code' => [
        'length' => (int) env('SF_PICKUP_CODE_LENGTH', 6),
        'max_attempts' => (int) env('SF_PICKUP_CODE_MAX_ATTEMPTS', 5),
        'resend_cooldown_seconds' => (int) env('SF_PICKUP_CODE_RESEND_COOLDOWN', 90),
        'grace_minutes_after_window' => (int) env('SF_PICKUP_CODE_GRACE_MIN', 10),
    ],
    'timeouts' => [
        'pending_auto_cancel_minutes' => (int) env('SF_PENDING_AUTO_CANCEL_MIN', 15),
    ],
    'timezone' => env('APP_TIMEZONE', 'Asia/Beirut'),
    'realtime' => [
        'enabled' => env('SF_REALTIME_ENABLED', false),
    ],
];
