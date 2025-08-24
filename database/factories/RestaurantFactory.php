<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Restaurant>
 */
class RestaurantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'name' => fake()->company(),
            'description' => fake()->paragraph(),
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'website' => fake()->url(),
            'image' => null,
            'profile_picture_path' => null,
            'cuisine_type' => fake()->randomElement(['Italian', 'Mexican', 'Chinese', 'Japanese', 'Indian', 'American', 'Thai', 'French']),
            'delivery_radius' => fake()->randomFloat(2, 1, 20),
            'is_active' => true,
            'average_rating' => fake()->randomFloat(2, 0, 5),
        ];
    }
}
