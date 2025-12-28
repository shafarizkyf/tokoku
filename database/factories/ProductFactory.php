<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => 1,
            'name' => $this->faker->words(3, true),
            'slug' => $this->faker->unique()->slug,
            'description' => $this->faker->sentence,
            'condition' => $this->faker->randomElement(['new', 'used']),
            'review_avg' => $this->faker->randomFloat(2, 0, 5),
            'review_count' => $this->faker->numberBetween(0, 1000),
            'sold_count' => $this->faker->numberBetween(0, 10000),
            'source' => $this->faker->url,
            'is_active' => true,
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }
}
