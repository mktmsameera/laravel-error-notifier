<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Enable Error Notifications
    |--------------------------------------------------------------------------
    |
    | Set to false to completely disable error notifications
    |
    */
    'enabled' => env('ERROR_NOTIFIER_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Environment Filter
    |--------------------------------------------------------------------------
    |
    | Only send notifications for these environments
    |
    */
    'environments' => [
        'production',
        'staging',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Prevent spam by limiting identical errors
    |
    */
    'rate_limit' => [
        'enabled' => true,
        'cache_key_prefix' => 'error_notifier_',
        'throttle_seconds' => 300, // 5 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Exception Filters
    |--------------------------------------------------------------------------
    |
    | Control which exceptions trigger notifications
    |
    */
    'filters' => [
        'include_status_codes' => [500, 503, 504],
        'exclude_exceptions' => [
            \Illuminate\Validation\ValidationException::class,
            \Illuminate\Auth\AuthenticationException::class,
            \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
        ],
        'max_message_length' => 2000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Channels
    |--------------------------------------------------------------------------
    |
    | Configure your notification channels
    |
    */
    'channels' => [
        'discord' => [
            'enabled' => env('DISCORD_ENABLED', false),
            'webhook_url' => env('DISCORD_WEBHOOK_URL'),
            'username' => env('DISCORD_USERNAME', 'Error Bot'),
            'mention_role' => env('DISCORD_MENTION_ROLE'), // Role ID to mention
        ],

        'slack' => [
            'enabled' => env('SLACK_ENABLED', false),
            'webhook_url' => env('SLACK_WEBHOOK_URL'),
            'channel' => env('SLACK_CHANNEL', '#errors'),
            'username' => env('SLACK_USERNAME', 'Error Bot'),
            'icon' => env('SLACK_ICON', ':warning:'),
        ],

        'whatsapp' => [
            'enabled' => env('WHATSAPP_ENABLED', false),
            'provider' => env('WHATSAPP_PROVIDER', 'twilio'), // twilio or whatsapp-business
            'twilio' => [
                'sid' => env('TWILIO_SID'),
                'token' => env('TWILIO_TOKEN'),
                'from' => env('TWILIO_WHATSAPP_FROM'), // whatsapp:+14155238886
                'to' => env('TWILIO_WHATSAPP_TO'), // whatsapp:+1234567890
            ],
            'whatsapp_business' => [
                'token' => env('WHATSAPP_BUSINESS_TOKEN'),
                'phone_id' => env('WHATSAPP_BUSINESS_PHONE_ID'),
                'to' => env('WHATSAPP_BUSINESS_TO'),
            ],
        ],

        'email' => [
            'enabled' => env('EMAIL_ENABLED', false),
            'to' => explode(',', env('ERROR_EMAIL_TO', '')),
            'from' => env('ERROR_EMAIL_FROM', env('MAIL_FROM_ADDRESS')),
            'subject_prefix' => env('ERROR_EMAIL_SUBJECT_PREFIX', '[ERROR]'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Settings
    |--------------------------------------------------------------------------
    |
    | Send notifications asynchronously using queues
    |
    */
    'queue' => [
        'enabled' => env('ERROR_NOTIFIER_QUEUE', true),
        'connection' => env('ERROR_NOTIFIER_QUEUE_CONNECTION', 'default'),
        'queue' => env('ERROR_NOTIFIER_QUEUE_NAME', 'notifications'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Application Information
    |--------------------------------------------------------------------------
    |
    | Information to include in notifications
    |
    */
    'app_info' => [
        'name' => env('APP_NAME', 'Laravel App'),
        'environment' => env('APP_ENV', 'production'),
        'url' => env('APP_URL'),
        'include_user_info' => true,
        'include_request_info' => true,
        'include_stack_trace' => env('ERROR_NOTIFIER_STACK_TRACE', false),
    ],
];