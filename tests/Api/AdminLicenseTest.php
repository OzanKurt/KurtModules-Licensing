<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Gate;
use Kurt\Modules\Licensing\Enums\LicenseStatus;
use Kurt\Modules\Licensing\Server\Models\License;
use Kurt\Modules\Licensing\Server\Models\Product;
use Kurt\Modules\Licensing\Server\Support\ActivationManager;

function licensingAdmin(bool $manages = true): GenericUser
{
    return new GenericUser(['id' => $manages ? 1 : 2, 'manages' => $manages]);
}

beforeEach(function () {
    Gate::define('licensing:manage', fn (GenericUser $user): bool => (bool) $user->manages);
});

it('rejects guests with 401', function () {
    License::factory()->create();

    $this->getJson('/api/licensing/licenses')->assertStatus(401);
});

it('rejects an authenticated but unauthorized user with 403', function () {
    License::factory()->create();

    $this->actingAs(licensingAdmin(manages: false))
        ->getJson('/api/licensing/licenses')
        ->assertStatus(403);
});

it('lists licenses for an authorized admin and never leaks the key hash', function () {
    $product = Product::factory()->create();
    License::factory()->for($product)->count(3)->create();

    $response = $this->actingAs(licensingAdmin())
        ->getJson('/api/licensing/licenses')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('meta.pagination.total', 3);

    expect($response->json('data.0'))->not->toHaveKey('key_hash');
});

it('filters licenses by status', function () {
    License::factory()->create(['status' => LicenseStatus::Active]);
    License::factory()->revoked()->create();

    $this->actingAs(licensingAdmin())
        ->getJson('/api/licensing/licenses?filter[status]=revoked')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'revoked');
});

it('shows a single license', function () {
    $license = License::factory()->create();

    $this->actingAs(licensingAdmin())
        ->getJson("/api/licensing/licenses/{$license->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $license->id)
        ->assertJsonPath('data.key_prefix', $license->key_prefix);
});

it('issues a license through the issuer and returns the plaintext key once', function () {
    $product = Product::factory()->create();

    $response = $this->actingAs(licensingAdmin())
        ->postJson('/api/licensing/licenses', [
            'product_id' => $product->id,
            'licensee_email' => 'buyer@example.com',
            'max_activations' => 3,
        ])
        ->assertCreated()
        ->assertJsonPath('data.license.status', 'active')
        ->assertJsonPath('data.license.max_activations', 3);

    expect($response->json('data.key'))->toBeString()->not->toBeEmpty();

    $this->assertDatabaseHas('licensing_licenses', [
        'licensee_email' => 'buyer@example.com',
        'product_id' => $product->id,
    ]);
});

it('rejects issuance for a non-authorized user', function () {
    $product = Product::factory()->create();

    $this->actingAs(licensingAdmin(manages: false))
        ->postJson('/api/licensing/licenses', [
            'product_id' => $product->id,
            'licensee_email' => 'buyer@example.com',
        ])
        ->assertStatus(403);
});

it('updates a license', function () {
    $license = License::factory()->create(['max_activations' => 1]);

    $this->actingAs(licensingAdmin())
        ->patchJson("/api/licensing/licenses/{$license->id}", ['max_activations' => 5, 'status' => 'suspended'])
        ->assertOk()
        ->assertJsonPath('data.max_activations', 5)
        ->assertJsonPath('data.status', 'suspended');
});

it('revokes a license and records the reason', function () {
    $license = License::factory()->create(['status' => LicenseStatus::Active]);

    $this->actingAs(licensingAdmin())
        ->postJson("/api/licensing/licenses/{$license->id}/revoke", ['reason' => 'Refund issued'])
        ->assertOk()
        ->assertJsonPath('data.status', 'revoked')
        ->assertJsonPath('data.revoked_reason', 'Refund issued');

    $this->assertDatabaseHas('licensing_license_events', [
        'license_id' => $license->id,
        'action' => 'revoked',
    ]);
});

it('lists activations for a license without exposing the fingerprint hash', function () {
    $license = License::factory()->seats(2)->create();
    app(ActivationManager::class)->activate($license, 'machine-1');

    $response = $this->actingAs(licensingAdmin())
        ->getJson("/api/licensing/licenses/{$license->id}/activations")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.active', true);

    expect($response->json('data.0'))->not->toHaveKey('fingerprint_hash');
});
