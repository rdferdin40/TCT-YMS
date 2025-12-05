<?php
/**
 * TCT-YMS Application Configuration
 *
 * This file contains all application-wide configuration settings.
 * Environment-specific values should be set in .env file.
 */

return [
    // Application Info
    'name' => env('APP_NAME', 'TCT Yard Management System'),
    'version' => '1.0.0',
    'env' => env('APP_ENV', 'production'),
    'debug' => env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => env('APP_TIMEZONE', 'America/Chicago'),

    // Security
    'key' => env('APP_KEY', ''),

    // Session
    'session' => [
        'lifetime' => env('SESSION_LIFETIME', 120),
        'secure' => env('SESSION_SECURE', true),
        'path' => '/',
        'domain' => null,
        'http_only' => true,
        'same_site' => 'lax',
    ],

    // Upload Settings
    'upload' => [
        'max_size' => env('UPLOAD_MAX_SIZE', 10485760), // 10MB
        'allowed_extensions' => explode(',', env('ALLOWED_EXTENSIONS', 'jpg,jpeg,png,gif,pdf,csv,xlsx')),
        'path' => 'uploads',
    ],

    // Logging
    'log' => [
        'level' => env('LOG_LEVEL', 'warning'),
        'file' => env('LOG_FILE', 'storage/logs/app.log'),
    ],

    // Feature Flags
    'features' => [
        'dark_mode' => env('FEATURE_DARK_MODE', true),
        'csv_import' => env('FEATURE_CSV_IMPORT', true),
        'csv_export' => env('FEATURE_CSV_EXPORT', true),
        'photo_upload' => env('FEATURE_PHOTO_UPLOAD', true),
    ],

    // Pagination
    'pagination' => [
        'per_page' => 25,
        'max_per_page' => 100,
    ],

    // Date/Time Formats
    'formats' => [
        'date' => 'Y-m-d',
        'time' => 'H:i:s',
        'datetime' => 'Y-m-d H:i:s',
        'display_date' => 'M d, Y',
        'display_time' => 'g:i A',
        'display_datetime' => 'M d, Y g:i A',
    ],
];
