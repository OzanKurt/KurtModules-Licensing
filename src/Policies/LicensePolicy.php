<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Kurt\Modules\Licensing\Policies\Concerns\AuthorizesLicensingAdmin;
use Kurt\Modules\Licensing\Server\Models\License;

/**
 * Authorizes the admin license endpoints. Every ability delegates to the shared
 * `licensing:manage` gate, so access is deny-by-default until the host wires it.
 */
final class LicensePolicy
{
    use AuthorizesLicensingAdmin;

    public function viewAny(Authenticatable $user): bool
    {
        return $this->manages($user);
    }

    public function view(Authenticatable $user, License $license): bool
    {
        return $this->manages($user);
    }

    public function create(Authenticatable $user): bool
    {
        return $this->manages($user);
    }

    public function update(Authenticatable $user, License $license): bool
    {
        return $this->manages($user);
    }

    public function revoke(Authenticatable $user, License $license): bool
    {
        return $this->manages($user);
    }
}
