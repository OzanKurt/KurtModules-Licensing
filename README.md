# Licensing

Self-hosted software licensing for Laravel. Issue and validate license keys, sign
offline license files, enforce per-machine seat limits, and gate private Composer
downloads — everything you need to sell a premium Laravel/Filament package, running
on your own infrastructure.

Part of the **KurtModules** family. Headless by design, with an optional Filament
admin (Filament 3, 4 & 5) and a **Core-free client SDK** you embed in the package you
sell.

## Features

- **License keys** — human-friendly, crypto-random keys (`ABCD-EF23-GH45-JK67`).
  Only a keyed HMAC of the key is stored, never the plaintext.
- **Three policies** — `perpetual`, `subscription` (time-limited), and
  `updates_window` (runs forever, but only downloads releases published before a
  cutoff — the "license expires, software keeps working" model).
- **Seat activations** — per-machine fingerprints with a configurable limit.
  Re-activating the same machine is idempotent; deactivation frees the seat.
- **Online + offline** — a JSON API for live validation, **and** Ed25519-signed
  license files that verify entirely offline with only a public key.
- **Composer gating** — an HTTP-Basic bridge (email + key) that authorizes Composer
  downloads against the license, front your private Satis or use the bundled
  `auth_request` endpoint.
- **Audit trail** — every issue / activate / validate / composer decision is logged.
- **Client SDK** — `Kurt\Modules\Licensing\Client\*` depends only on `illuminate/*`
  (no Core), so you can embed it in the package you sell. Includes an offline grace
  window so a network blip never bricks a paying customer.

## Requirements

