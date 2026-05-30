<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Licensing\Server;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Kurt\Modules\Licensing\Server\Models\Product;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /** @var class-string<Product> */
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'name' => ['en' => Str::title($name)],
            'description' => ['en' => $this->faker->sentence()],
            'composer_packages' => ['vendor/'.Str::slug($name)],
            'default_policy' => ['type' => 'perpetual', 'max_activations' => 1],
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
