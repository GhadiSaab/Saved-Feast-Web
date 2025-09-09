<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => $this->faker->randomElement(['PENDING', 'COMPLETED', 'CANCELLED_BY_CUSTOMER']),
            'total_amount' => $this->faker->randomFloat(2, 15, 120),
        ];
    }

    /**
     * Indicate that the order is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'PENDING',
        ]);
    }

    /**
     * Indicate that the order is accepted.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'ACCEPTED',
        ]);
    }

    /**
     * Indicate that the order is ready for pickup.
     */
    public function readyForPickup(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'READY_FOR_PICKUP',
        ]);
    }

    /**
     * Indicate that the order is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'COMPLETED',
        ]);
    }

    /**
     * Indicate that the order is cancelled by customer.
     */
    public function cancelledByCustomer(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'CANCELLED_BY_CUSTOMER',
        ]);
    }

    /**
     * Indicate that the order is cancelled by restaurant.
     */
    public function cancelledByRestaurant(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'CANCELLED_BY_RESTAURANT',
        ]);
    }

    /**
     * Indicate that the order is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'EXPIRED',
        ]);
    }
}
