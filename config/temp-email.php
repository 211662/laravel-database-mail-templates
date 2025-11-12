<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Email Lifetime (in hours)
    |--------------------------------------------------------------------------
    |
    | The default lifetime for temporary emails in hours.
    | After this time, emails will be marked as expired.
    |
    */
    'default_lifetime' => env('TEMP_EMAIL_LIFETIME', 2),

    /*
    |--------------------------------------------------------------------------
    | Maximum Email Lifetime (in hours)
    |--------------------------------------------------------------------------
    |
    | The maximum lifetime that can be requested for a temp email.
    |
    */
    'max_lifetime' => env('TEMP_EMAIL_MAX_LIFETIME', 24),

    /*
    |--------------------------------------------------------------------------
    | Cleanup Settings
    |--------------------------------------------------------------------------
    |
    | Settings for cleaning up expired emails and old messages.
    |
    */
    'cleanup' => [
        'enabled' => env('TEMP_EMAIL_CLEANUP_ENABLED', true),
        'delete_after_days' => env('TEMP_EMAIL_DELETE_AFTER_DAYS', 7),
        'schedule' => env('TEMP_EMAIL_CLEANUP_SCHEDULE', 'daily'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limiting settings to prevent abuse.
    |
    */
    'rate_limit' => [
        'enabled' => env('TEMP_EMAIL_RATE_LIMIT_ENABLED', true),
        'max_requests_per_hour' => env('TEMP_EMAIL_MAX_REQUESTS_PER_HOUR', 10),
        'max_emails_per_ip' => env('TEMP_EMAIL_MAX_PER_IP', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | 2FA Code Detection
    |--------------------------------------------------------------------------
    |
    | Settings for automatically detecting 2FA codes in emails.
    |
    */
    'two_fa' => [
        'enabled' => env('TEMP_EMAIL_2FA_DETECTION', true),
        'highlight' => env('TEMP_EMAIL_2FA_HIGHLIGHT', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Attachments
    |--------------------------------------------------------------------------
    |
    | Settings for handling email attachments.
    |
    */
    'attachments' => [
        'enabled' => env('TEMP_EMAIL_ATTACHMENTS_ENABLED', true),
        'max_size' => env('TEMP_EMAIL_MAX_ATTACHMENT_SIZE', 5242880), // 5MB in bytes
        'allowed_types' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Broadcasting
    |--------------------------------------------------------------------------
    |
    | Enable real-time notifications when new emails arrive.
    |
    */
    'broadcasting' => [
        'enabled' => env('TEMP_EMAIL_BROADCASTING', false),
        'driver' => env('BROADCAST_DRIVER', 'pusher'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Mail Receiving Method
    |--------------------------------------------------------------------------
    |
    | How emails should be received: 'imap', 'webhook', or 'both'
    |
    */
    'receive_method' => env('TEMP_EMAIL_RECEIVE_METHOD', 'webhook'),

    /*
    |--------------------------------------------------------------------------
    | IMAP Settings (if using IMAP method)
    |--------------------------------------------------------------------------
    */
    'imap' => [
        'host' => env('IMAP_HOST', 'imap.gmail.com'),
        'port' => env('IMAP_PORT', 993),
        'encryption' => env('IMAP_ENCRYPTION', 'ssl'),
        'username' => env('IMAP_USERNAME'),
        'password' => env('IMAP_PASSWORD'),
        'check_interval' => env('IMAP_CHECK_INTERVAL', 60), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Settings (if using webhook method)
    |--------------------------------------------------------------------------
    */
    'webhook' => [
        'enabled' => env('TEMP_EMAIL_WEBHOOK_ENABLED', true),
        'secret' => env('TEMP_EMAIL_WEBHOOK_SECRET'),
        'allowed_ips' => explode(',', env('TEMP_EMAIL_WEBHOOK_ALLOWED_IPS', '')),
    ],

];
