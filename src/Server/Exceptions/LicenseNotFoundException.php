<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Server\Exceptions;

final class LicenseNotFoundException extends LicensingException
{
    public static function make(): self
    {
        return new self('No license matches the supplied key.');
    }
}
