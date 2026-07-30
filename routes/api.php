<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Kurt\Modules\Licensing\Http\Controllers\Api\Admin\LicenseController;
use Kurt\Modules\Licensing\Http\Controllers\Api\Admin\ProductController;
use Kurt\Modules\Licensing\Http\Controllers\Api\LicenseApiController;

/*
|--------------------------------------------------------------------------
| Licensing REST API
|--------------------------------------------------------------------------
|
| This file is required inside the outer group built by
| PackageServiceProvider::registerModuleApi() — the prefix (`api/licensing`),
| base middleware, `licensing-api` throttle and `licensing.api.` name prefix are
| already applied. Here we only distinguish read vs write.
|
| Read group (below): the machine-facing endpoints. They authenticate by the
| license key in the request body and go through the domain services, so seat
| accounting and offline/expiry semantics are preserved. No user auth.
|
*/
Route::post('validate', [LicenseApiController::class, 'validateLicense'])->name('validate');
Route::post('activate', [LicenseApiController::class, 'activate'])->name('activate');
Route::post('deactivate', [LicenseApiController::class, 'deactivate'])->name('deactivate');

/*
| Write / admin group: requires the module auth middleware on top of the base
| stack, and every action enforces a Policy via $this->authorize().
*/
Route::middleware(config('licensing.http.auth_middleware', ['auth']))->group(function (): void {
    Route::get('licenses', [LicenseController::class, 'index'])->name('licenses.index');
    Route::post('licenses', [LicenseController::class, 'store'])->name('licenses.store');
    Route::get('licenses/{license}', [LicenseController::class, 'show'])->name('licenses.show');
    Route::patch('licenses/{license}', [LicenseController::class, 'update'])->name('licenses.update');
    Route::post('licenses/{license}/revoke', [LicenseController::class, 'revoke'])->name('licenses.revoke');
    Route::get('licenses/{license}/activations', [LicenseController::class, 'activations'])->name('licenses.activations');

    Route::get('products', [ProductController::class, 'index'])->name('products.index');
    Route::post('products', [ProductController::class, 'store'])->name('products.store');
    Route::get('products/{product}', [ProductController::class, 'show'])->name('products.show');
    Route::patch('products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
});
