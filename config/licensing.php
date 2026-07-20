<?php

declare(strict_types=1);
use Kurt\Modules\Licensing\Server\Models\Activation;
use Kurt\Modules\Licensing\Server\Models\License;
use Kurt\Modules\Licensing\Server\Models\LicenseEvent;
use Kurt\Modules\Licensing\Server\Models\Product;

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

        // Keyed HMAC secret used to hash license keys for storage/lookup.
        // Falls back to the app key; set a dedicated value to decouple license
        // hashes from app-key rotation.
        'hash_secret' => env('LICENSING_KEY_HASH_SECRET'),
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
    | License keys gate Composer downloads via HTTP Basic auth (email = user,
    | key = password). Apply the `licensing.composer` middleware to your private
    | Satis/repository routes, or point an nginx `auth_request` at the bundled
    | GET {prefix}/composer/authorize/{package} endpoint. `realm` is the Basic
    | realm shown to Composer when a key is required.
    |
    */
    'composer' => [
        'realm' => 'Composer',
    ],

    /*
    |--------------------------------------------------------------------------
    | Offline license files
    |--------------------------------------------------------------------------
    |
    | Signed offline files verify with no server call, so they need their own
    | withdrawal path. Every file the server signs embeds a `not_after`
    | timestamp `reissue_ttl_days` in the future; the client rejects a file once
    | that moment passes and must re-download a freshly signed one. This is the
    | re-issue cadence: a revoked or downgraded license stops working within one
    | TTL window without ever contacting the server. Keep it short enough that
    | revocation takes effect promptly, long enough that clients are not forced
    | to re-download constantly (7 days is a sensible default).
    |
    | `skew_tolerance` is the leeway (in seconds) applied when comparing
    | `expires_at` / `not_after` against the local clock, so a client whose
    | clock is slightly fast does not fail an otherwise-valid file.
    |
    */
    'offline' => [
        'reissue_ttl_days' => 7,
        'skew_tolerance' => 60,
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
        'product' => Product::class,
        'license' => License::class,
        'activation' => Activation::class,
        'license_event' => LicenseEvent::class,
    ],
];
