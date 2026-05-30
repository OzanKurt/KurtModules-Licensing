<?php

declare(strict_types=1);

use Kurt\Modules\Licensing\Enums\PolicyType;

it('exposes the three policy types', function () {
    expect(PolicyType::Perpetual->value)->toBe('perpetual');
    expect(PolicyType::Subscription->value)->toBe('subscription');
    expect(PolicyType::UpdatesWindow->value)->toBe('updates_window');
});
