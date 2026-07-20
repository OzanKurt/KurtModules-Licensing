<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Kurt\Modules\Licensing\Http\Controllers\ComposerAuthorizeController;
use Kurt\Modules\Licensing\Http\Middleware\AuthenticatesComposer;

/*
| Composer download-gating endpoint. Mounted separately from the REST API kit
| (see routes/api.php) because it is `auth_request` infrastructure for private
| Composer repositories, gated by HTTP Basic (email : license key) rather than a
| logged-in user, and enabled by default.
*/
Route::get('composer/authorize/{package}', ComposerAuthorizeController::class)
    ->where('package', '.+')
    ->middleware(AuthenticatesComposer::class)
    ->name('composer.authorize');
