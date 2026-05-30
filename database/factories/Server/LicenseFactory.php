<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Licensing\Server;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Kurt\Modules\Licensing\Enums\LicenseStatus;
use Kurt\Modules\Licensing\Enums\PolicyType;
use Kurt\Modules\Licensing\Server\Models\License;
use Kurt\Modules\Licensing\Server\Models\Product;

/**
 * @extends Factory<License>
 */
class LicenseFactory extends Factory
{
    /** @var class-string<License> */
    protected $model = License::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'key_hash' => hash('sha256', Str::random(48)),
            'key_prefix' => Str::upper(Str::random(4)),
            'licensee_email' => $this->faker->unique()->safeEmail(),
            'licensee_name' => $this->faker->name(),
            'status' => LicenseStatus::Active,
            'policy_type' => PolicyType::Perpetual,
            'max_activations' => 1,
            'issued_at' => now(),
        ];
    }

    public function subscription(?\DateTimeInterface $expiresAt = null): static
    {
        return $this->state(fn () => [
            'policy_type' => PolicyType::Subscription,
            'expires_at' => $expiresAt ?? now()->addYear(),
        ]);
    }

    public function updatesWindow(?\DateTimeInterface $updatesUntil = null): static
    {
        return $this->state(fn () => [
            'policy_type' => PolicyType::UpdatesWindow,
            'updates_until' => $updatesUntil ?? now()->addYear(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'policy_type' => PolicyType::Subscription,
            'expires_at' => now()->subDay(),
        ]);
    }

    public function revoked(): static
    {
        return $this->state(fn () => [
            'status' => LicenseStatus::Revoked,
            'revoked_at' => now(),
            'revoked_reason' => 'Refund issued',
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => LicenseStatus::Suspended]);
    }

    public function seats(int $count): static
    {
        return $this->state(fn () => ['max_activations' => $count]);
    }
}
