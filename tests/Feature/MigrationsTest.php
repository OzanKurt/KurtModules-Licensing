<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

it('creates every licensing_* table', function () {
    foreach ([
        'licensing_products',
        'licensing_licenses',
        'licensing_activations',
        'licensing_license_events',
    ] as $table) {
        expect(Schema::hasTable($table))->toBeTrue("missing {$table}");
    }
});
