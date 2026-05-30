<?php

declare(strict_types=1);

use Kurt\Modules\Licensing\Client\OfflineVerifier;
use Kurt\Modules\Licensing\Crypto\Ed25519;
use Kurt\Modules\Licensing\Server\Support\LicenseFileSigner;

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
