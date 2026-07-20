<?php

declare(strict_types=1);

/*
| This file runs under the default TestCase, which leaves the module headless
| (`licensing.http.mode` unset -> headless). The REST API kit must register
| nothing in that mode, so every api/licensing route 404s.
*/

it('registers no REST API routes while headless (the safe default)', function () {
    expect(config('licensing.http.mode'))->toBe('headless');

    $this->postJson('/api/licensing/validate', ['key' => 'X'])->assertNotFound();
    $this->getJson('/api/licensing/licenses')->assertNotFound();
    $this->getJson('/api/licensing/products')->assertNotFound();
});

it('still serves the composer authorize endpoint while headless', function () {
    // Composer gating is separate infrastructure and stays enabled by default.
    $this->getJson('/licensing/composer/authorize/acme/premium')
        ->assertStatus(401)
        ->assertHeader('WWW-Authenticate');
});
