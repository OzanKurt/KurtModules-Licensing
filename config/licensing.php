<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Signing keys (Ed25519)
    |--------------------------------------------------------------------------
    |
    | The server signs license files with the private key; clients verify them
    | with the public key. Generate a pair with `php artisan licensing:keygen`.
    | A pure-client install (a premium package embedding the SDK) only needs the
    | public key.
    |
    */
    'signing_key' => env('LICENSING_SIGNING_KEY'),
    'public_key' => env('LICENSING_PUBLIC_KEY'),

    /*
    |--------------------------------------------------------------------------
    | License key format
    |--------------------------------------------------------------------------
    */
    'key' => [
        'groups' => 4,
        'group_size' => 4,
        'alphabet' => 'ABCDEFGHJKMNPQRSTUVWXYZ23456789',
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    */
    'routes' => [
        'api_enabled' => true,
        'prefix' => 'licensing',
        'throttle' => '60,1',
    ],

    /*
    |--------------------------------------------------------------------------
    | Composer download gating
    |--------------------------------------------------------------------------
    |
    | When `repository_enabled` is true, this app serves as a private Composer
    | repository (packages.json + dist zips) gated by license keys, so you don't
    | need a separate Satis. Otherwise the AuthenticatesComposer middleware can
    | front your existing Satis.
    |
    */
    'composer' => [
        'repository_enabled' => false,
        'dist_disk' => env('LICENSING_DIST_DISK', 'local'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Client SDK
    |--------------------------------------------------------------------------
    */
    'client' => [
        'server_url' => env('LICENSING_SERVER_URL'),
        'key' => env('LICENSING_KEY'),
        'heartbeat_days' => 7,
        'grace_days' => 14,
        'cache_store' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Model overrides
    |--------------------------------------------------------------------------
    */
    'models' => [
        'product' => \Kurt\Modules\Licensing\Server\Models\Product::class,
        'license' => \Kurt\Modules\Licensing\Server\Models\License::class,
        'activation' => \Kurt\Modules\Licensing\Server\Models\Activation::class,
        'license_event' => \Kurt\Modules\Licensing\Server\Models\LicenseEvent::class,
    ],
];
