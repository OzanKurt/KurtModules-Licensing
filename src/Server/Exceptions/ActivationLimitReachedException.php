<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Server\Exceptions;

final class ActivationLimitReachedException extends LicensingException
{
    public static function forSeats(int $max): self
    {
        return new self("License activation limit reached ({$max} seat(s) in use).");
    }
}
