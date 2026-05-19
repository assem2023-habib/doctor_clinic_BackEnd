<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Blocking Strategies
    |--------------------------------------------------------------------------
    |
    | استراتيجيات الحظر التي يتم تطبيقها قبل تسجيل الدخول
    | يتم ترتيبها حسب الأولوية (الأقل رقماً ينفذ أولاً)
    |
    */
    'strategies' => [
        'fingerprint' => [
            'enabled' => true,
            'priority' => 10,
        ],
        'ip' => [
            'enabled' => true,
            'priority' => 20,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limits
    |--------------------------------------------------------------------------
    |
    */
    'limits' => [
        'max_failures_per_email_per_hour' => 5,
        'max_failures_per_ip_per_15_minutes' => 10,
        'max_failures_per_fingerprint_per_30_minutes' => 5,
        'max_unique_fingerprints_per_email_per_10_minutes' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Block Durations (in minutes)
    |--------------------------------------------------------------------------
    |
    */
    'block_durations' => [
        'fingerprint_temporary' => 60,
        'ip_temporary' => 30,
        'ip_permanent' => 1440,
    ],

    /*
    |--------------------------------------------------------------------------
    | Device Trust
    |--------------------------------------------------------------------------
    |
    | Automatically trust a device after N successful logins from it
    |
    */
    'device_trust' => [
        'auto_trust_after_logins' => 3,
        'max_trusted_devices' => 10,
    ],
];
