<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Enums;

enum LicenseEventType: string
{
    case Issued = 'issued';
    case Activated = 'activated';
    case Deactivated = 'deactivated';
    case Validated = 'validated';
    case Expired = 'expired';
    case Revoked = 'revoked';
    case Renewed = 'renewed';
    case Suspended = 'suspended';
    case ComposerAuthorized = 'composer_authorized';
    case ComposerDenied = 'composer_denied';
    case LimitReached = 'limit_reached';
}