- PHP 8.4+ (with `ext-sodium`, bundled in modern PHP)
- Laravel 12
- [`ozankurt/laravel-modules-core`](https://github.com/OzanKurt/KurtModules-Core) ^2.0

## Installation

```bash
composer require ozankurt/laravel-modules-licensing
```

Core is not on Packagist yet, so add it as a VCS repository in your app's
`composer.json`:

```json
"repositories": [
    { "type": "vcs", "url": "https://github.com/OzanKurt/KurtModules-Core" }
]
```

Publish config + migrations and migrate:

```bash
php artisan vendor:publish --tag=modules-licensing-config
php artisan vendor:publish --tag=modules-licensing-migrations
php artisan migrate
```

Generate a signing keypair and add it to `.env` (keep the signing key on the server only):

```bash
php artisan licensing:keygen
# LICENSING_SIGNING_KEY=...   (server only — signs license files)
# LICENSING_PUBLIC_KEY=...    (also embed in the package you sell — verifies them)
```

## Server usage

### Define a product

```php
use Kurt\Modules\Licensing\Server\Models\Product;

$product = Product::create([
    'slug' => 'acme-pro',
    'name' => ['en' => 'Acme Pro'],
    'composer_packages' => ['acme/pro'],            // gated for Composer
    'default_policy' => ['type' => 'subscription', 'max_activations' => 3],
]);
```

### Issue a license

```php
use Kurt\Modules\Licensing\Facades\Licensing;

$issued = Licensing::issue($product, [
    'licensee_email' => 'buyer@example.com',
    'expires_at' => now()->addYear(),
]);

$issued->key;      // 'ABCD-EF23-...' — show once, it is not recoverable
$issued->license;  // the persisted License model
```

…or from the CLI:

```bash
php artisan licensing:issue acme-pro buyer@example.com --seats=3 --expires="2027-01-01"
```

### Sign a downloadable license file (offline activation)

```php
$blob = base64_encode(json_encode(Licensing::signFileFor($issued->license)));
// hand `$blob` to the customer as a .lic file
```

Every signed file embeds a `not_after` timestamp (inside the signed claims, so it
cannot be tampered out) set `licensing.offline.reissue_ttl_days` in the future
(default 7 days). The client refuses a file once that moment passes and must
re-download a fresh one. This is the offline **revocation / re-issue cadence**:
because an offline file never phones home, withdrawing entitlement (revoke,
downgrade, non-renewal) simply means not re-signing a new file — the old one
lapses within one TTL window. Point customers at a re-download endpoint (or ship
a new `.lic` on renewal) and pick a TTL that trades revocation latency against
how often clients must re-fetch. Clients whose clock is slightly off are covered
by `licensing.offline.skew_tolerance` (seconds) so a valid file is not rejected
on the boundary.

## REST API

An out-of-the-box JSON REST API, built on the shared **Core API kit** and
**safe-by-default**: nothing is registered until you opt in. Enable it by setting
the mode to `api` (or `ui`):

```dotenv
LICENSING_HTTP_MODE=api
```

Everything is configured under `licensing.http` in `config/licensing.php`:

| Key | Default | Purpose |
| --- | --- | --- |
| `mode` | `headless` | `headless` (nothing registered) \| `api` \| `ui`. |
| `prefix` | `api/licensing` | URL prefix for the route group. |
| `middleware` | `['api']` | Base middleware for every route. |
| `auth_middleware` | `['auth']` | Added to the admin (write) routes. |
| `rate_limit` | `'60,1'` | `maxAttempts,decayMinutes` for the `licensing-api` throttle. |
| `ability` | `licensing:manage` | Gate ability the admin policies check (deny-by-default). |

### Authentication model

The API has two tiers with different authentication:

- **Machine endpoints** (`validate` / `activate` / `deactivate`) authenticate by
  the **license key in the request body** — there is no logged-in user. They are
  the public-ish surface the client SDK talks to, run through the domain services
  (so seat accounting and expiry / `not_after` semantics are preserved), and are
  throttled by the `licensing-api` limiter. They return the flat
  `{ valid, reason, claims }` shape and never expose internal fields. There is no
  way to validate or forge without the real key.
- **Admin endpoints** (licenses, products, activations) additionally require the
  `auth_middleware` **plus a Policy**. The `LicensePolicy` / `ProductPolicy` gate
  on the `licensing:manage` ability, which is **deny-by-default**: define it in
  your app to grant access.

  ```php
  use Illuminate\Support\Facades\Gate;

  Gate::define('licensing:manage', fn ($user) => $user->isAdmin());
  ```

  Guests get `401`; authenticated-but-unauthorized users get `403`.

### Endpoints

Mounted under `prefix` (default `api/licensing`). Machine endpoints:

| Method | Path                | Auth       | Body                           | Response |
| ------ | ------------------- | ---------- | ------------------------------ | -------- |
| POST   | `validate`          | key        | `{ key, fingerprint? }`        | `{ valid, reason, claims }` |
| POST   | `activate`          | key        | `{ key, fingerprint, label? }` | `{ valid, claims }` / 422 / 404 |
| POST   | `deactivate`        | key        | `{ key, fingerprint }`         | `{ deactivated }` |

Admin endpoints (auth + `licensing:manage`), enveloped as `{ data, meta }`:

| Method | Path                                 | Purpose |
| ------ | ------------------------------------ | ------- |
| GET    | `licenses`                           | List (sort/filter/paginate). |
| POST   | `licenses`                           | Issue a license; returns the plaintext `key` once. |
| GET    | `licenses/{license}`                 | Show. |
| PATCH  | `licenses/{license}`                 | Update status / policy / seats. |
| POST   | `licenses/{license}/revoke`          | Revoke (`{ reason? }`). |
| GET    | `licenses/{license}/activations`     | List a license's seat activations. |
| GET    | `products`                           | List. |
| POST   | `products`                           | Create. |
| GET    | `products/{product}`                 | Show. |
| PATCH  | `products/{product}`                 | Update. |
| DELETE | `products/{product}`                 | Delete. |

Index endpoints accept `?sort=`, `?filter[field]=`, `?per_page=` / `?page=` per
the Core API kit conventions.

### Composer authorize endpoint

Separate from the REST API kit and **enabled by default** (it is `auth_request`
infrastructure, not a user-facing API), mounted under `licensing.routes.prefix`:

| Method | Path                                    | Body                    | Response |
| ------ | --------------------------------------- | ----------------------- | -------- |
| GET    | `/licensing/composer/authorize/{pkg}`   | HTTP Basic (email\:key) | 204 / 401 / 403 |

## Client SDK (embed in the package you sell)

```php
use Kurt\Modules\Licensing\Client\LicenseManager;

// auto-resolved from config('licensing.client.*'): server_url + key
$state = app(LicenseManager::class)->check();

$state->ok();        // true when valid OR within the offline grace window
$state->isGrace();   // true when the server was unreachable but a recent
                     // success is cached and still inside grace_days
$state->status;      // 'valid' | 'grace' | 'invalid'
$state->claims;      // product, policy, seats, expiry, licensee, ...
```

Offline verification of a signed file — no server, only the public key:

```php
use Kurt\Modules\Licensing\Client\OfflineVerifier;

$result = (new OfflineVerifier($publicKey))->verifyBlob($blob);
$result->valid;   // format tag, signature, expiry, and not_after checked locally
```

The machine fingerprint is stable and opaque (no raw host data leaves the machine):

```php
use Kurt\Modules\Licensing\Client\Fingerprint;

Fingerprint::generate($installSalt);
```

## Composer download gating

Apply the `licensing.composer` middleware to your private repository routes, or point
an nginx `auth_request` at the bundled endpoint:

```nginx
location ~ ^/dist/(?<pkg>.+)\.zip$ {
    auth_request /auth;
}
location = /auth {
    internal;
    proxy_pass https://licenses.example.com/licensing/composer/authorize/$pkg;
    proxy_pass_request_body off;
    proxy_set_header Authorization $http_authorization;
}
```

Composer sends the buyer's email as the username and the license key as the password;
the bridge authorizes against the license's product, status, and (for
`updates_window`) the release date.

## Filament admin (optional)

If you run a Filament panel, register the version-dispatching plugin — the same
call works on Filament 3, 4, and 5:

```php
use Kurt\Modules\Licensing\Filament\LicensingPlugin;

$panel->plugin(LicensingPlugin::make());
```

It adds a **Products** resource (full CRUD) and a **Licenses** resource (browse,
edit status/policy/seats, and a one-click **revoke** action). Licenses are minted
through the issuer/API, so the Licenses resource has no "create" form by design.

## Configuration

Key `.env` values:

| Variable                     | Purpose                                            |
| ---------------------------- | -------------------------------------------------- |
| `LICENSING_SIGNING_KEY`      | Ed25519 secret — signs license files (server only) |
| `LICENSING_PUBLIC_KEY`       | Ed25519 public — verifies files (server + clients) |
| `LICENSING_KEY_HASH_SECRET`  | HMAC secret for key hashing (defaults to app key)  |
| `LICENSING_SERVER_URL`       | Client: base URL of the licensing API              |
| `LICENSING_KEY`              | Client: this install's license key                 |
| `LICENSING_HTTP_MODE`        | REST API mode: `headless` (default) / `api` / `ui` |

See `config/licensing.php` for the full reference.

## Testing

```bash
composer test    # Pest
composer stan    # PHPStan level 8
composer lint    # Pint (dry-run)
```

## License

MIT © Ozan Kurt
