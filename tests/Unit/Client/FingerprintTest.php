<?php

declare(strict_types=1);

use Kurt\Modules\Licensing\Client\Fingerprint;

it('is stable across calls on the same machine', function () {
    expect(Fingerprint::generate())->toBe(Fingerprint::generate());
});

it('changes when the salt changes', function () {
    expect(Fingerprint::generate('install-a'))->not->toBe(Fingerprint::generate('install-b'));
});

it('returns a 64-character sha256 hex digest', function () {
    expect(Fingerprint::generate())->toMatch('/^[a-f0-9]{64}$/');
});
