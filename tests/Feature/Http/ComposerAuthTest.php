<?php

declare(strict_types=1);

use Kurt\Modules\Licensing\Server\Models\License;
use Kurt\Modules\Licensing\Server\Models\Product;
use Kurt\Modules\Licensing\Server\Support\KeyHasher;

/**
 * @return array<string, string>
 */
function basicAuth(string $user, string $pass): array
{
    return ['PHP_AUTH_USER' => $user, 'PHP_AUTH_PW' => $pass];
}

it('authorizes (204) when the license covers the requested package', function () {
    $product = Product::factory()->create(['composer_packages' => ['acme/premium']]);
    License::factory()->for($product)->create([
        'key_hash' => app(KeyHasher::class)->hash('CMP-1'),
        'licensee_email' => 'buyer@example.com',
    ]);

    $this->withServerVariables(basicAuth('buyer@example.com', 'CMP-1'))
        ->getJson('/licensing/composer/authorize/acme/premium')
        ->assertNoContent();
});

it('forbids (403) a package the license does not cover', function () {
    $product = Product::factory()->create(['composer_packages' => ['acme/premium']]);
    License::factory()->for($product)->create(['key_hash' => app(KeyHasher::class)->hash('CMP-2')]);

    $this->withServerVariables(basicAuth('buyer@example.com', 'CMP-2'))
        ->getJson('/licensing/composer/authorize/acme/other')
        ->assertStatus(403);
});

it('challenges with 401 when no license key is supplied', function () {
    $this->getJson('/licensing/composer/authorize/acme/premium')
        ->assertStatus(401)
        ->assertHeader('WWW-Authenticate');
});
