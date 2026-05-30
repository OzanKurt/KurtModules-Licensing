<?php

declare(strict_types=1);

use Kurt\Modules\Licensing\Enums\LicenseEventType;

it('covers the full lifecycle + composer audit event set', function () {
    expect(LicenseEventType::cases())->toHaveCount(11);
    expect(LicenseEventType::Issued->value)->toBe('issued');
    expect(LicenseEventType::ComposerAuthorized->value)->toBe('composer_authorized');
    expect(LicenseEventType::ComposerDenied->value)->toBe('composer_denied');
    expect(LicenseEventType::LimitReached->value)->toBe('limit_reached');
});
