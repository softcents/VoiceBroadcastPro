<?php

declare(strict_types=1);

return [
    'default' => 'default',

    'replications' => [
        'default' => [
            'host' => env('TRIGGER_HOST', ''),
            'port' => (int) env('TRIGGER_PORT', 3306),
            'user' => env('TRIGGER_USER', ''),
            'password' => env('TRIGGER_PASSWORD', ''),

            // detect from trigger routers
            'detect' => (bool) env('TRIGGER_DETECT', false),
            // or set database and tables
            'databases' => env('TRIGGER_DATABASES', '') ? explode(',', env('TRIGGER_DATABASES')) : [],
            'tables' => env('TRIGGER_TABLES', '') ? explode(',', env('TRIGGER_TABLES')) : [],

            'heartbeat' => (int) env('TRIGGER_HEARTBEAT', 3),

            // Periodically ping the MySQL metadata connection to avoid server-side idle disconnects.
            // Set to 0 to disable.
            'keepalive' => (int) env('TRIGGER_KEEPALIVE', 0),

            // MySQL session variables to apply on connect (for the metadata connection).
            // Example:
            // - wait_timeout=7200,interactive_timeout=7200
            'session_variables' => env('TRIGGER_SESSION_VARIABLES', '')
                ? array_filter(array_map('trim', explode(',', (string) env('TRIGGER_SESSION_VARIABLES'))))
                : [],
            'subscribers' => [
                App\Support\Trigger\Subscribers\Heartbeat::class,
            ],
            'route' => app()->basePath('routes/trigger.php'),
        ],
    ],
];
