<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Licensing\Server;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Kurt\Modules\Licensing\Server\Models\Activation;
use Kurt\Modules\Licensing\Server\Models\License;

/**
 * @extends Factory<Activation>
 */
class ActivationFactory extends Factory
{
    /** @var class-string<Activation> */
    protected $model = Activation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'license_id' => License::factory(),
            'fingerprint_hash' => hash('sha256', Str::random(32)),
            'label' => $this->faker->word(),
            'ip' => $this->faker->ipv4(),
            'user_agent' => 'licensing-client/1.0',
            'activated_at' => now(),
            'last_seen_at' => now(),
        ];
    }

    public function deactivated(): static
    {
        return $this->state(fn () => ['deactivated_at' => now()]);
    }
}
