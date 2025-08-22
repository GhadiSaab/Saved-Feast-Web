<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Meal>
 */
class MealFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'restaurant_id' => \App\Models\Restaurant::factory(),
            'title' => fake()->words(3, true),
            'description' => fake()->paragraph(),
            'original_price' => fake()->randomFloat(2, 10, 50),
            'current_price' => fake()->randomFloat(2, 5, 25),
            'quantity' => fake()->numberBetween(1, 20),
            'available_from' => now(),
            'available_until' => now()->addDays(7),
            'status' => 'available',
            'image' => null,
        ];
    }
}
