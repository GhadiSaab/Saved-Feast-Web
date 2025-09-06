<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\RestaurantInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RestaurantInvoiceItem>
 */
class RestaurantInvoiceItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => RestaurantInvoice::factory(),
            'order_id' => Order::factory(),
            'order_total' => $this->faker->randomFloat(2, 10, 100),
            'commission_rate' => 7.0,
            'commission_amount' => $this->faker->randomFloat(2, 0.7, 7.0),
        ];
    }
}
