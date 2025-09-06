<?php

namespace Database\Factories;

use App\Models\Restaurant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RestaurantInvoice>
 */
class RestaurantInvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $periodStart = Carbon::now()->subWeek()->startOfWeek();
        $periodEnd = $periodStart->copy()->endOfWeek();
        
        return [
            'restaurant_id' => Restaurant::factory(),
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'status' => 'draft',
            'subtotal_sales' => $this->faker->randomFloat(2, 100, 1000),
            'commission_rate' => 7.0,
            'commission_total' => $this->faker->randomFloat(2, 7, 70),
            'orders_count' => $this->faker->numberBetween(1, 20),
            'pdf_path' => null,
            'meta' => ['currency' => 'EUR'],
        ];
    }
}
