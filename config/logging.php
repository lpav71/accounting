<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "custom", "stack"
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['daily'],
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => 'debug',
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel_log/laravel.log'),
            'level' => 'debug',
            'days' => 0,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => 'critical',
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => 'debug',
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => 'debug',
        ],

        'request_log' => [
            'driver' => 'daily',
            'path' => storage_path('logs/request_log/request.log'),
            'level' => 'debug',
            'days' => 0,
        ],

        'security_log' => [
            'driver' => 'daily',
            'path' => storage_path('logs/security_log/security.log'),
            'level' => 'debug',
            'days' => 0,
        ],

        'utm_no_log' => [
            'driver' => 'daily',
            'path' => storage_path('logs/utm_no_log/utm_no.log'),
            'level' => 'debug',
            'days' => 0,
        ],

        'urgent_call_log' => [
            'driver' => 'daily',
            'path' => storage_path('logs/urgent_call_log/urgent_call.log'),
            'level' => 'debug',
            'days' => 0,
        ],

        'worker_log' => [
            'driver' => 'daily',
            'path' => storage_path('logs/worker_log/worker.log'),
            'level' => 'debug',
            'days' => 0,
        ],

        'content_control_log' => [
            'driver' => 'daily',
            'path' => storage_path('logs/content_control_log/content_control.log'),
            'level' => 'debug',
            'days' => 0,
        ],

        'telephony_webhook_log' => [
            'driver' => 'daily',
            'path' => storage_path('logs/telephony_webhook_log/telephony_webhook.log'),
            'level' => 'debug',
            'days' => 0,
        ],

    ],

];
