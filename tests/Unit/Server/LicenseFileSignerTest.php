<?php

declare(strict_types=1);

use Kurt\Modules\Licensing\Crypto\ClaimsCodec;
use Kurt\Modules\Licensing\Crypto\Ed25519;
use Kurt\Modules\Licensing\Server\Exceptions\LicensingException;
use Kurt\Modules\Licensing\Server\Support\LicenseFileSigner;

function freshSigner(): LicenseFileSigner
{
    $pair = Ed25519::generateKeyPair();

    return new LicenseFileSigner($pair['secret'], $pair['public']);
}

it('signs a license file that the embedded public key verifies', function () {
    $signer = freshSigner();
    $claims = ['key_prefix' => 'ABCD', 'product' => 'acme/premium', 'seats' => 3];

    $file = $signer->sign($claims);

    expect($file['format'])->toBe(LicenseFileSigner::FORMAT);
    expect(Ed25519::verify(
        ClaimsCodec::encode($file['claims']),
        $file['signature'],
        $file['public_key'],
    ))->toBeTrue();
});

it('produces a signature that breaks when claims are tampered', function () {
    $signer = freshSigner();
    $file = $signer->sign(['seats' => 1]);

    $tampered = $file['claims'];
    $tampered['seats'] = 999;

    expect(Ed25519::verify(
        ClaimsCodec::encode($tampered),
        $file['signature'],
        $file['public_key'],
    ))->toBeFalse();
});

it('throws when no signing key is configured', function () {
    (new LicenseFileSigner('', ''))->sign(['x' => 1]);
})->throws(LicensingException::class);

it('round-trips through a base64 blob', function () {
    $blob = freshSigner()->toBlob(['seats' => 2]);

    /** @var array{claims: array{seats: int}} $decoded */
    $decoded = json_decode((string) base64_decode($blob, true), true);

    expect($decoded['claims']['seats'])->toBe(2);
});
