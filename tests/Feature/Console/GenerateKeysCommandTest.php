<?php

declare(strict_types=1);

it('outputs an env-ready Ed25519 keypair', function () {
    $this->artisan('licensing:keygen')
        ->expectsOutputToContain('LICENSING_PUBLIC_KEY=')
        ->expectsOutputToContain('LICENSING_SIGNING_KEY=')
        ->assertSuccessful();
});
