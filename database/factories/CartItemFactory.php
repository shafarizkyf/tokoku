<?php

namespace Database\Factories;

use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CartItem>
 */
class CartItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cart_id' => Cart::factory(),
            'product_id' => Product::factory(),
            'product_variation_id' => ProductVariation::factory(),
            'price_at_time' => $this->faker->numberBetween(1000, 10000),
            'price_discount_at_time' => $this->faker->optional()->numberBetween(500, 9000),
            'quantity' => $this->faker->numberBetween(1, 5),
        ];
    }
}
