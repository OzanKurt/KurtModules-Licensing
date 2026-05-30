<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Licensing\Server;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Licensing\Enums\LicenseEventType;
use Kurt\Modules\Licensing\Server\Models\License;
use Kurt\Modules\Licensing\Server\Models\LicenseEvent;

/**
 * @extends Factory<LicenseEvent>
 */
class LicenseEventFactory extends Factory
{
    /** @var class-string<LicenseEvent> */
    protected $model = LicenseEvent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'license_id' => License::factory(),
            'action' => LicenseEventType::Issued,
            'context' => [],
            'occurred_at' => now(),
        ];
    }
}
