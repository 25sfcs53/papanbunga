<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Component>
 */
class ComponentFactory extends Factory
{
    public function definition(): array
    {
        $types = ['huruf', 'hiasan'];

        return [
            'name' => $this->faker->word() . ' ' . $this->faker->randomNumber(2),
            'type' => $this->faker->randomElement($types),
            'quantity_available' => $this->faker->numberBetween(0, 50),
        ];
    }
}
