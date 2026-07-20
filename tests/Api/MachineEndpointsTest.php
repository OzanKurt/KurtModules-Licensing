<?php

declare(strict_types=1);

use Kurt\Modules\Licensing\Server\Models\License;
use Kurt\Modules\Licensing\Server\Models\Product;
use Kurt\Modules\Licensing\Server\Support\ActivationManager;
use Kurt\Modules\Licensing\Server\Support\KeyHasher;

/*
| The machine-facing endpoints authenticate by the license key in the body (no
| logged-in user) and return the flat SDK shape { valid, reason, claims }.
*/

it('validates a known key over HTTP and returns claims', function () {
    $product = Product::factory()->create();
    License::factory()->for($product)->create(['key_hash' => app(KeyHasher::class)->hash('KEY-1')]);

    $this->postJson('/api/licensing/validate', ['key' => 'KEY-1'])
        ->assertOk()
        ->assertJson(['valid' => true])
        ->assertJsonPath('claims.product', $product->slug);
});

it('reports invalid for an expired subscription honoring not_after semantics', function () {
    License::factory()->expired()->create(['key_hash' => app(KeyHasher::class)->hash('EXP-1')]);

    $this->postJson('/api/licensing/validate', ['key' => 'EXP-1'])
        ->assertOk()
        ->assertJson(['valid' => false, 'reason' => 'expired']);
});

it('reports not_found for an unknown key without leaking internals', function () {
    $this->postJson('/api/licensing/validate', ['key' => 'NOPE-NOPE'])
        ->assertOk()
        ->assertJson(['valid' => false, 'reason' => 'not_found'])
        ->assertJsonMissingPath('claims.key_hash');
});

it('requires a key', function () {
    $this->postJson('/api/licensing/validate', [])->assertStatus(422);
});

it('activates a machine over HTTP', function () {
    License::factory()->seats(1)->create(['key_hash' => app(KeyHasher::class)->hash('ACT-1')]);

    $this->postJson('/api/licensing/activate', ['key' => 'ACT-1', 'fingerprint' => 'machine-1'])
        ->assertOk()
        ->assertJson(['valid' => true]);
});

it('rejects an over-limit activation without bypassing the seat cap', function () {
    $license = License::factory()->seats(1)->create(['key_hash' => app(KeyHasher::class)->hash('ACT-2')]);

    $this->postJson('/api/licensing/activate', ['key' => 'ACT-2', 'fingerprint' => 'machine-1'])->assertOk();

    $this->postJson('/api/licensing/activate', ['key' => 'ACT-2', 'fingerprint' => 'machine-2'])
        ->assertStatus(422)
        ->assertJson(['valid' => false, 'reason' => 'limit_reached']);

    expect($license->fresh()->activeActivationsCount())->toBe(1);
});

it('returns 404 when activating an unknown key', function () {
    $this->postJson('/api/licensing/activate', ['key' => 'GHOST', 'fingerprint' => 'm'])
        ->assertStatus(404);
});

it('deactivates a machine over HTTP and frees the seat', function () {
    $license = License::factory()->seats(1)->create(['key_hash' => app(KeyHasher::class)->hash('DEA-1')]);
    app(ActivationManager::class)->activate($license, 'machine-1');

    $this->postJson('/api/licensing/deactivate', ['key' => 'DEA-1', 'fingerprint' => 'machine-1'])
        ->assertOk()
        ->assertJson(['deactivated' => true]);

    expect($license->fresh()->activeActivationsCount())->toBe(0);
});
