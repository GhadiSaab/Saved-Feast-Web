<?php

namespace App\Services;

class CommissionService
{
    /**
     * Calculate commission amount from total and rate
     *
     * @param float $total
     * @param float $rate
     * @return float
     */
    public function calculateCommission(float $total, float $rate): float
    {
        return round(($total * $rate) / 100, 2);
    }

    /**
     * Get the commission rate for a restaurant
     * Falls back to default rate if restaurant doesn't have one set
     *
     * @param \App\Models\Restaurant|null $restaurant
     * @return float
     */
    public function getCommissionRate($restaurant = null): float
    {
        if ($restaurant && $restaurant->commission_rate) {
            return (float) $restaurant->commission_rate;
        }

        return (float) config('savedfeast.commission.default_rate');
    }

    /**
     * Calculate commission for an order
     *
     * @param float $orderTotal
     * @param \App\Models\Restaurant|null $restaurant
     * @return array ['rate' => float, 'amount' => float]
     */
    public function calculateOrderCommission(float $orderTotal, $restaurant = null): array
    {
        $rate = $this->getCommissionRate($restaurant);
        $amount = $this->calculateCommission($orderTotal, $rate);

        return [
            'rate' => $rate,
            'amount' => $amount,
        ];
    }
}
