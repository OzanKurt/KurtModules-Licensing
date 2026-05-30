<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Kurt\Modules\Licensing\Http\Controllers\Api\LicenseApiController;
use Kurt\Modules\Licensing\Http\Controllers\ComposerAuthorizeController;
use Kurt\Modules\Licensing\Http\Middleware\AuthenticatesComposer;

Route::post('validate', [LicenseApiController::class, 'validate'])->name('validate');
Route::post('activate', [LicenseApiController::class, 'activate'])->name('activate');
Route::post('deactivate', [LicenseApiController::class, 'deactivate'])->name('deactivate');

Route::get('composer/authorize/{package}', ComposerAuthorizeController::class)
    ->where('package', '.+')
    ->middleware(AuthenticatesComposer::class)
    ->name('composer.authorize');
