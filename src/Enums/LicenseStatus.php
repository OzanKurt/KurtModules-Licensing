<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Enums;

enum LicenseStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Expired = 'expired';
    case Revoked = 'revoked';

    /**
     * Only an active license may be activated or pass validation. Suspended,
     * expired and revoked all deny — but they are distinct so the client can
     * surface an accurate reason to the end user.
     */
    public function isUsable(): bool
    {
        return $this === self::Active;
    }
}
