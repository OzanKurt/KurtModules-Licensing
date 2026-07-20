<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Kurt\Modules\Licensing\Policies\Concerns\AuthorizesLicensingAdmin;
use Kurt\Modules\Licensing\Server\Models\Product;

/**
 * Authorizes the admin product endpoints. Every ability delegates to the shared
 * `licensing:manage` gate, so access is deny-by-default until the host wires it.
 */
final class ProductPolicy
{
    use AuthorizesLicensingAdmin;

    public function viewAny(Authenticatable $user): bool
    {
        return $this->manages($user);
    }

    public function view(Authenticatable $user, Product $product): bool
    {
        return $this->manages($user);
    }

    public function create(Authenticatable $user): bool
    {
        return $this->manages($user);
    }

    public function update(Authenticatable $user, Product $product): bool
    {
        return $this->manages($user);
    }

    public function delete(Authenticatable $user, Product $product): bool
    {
        return $this->manages($user);
    }
}
