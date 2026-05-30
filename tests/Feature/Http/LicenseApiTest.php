<?php

declare(strict_types=1);

use Kurt\Modules\Licensing\Server\Models\License;
use Kurt\Modules\Licensing\Server\Models\Product;
use Kurt\Modules\Licensing\Server\Support\ActivationManager;
use Kurt\Modules\Licensing\Server\Support\KeyHasher;

it('validates a known key over HTTP and returns claims', function () {
    $product = Product::factory()->create();
    License::factory()->for($product)->create(['key_hash' => app(KeyHasher::class)->hash('KEY-1')]);

    $this->postJson('/licensing/validate', ['key' => 'KEY-1'])
        ->assertOk()
        ->assertJson(['valid' => true])
        ->assertJsonPath('claims.product', $product->slug);
});

it('reports not_found over HTTP for an unknown key', function () {
    $this->postJson('/licensing/validate', ['key' => 'NOPE-NOPE'])
        ->assertOk()
        ->assertJson(['valid' => false, 'reason' => 'not_found']);
});

it('requires a key', function () {
    $this->postJson('/licensing/validate', [])->assertStatus(422);
});

it('activates a machine over HTTP', function () {
    License::factory()->seats(1)->create(['key_hash' => app(KeyHasher::class)->hash('ACT-1')]);

    $this->postJson('/licensing/activate', ['key' => 'ACT-1', 'fingerprint' => 'machine-1'])
        ->assertOk()
        ->assertJson(['valid' => true]);
});

it('returns 422 when the activation limit is reached', function () {
    License::factory()->seats(1)->create(['key_hash' => app(KeyHasher::class)->hash('ACT-2')]);

    $this->postJson('/licensing/activate', ['key' => 'ACT-2', 'fingerprint' => 'machine-1'])->assertOk();

    $this->postJson('/licensing/activate', ['key' => 'ACT-2', 'fingerprint' => 'machine-2'])
        ->assertStatus(422)
        ->assertJson(['valid' => false, 'reason' => 'limit_reached']);
});

it('returns 404 when activating an unknown key', function () {
    $this->postJson('/licensing/activate', ['key' => 'GHOST', 'fingerprint' => 'm'])
        ->assertStatus(404);
});

it('deactivates a machine over HTTP', function () {
    $license = License::factory()->seats(1)->create(['key_hash' => app(KeyHasher::class)->hash('DEA-1')]);
    app(ActivationManager::class)->activate($license, 'machine-1');

    $this->postJson('/licensing/deactivate', ['key' => 'DEA-1', 'fingerprint' => 'machine-1'])
        ->assertOk()
        ->assertJson(['deactivated' => true]);
});
