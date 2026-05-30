<?php

declare(strict_types=1);

use Kurt\Modules\Licensing\Crypto\Ed25519;

it('round-trips sign and verify', function () {
    $pair = Ed25519::generateKeyPair();
    $message = 'license-file-payload';

    $signature = Ed25519::sign($message, $pair['secret']);

    expect(Ed25519::verify($message, $signature, $pair['public']))->toBeTrue();
});

it('rejects a tampered message', function () {
    $pair = Ed25519::generateKeyPair();
    $signature = Ed25519::sign('original', $pair['secret']);

    expect(Ed25519::verify('tampered', $signature, $pair['public']))->toBeFalse();
});

it('rejects a signature from a different key', function () {
    $a = Ed25519::generateKeyPair();
    $b = Ed25519::generateKeyPair();
    $signature = Ed25519::sign('msg', $a['secret']);

    expect(Ed25519::verify('msg', $signature, $b['public']))->toBeFalse();
});

it('returns false for garbage signature input rather than throwing', function () {
    $pair = Ed25519::generateKeyPair();

    expect(Ed25519::verify('msg', 'not-base64-$$$', $pair['public']))->toBeFalse();
});

it('throws on an invalid secret key when signing', function () {
    expect(fn () => Ed25519::sign('msg', 'not-base64-$$$'))
        ->toThrow(InvalidArgumentException::class);
});
