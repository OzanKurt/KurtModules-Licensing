<?php

declare(strict_types=1);

use Kurt\Modules\Licensing\Server\Models\License;
use Kurt\Modules\Licensing\Server\Models\Product;

it('issues a license from the CLI', function () {
    Product::factory()->create(['slug' => 'acme-premium']);

    $this->artisan('licensing:issue', [
        'product' => 'acme-premium',
        'email' => 'cli@example.com',
        '--seats' => 3,
    ])->assertSuccessful();

    $license = License::query()->where('licensee_email', 'cli@example.com')->firstOrFail();

    expect($license->max_activations)->toBe(3);
});

it('fails for an unknown product slug', function () {
    $this->artisan('licensing:issue', ['product' => 'does-not-exist', 'email' => 'x@y.com'])
        ->assertFailed();
});
