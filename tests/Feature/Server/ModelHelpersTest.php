<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Kurt\Modules\Licensing\Server\Models\License;

it('treats perpetual licenses as never expiring and always downloadable', function () {
    $license = License::factory()->create();

    expect($license->hasExpired())->toBeFalse();
    expect($license->isUsable())->toBeTrue();
    expect($license->allowsDownloadOf(Carbon::now()->addYears(10)))->toBeTrue();
});

it('treats an expired subscription as unusable and not downloadable', function () {
    $license = License::factory()->expired()->create();

    expect($license->hasExpired())->toBeTrue();
    expect($license->isUsable())->toBeFalse();
    expect($license->allowsDownloadOf(Carbon::now()))->toBeFalse();
});

it('keeps an updates-window license runnable but gates new releases', function () {
    $cutoff = Carbon::now();
    $license = License::factory()->updatesWindow($cutoff)->create();

    expect($license->isUsable())->toBeTrue();
    expect($license->allowsDownloadOf($cutoff->copy()->subDay()))->toBeTrue();
    expect($license->allowsDownloadOf($cutoff->copy()->addDay()))->toBeFalse();
});

it('reports seat availability against active activations', function () {
    $license = License::factory()->seats(2)->create();

    expect($license->hasAvailableSeat())->toBeTrue();

    $license->activations()->create([
        'fingerprint_hash' => hash('sha256', 'a'),
        'activated_at' => now(),
    ]);
    $license->activations()->create([
        'fingerprint_hash' => hash('sha256', 'b'),
        'activated_at' => now(),
    ]);

    expect($license->fresh()->hasAvailableSeat())->toBeFalse();
});
