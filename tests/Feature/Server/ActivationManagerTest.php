<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Kurt\Modules\Licensing\Server\Exceptions\ActivationLimitReachedException;
use Kurt\Modules\Licensing\Server\Exceptions\LicenseNotUsableException;
use Kurt\Modules\Licensing\Server\Models\License;
use Kurt\Modules\Licensing\Server\Support\ActivationManager;
use Kurt\Modules\Licensing\Tests\Fixtures\LockCapturingGrammar;

/**
 * Run $work with a grammar that renders `lockForUpdate()` as a visible SQL
 * comment (SQLite drops real locks), returning every statement emitted.
 *
 * @param  Closure():void  $work
 * @return array<int, string>
 */
function captureActivationSql(Closure $work): array
{
    $connection = DB::connection();
    $connection->setQueryGrammar(new LockCapturingGrammar($connection));

    $statements = [];
    DB::listen(function ($query) use (&$statements): void {
        $statements[] = $query->sql;
    });

    $work();

    return array_values(array_filter($statements, fn (string $sql) => str_contains($sql, 'for update')));
}

it('activates a new machine and consumes one seat', function () {
    $license = License::factory()->seats(2)->create();

    $activation = app(ActivationManager::class)->activate($license, 'machine-1');

    expect($activation->isActive())->toBeTrue();
    expect($license->fresh()->activeActivationsCount())->toBe(1);
});

it('is idempotent for a repeated fingerprint and does not consume a second seat', function () {
    $license = License::factory()->seats(1)->create();
    $manager = app(ActivationManager::class);

    $manager->activate($license, 'same-machine');
    $manager->activate($license, 'same-machine');

    expect($license->fresh()->activeActivationsCount())->toBe(1);
});

it('throws once seats are exhausted', function () {
    $license = License::factory()->seats(1)->create();
    $manager = app(ActivationManager::class);

    $manager->activate($license, 'machine-1');
    $manager->activate($license, 'machine-2');
})->throws(ActivationLimitReachedException::class);

it('frees a seat on deactivation so a new machine can activate', function () {
    $license = License::factory()->seats(1)->create();
    $manager = app(ActivationManager::class);

    $manager->activate($license, 'machine-1');
    expect($manager->deactivate($license, 'machine-1'))->toBeTrue();
    expect($license->fresh()->activeActivationsCount())->toBe(0);

    $manager->activate($license, 'machine-2');
    expect($license->fresh()->activeActivationsCount())->toBe(1);
});

it('refuses to activate a revoked license', function () {
    $license = License::factory()->revoked()->create();

    app(ActivationManager::class)->activate($license, 'machine-1');
})->throws(LicenseNotUsableException::class);

it('never exceeds the seat cap under repeated distinct-fingerprint activations', function () {
    // Regression for the seat-limit TOCTOU race: looping distinct fingerprints
    // against a 1-seat license (a sqlite-friendly proxy for concurrency) must
    // never let active activations climb past max_activations.
    $license = License::factory()->seats(1)->create();
    $manager = app(ActivationManager::class);

    $granted = 0;

    foreach (range(1, 20) as $i) {
        try {
            $manager->activate($license, "machine-{$i}");
            $granted++;
        } catch (ActivationLimitReachedException) {
            // Expected once the single seat is taken.
        }

        expect($license->fresh()->activeActivationsCount())
            ->toBeLessThanOrEqual($license->max_activations);
    }

    expect($granted)->toBe(1);
    expect($license->fresh()->activeActivationsCount())->toBe(1);
});

it('locks the license row FOR UPDATE while activating a seat', function () {
    // The seat-race fix hinges on lockForUpdate() serializing activations.
    // SQLite cannot enforce a real lock, so instead of a (no-op) concurrency
    // loop we assert the clause is actually emitted: if someone deletes the
    // lockForUpdate() call, no `for update` statement is produced and this
    // fails, guarding the fix from silent removal.
    $license = License::factory()->seats(2)->create();

    $locked = captureActivationSql(function () use ($license) {
        app(ActivationManager::class)->activate($license, 'machine-1');
    });

    expect($locked)->not->toBeEmpty();
    expect(implode("\n", $locked))->toContain('licenses');
});

it('locks the license row FOR UPDATE while deactivating a seat', function () {
    $license = License::factory()->seats(1)->create();
    $manager = app(ActivationManager::class);
    $manager->activate($license, 'machine-1');

    $locked = captureActivationSql(fn () => $manager->deactivate($license, 'machine-1'));

    expect($locked)->not->toBeEmpty();
    expect(implode("\n", $locked))->toContain('licenses');
});

it('still recognizes a seat stored under the legacy unkeyed fingerprint hash', function () {
    // Rows written before the keyed-hash upgrade carry an unkeyed sha256. The
    // dual-read must match them so an upgrade never orphans existing seats.
    $license = License::factory()->seats(1)->create();
    $manager = app(ActivationManager::class);

    $license->activations()->create([
        'fingerprint_hash' => hash('sha256', 'legacy-machine'),
        'activated_at' => now(),
        'last_seen_at' => now(),
    ]);

    // Re-activating the same fingerprint is idempotent against the legacy row
    // and must not burn the only remaining seat.
    $manager->activate($license, 'legacy-machine');

    expect($license->fresh()->activeActivationsCount())->toBe(1);
});

it('stores new activations under the keyed HMAC rather than a raw sha256', function () {
    $license = License::factory()->seats(1)->create();
    $manager = app(ActivationManager::class);

    $manager->activate($license, 'machine-1');

    $stored = $license->activations()->value('fingerprint_hash');

    expect($stored)->toBe($manager->fingerprintHash('machine-1'));
    expect($stored)->not->toBe(hash('sha256', 'machine-1'));
});
