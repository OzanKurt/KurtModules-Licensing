<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Gate;
use Kurt\Modules\Licensing\Server\Models\Product;

function productAdmin(bool $manages = true): GenericUser
{
    return new GenericUser(['id' => $manages ? 1 : 2, 'manages' => $manages]);
}

beforeEach(function () {
    Gate::define('licensing:manage', fn (GenericUser $user): bool => (bool) $user->manages);
});

it('rejects guests with 401', function () {
    $this->getJson('/api/licensing/products')->assertStatus(401);
});

it('rejects an unauthorized user with 403', function () {
    $this->actingAs(productAdmin(manages: false))
        ->getJson('/api/licensing/products')
        ->assertStatus(403);
});

it('lists products for an authorized admin', function () {
    Product::factory()->count(2)->create();

    $this->actingAs(productAdmin())
        ->getJson('/api/licensing/products')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.pagination.total', 2);
});

it('creates a product', function () {
    $this->actingAs(productAdmin())
        ->postJson('/api/licensing/products', [
            'slug' => 'acme-pro',
            'name' => 'Acme Pro',
            'composer_packages' => ['acme/premium'],
            'is_active' => true,
        ])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'acme-pro')
        ->assertJsonPath('data.name', 'Acme Pro');

    $this->assertDatabaseHas('licensing_products', ['slug' => 'acme-pro']);
});

it('rejects a duplicate slug', function () {
    Product::factory()->create(['slug' => 'dupe']);

    $this->actingAs(productAdmin())
        ->postJson('/api/licensing/products', ['slug' => 'dupe', 'name' => 'Dupe'])
        ->assertStatus(422);
});

it('shows a product', function () {
    $product = Product::factory()->create();

    $this->actingAs(productAdmin())
        ->getJson("/api/licensing/products/{$product->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $product->id);
});

it('updates a product', function () {
    $product = Product::factory()->create(['is_active' => true]);

    $this->actingAs(productAdmin())
        ->patchJson("/api/licensing/products/{$product->id}", ['is_active' => false])
        ->assertOk()
        ->assertJsonPath('data.is_active', false);
});

it('deletes a product', function () {
    $product = Product::factory()->create();

    $this->actingAs(productAdmin())
        ->deleteJson("/api/licensing/products/{$product->id}")
        ->assertNoContent();

    $this->assertSoftDeleted('licensing_products', ['id' => $product->id]);
});
