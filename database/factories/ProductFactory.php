<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'base_price' => $this->faker->randomFloat(2, 20, 200),
            'photo' => null,
            'description' => $this->faker->sentence(),
            // Defaults needed by order allocation logic (match migration enum/column names)
            'required_papan_quantity' => '1',
            'required_papan_color' => 'Putih',
            'default_rack_color' => 'Putih',
        ];
    }
}
