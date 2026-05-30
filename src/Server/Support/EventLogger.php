<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Server\Support;

use Kurt\Modules\Licensing\Enums\LicenseEventType;
use Kurt\Modules\Licensing\Server\Models\License;
use Kurt\Modules\Licensing\Server\Models\LicenseEvent;

/**
 * Single choke point for writing the license audit trail, so every service
 * records events with a consistent shape and timestamp.
 */
final class EventLogger
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function log(License $license, LicenseEventType $action, array $context = []): LicenseEvent
    {
        return $license->events()->create([
            'action' => $action,
            'context' => $context,
            'occurred_at' => now(),
        ]);
    }
}
