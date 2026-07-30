<?php

declare(strict_types=1);

use Kurt\Modules\Licensing\Client\OfflineVerifier;
use Kurt\Modules\Licensing\Crypto\ClaimsCodec;
use Kurt\Modules\Licensing\Crypto\Ed25519;
use Kurt\Modules\Licensing\Server\Support\LicenseFileSigner;

/**
 * Assemble a signed file with an arbitrary claim set, bypassing the signer's
 * mandatory not_after stamping so tests can exercise past / missing windows.
 *
 * @param  array{secret: string, public: string}  $pair
 * @param  array<string, mixed>  $claims
 * @return array{format: string, public_key: string, claims: array<string, mixed>, signature: string}
 */
function signedFile(array $pair, array $claims): array
{
    return [
        'format' => LicenseFileSigner::FORMAT,
        'public_key' => $pair['public'],
        'claims' => $claims,
        'signature' => Ed25519::sign(ClaimsCodec::encode($claims), $pair['secret']),
    ];
}

it('verifies a server-signed license file fully offline', function () {
    $pair = Ed25519::generateKeyPair();
    $blob = (new LicenseFileSigner($pair['secret'], $pair['public']))
        ->toBlob(['key_prefix' => 'ABCD', 'product' => 'acme/premium', 'seats' => 3]);

    $result = (new OfflineVerifier($pair['public']))->verifyBlob($blob);

    expect($result->valid)->toBeTrue();
    expect($result->claims['seats'])->toBe(3);
});

it('rejects a file signed by a different key', function () {
    $pair = Ed25519::generateKeyPair();
    $other = Ed25519::generateKeyPair();
    $blob = (new LicenseFileSigner($pair['secret'], $pair['public']))->toBlob(['seats' => 1]);

    expect((new OfflineVerifier($other['public']))->verifyBlob($blob)->reason)->toBe('bad_signature');
});

it('rejects a signed file whose expiry has passed', function () {
    $pair = Ed25519::generateKeyPair();
    $blob = (new LicenseFileSigner($pair['secret'], $pair['public']))
        ->toBlob(['expires_at' => '2000-01-01T00:00:00+00:00']);

    expect((new OfflineVerifier($pair['public']))->verifyBlob($blob)->reason)->toBe('expired');
});

it('rejects malformed input', function () {
    expect((new OfflineVerifier('anything'))->verifyBlob('not-base64-$$$')->reason)->toBe('malformed');
});

it('accepts a file within its not_after re-issue window', function () {
    $pair = Ed25519::generateKeyPair();
    // The signer stamps a future not_after (7-day TTL) automatically.
    $blob = (new LicenseFileSigner($pair['secret'], $pair['public'], 7))
        ->toBlob(['product' => 'acme/premium']);

    expect((new OfflineVerifier($pair['public']))->verifyBlob($blob)->valid)->toBeTrue();
});

it('rejects a file past its not_after re-issue window', function () {
    $pair = Ed25519::generateKeyPair();
    $file = signedFile($pair, [
        'product' => 'acme/premium',
        'not_after' => '2000-01-01T00:00:00+00:00',
    ]);

    expect((new OfflineVerifier($pair['public']))->verify($file)->reason)->toBe('stale');
});

it('rejects a file that carries no not_after at all', function () {
    $pair = Ed25519::generateKeyPair();
    $file = signedFile($pair, ['product' => 'acme/premium']);

    expect((new OfflineVerifier($pair['public']))->verify($file)->reason)->toBe('stale');
});

it('rejects a file whose format tag is unknown', function () {
    $pair = Ed25519::generateKeyPair();
    $file = signedFile($pair, ['product' => 'acme/premium', 'not_after' => '2999-01-01T00:00:00+00:00']);
    $file['format'] = 'licensing-file/999';

    expect((new OfflineVerifier($pair['public']))->verify($file)->reason)->toBe('unsupported_format');
});

it('rejects a file with a missing format tag', function () {
    $pair = Ed25519::generateKeyPair();
    $file = signedFile($pair, ['product' => 'acme/premium', 'not_after' => '2999-01-01T00:00:00+00:00']);
    unset($file['format']);

    expect((new OfflineVerifier($pair['public']))->verify($file)->reason)->toBe('unsupported_format');
});

it('tolerates a slightly fast clock via the skew allowance', function () {
    $pair = Ed25519::generateKeyPair();
    // not_after 30s in the past: a 60s skew tolerance accepts it, zero rejects.
    $file = signedFile($pair, [
        'product' => 'acme/premium',
        'not_after' => date('c', time() - 30),
    ]);

    expect((new OfflineVerifier($pair['public'], 60))->verify($file)->valid)->toBeTrue();
    expect((new OfflineVerifier($pair['public'], 0))->verify($file)->reason)->toBe('stale');
});
