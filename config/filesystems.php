<?php

return [

    'default' => env('FILESYSTEM_DRIVER', 'local'),

    'cloud' => env('FILESYSTEM_CLOUD', 's3'),

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root'   => storage_path('app'),
        ],

        'public' => [
            'driver'     => 'local',
            'root'       => storage_path('app/public'),
            'url'        => env('APP_URL') . '/storage',
            'visibility' => 'public',
        ],

        /*
        |----------------------------------------------------------------------
        | AWS S3 / Cloudflare R2 — recommended for multi-tenant production
        |----------------------------------------------------------------------
        | Set FILESYSTEM_DRIVER=s3 and fill in credentials for true tenant
        | isolation in the cloud. R2 is an S3-compatible drop-in.
        */
        's3' => [
            'driver'   => 's3',
            'key'      => env('AWS_ACCESS_KEY_ID'),
            'secret'   => env('AWS_SECRET_ACCESS_KEY'),
            'region'   => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'bucket'   => env('AWS_BUCKET'),
            'url'      => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),           // Cloudflare R2 endpoint
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    | Run: php artisan storage:link
    | For tenancy: php artisan tenants:link (per tenant storage symlinks)
    */
    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
