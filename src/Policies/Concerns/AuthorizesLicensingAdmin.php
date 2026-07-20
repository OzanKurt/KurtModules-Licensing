<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Policies\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;

/**
 * Shared authorization signal for the licensing admin policies.
 *
 * Management access is gated by a single ability the host application defines
 * (default `licensing:manage`). It is deny-by-default: until the host wires the
 * ability, no authenticated user may reach the admin CRUD endpoints, so the API
 * stays safe out of the box.
 *
 *   Gate::define('licensing:manage', fn ($user) => $user->isAdmin());
 */
trait AuthorizesLicensingAdmin
{
    protected function manages(Authenticatable $user): bool
    {
        return Gate::forUser($user)->allows(self::ability());
    }

    protected static function ability(): string
    {
        $ability = config('licensing.http.ability', 'licensing:manage');

        return is_string($ability) ? $ability : 'licensing:manage';
    }
}
