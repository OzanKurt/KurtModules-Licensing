# Changelog

All notable changes to `ozankurt/laravel-modules-licensing` are documented here.

## v2.0.0

### Breaking changes

- Requires PHP 8.4+ (drops PHP 8.3).
- Requires Laravel 13 (drops Laravel 12).
- Requires `ozankurt/laravel-modules-core` ^2.0.
- Test suite runs on Pest 5 / Testbench 11.

No public API changed: the licensing server, client SDK, HTTP endpoints and
Filament resources are identical to v1.1.0.

## v1.1.0

### Filament admin (v3 · v4 · v5)

- `ProductResource` — full CRUD: name, slug, description, Composer packages,
  default policy, active toggle, licenses count.
- `LicenseResource` — browse/edit licensee, status, policy, seats, expiry; a
  one-click **revoke** row action; filters by status and policy. Create is
  disabled by design — licenses are minted via the issuer/API.
- Version-dispatching `LicensingPlugin::make()` resolves the correct V{n}
  resource set; opt in with `$panel->plugin(LicensingPlugin::make())`.
- Per-Filament-major PHPStan configs and guarded introspection smoke tests; the
  CI matrix analyses each version directory under its installed Filament major.

## v1.0.0

Initial release — self-hosted software licensing for Laravel.

### Server

- `Product`, `License`, `Activation`, `LicenseEvent` models with factories.
- License keys: crypto-random grouped format; only a keyed HMAC is stored.
- Ed25519 signing/verification (`Crypto\Ed25519`) and canonical claim encoding
  (`Crypto\ClaimsCodec`) shared by signer and verifier.
- Support services: `KeyGenerator`, `KeyHasher`, `LicenseFileSigner`,
  `LicenseIssuer`, `ActivationManager`, `LicenseValidator`, `ComposerAuthValidator`,
  `EventLogger`, `LicenseClaims`.
- Three policies: perpetual, subscription, updates-window — enforced for runtime
  usability and Composer download eligibility.
- Idempotent per-fingerprint seat activations with configurable limits.

### HTTP

- JSON API: `POST validate | activate | deactivate` (throttled, configurable prefix).
- `AuthenticatesComposer` middleware (HTTP Basic email\:key) + `licensing.composer`
  alias, and a `GET composer/authorize/{package}` endpoint for nginx `auth_request`.

### Client SDK (Core-free)

- `LicenseManager` — online validation with a cached offline grace window.
- `OfflineVerifier` — verifies Ed25519-signed license files with only a public key.
- `Fingerprint` — stable, salted, opaque machine identifier.
- `HttpLicenseTransport` + `ArrayLicenseCache` / `IlluminateLicenseCache`,
  swappable via the `LicenseTransport` / `LicenseCache` contracts.

### Console

- `licensing:keygen` — generate an Ed25519 keypair.
- `licensing:issue` — issue a license from the CLI.
- `licensing:expire` — flip past-due subscriptions to expired (scheduled daily).

### Tooling

- Pest suite, PHPStan level 8, Laravel Pint.
- CI across PHP 8.4 / Laravel 12 / Filament 3·4·5.
