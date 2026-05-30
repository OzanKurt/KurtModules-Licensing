<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Server\Exceptions;

final class LicenseNotUsableException extends LicensingException
{
    public static function withReason(string $reason): self
    {
        return new self("License is not usable: {$reason}.");
    }
}
