<?php

declare(strict_types=1);

use Illuminate\Http\Client\Factory;
use Kurt\Modules\Licensing\Client\HttpLicenseTransport;

it('parses a successful validate response', function () {
    $http = new Factory;
    $http->fake(['*' => Factory::response(['valid' => true, 'claims' => ['seats' => 3]], 200)]);

    $response = (new HttpLicenseTransport($http, 'https://licenses.test/api/licensing'))
        ->validate('ABCD-1234', 'fp');

    expect($response->valid)->toBeTrue();
    expect($response->claims['seats'])->toBe(3);
});

it('parses the deactivate boolean', function () {
    $http = new Factory;
    $http->fake(['*' => Factory::response(['deactivated' => true], 200)]);

    expect((new HttpLicenseTransport($http, 'https://licenses.test/api'))->deactivate('K', 'fp'))
        ->toBeTrue();
});

it('treats a non-JSON error body as an invalid response', function () {
    $http = new Factory;
    $http->fake(['*' => Factory::response('oops', 500)]);

    expect((new HttpLicenseTransport($http, 'https://licenses.test/api'))->validate('K')->valid)
        ->toBeFalse();
});
