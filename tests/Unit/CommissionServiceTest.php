<?php

namespace Tests\Unit;

use App\Models\Restaurant;
use App\Services\CommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommissionServiceTest extends TestCase
{
    use RefreshDatabase;
    
    protected CommissionService $commissionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->commissionService = new CommissionService();
    }

    public function test_calculate_commission_with_standard_rate()
    {
        $total = 100.00;
        $rate = 7.0;
        $expectedCommission = 7.00;

        $commission = $this->commissionService->calculateCommission($total, $rate);
        
        $this->assertEquals($expectedCommission, $commission);
    }

    public function test_calculate_commission_with_different_rate()
    {
        $total = 200.00;
        $rate = 5.0;
        $expectedCommission = 10.00;

        $commission = $this->commissionService->calculateCommission($total, $rate);
        
        $this->assertEquals($expectedCommission, $commission);
    }

    public function test_calculate_commission_rounds_to_two_decimals()
    {
        $total = 33.33;
        $rate = 7.0;
        $expectedCommission = 2.33; // 33.33 * 0.07 = 2.3331, rounded to 2.33

        $commission = $this->commissionService->calculateCommission($total, $rate);
        
        $this->assertEquals($expectedCommission, $commission);
    }

    public function test_calculate_commission_with_zero_rate()
    {
        $total = 100.00;
        $rate = 0.0;
        $expectedCommission = 0.00;

        $commission = $this->commissionService->calculateCommission($total, $rate);
        
        $this->assertEquals($expectedCommission, $commission);
    }

    public function test_calculate_commission_with_high_rate()
    {
        $total = 100.00;
        $rate = 15.0;
        $expectedCommission = 15.00;

        $commission = $this->commissionService->calculateCommission($total, $rate);
        
        $this->assertEquals($expectedCommission, $commission);
    }

    public function test_get_commission_rate_returns_restaurant_rate()
    {
        $restaurant = new Restaurant(['commission_rate' => 5.5]);
        
        $rate = $this->commissionService->getCommissionRate($restaurant);
        
        $this->assertEquals(5.5, $rate);
    }

    public function test_get_commission_rate_returns_default_when_restaurant_null()
    {
        $rate = $this->commissionService->getCommissionRate(null);
        
        // Should return the default rate from config (7.0)
        $this->assertEquals(7.0, $rate);
    }

    public function test_get_commission_rate_returns_default_when_restaurant_has_no_rate()
    {
        $restaurant = new Restaurant(['commission_rate' => null]);
        
        $rate = $this->commissionService->getCommissionRate($restaurant);
        
        $this->assertEquals(7.0, $rate);
    }

    public function test_calculate_order_commission_returns_correct_structure()
    {
        $orderTotal = 50.00;
        $restaurant = new Restaurant(['commission_rate' => 8.0]);
        
        $result = $this->commissionService->calculateOrderCommission($orderTotal, $restaurant);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('rate', $result);
        $this->assertArrayHasKey('amount', $result);
        $this->assertEquals(8.0, $result['rate']);
        $this->assertEquals(4.00, $result['amount']);
    }

    public function test_calculate_order_commission_with_null_restaurant()
    {
        $orderTotal = 100.00;
        
        $result = $this->commissionService->calculateOrderCommission($orderTotal, null);
        
        $this->assertEquals(7.0, $result['rate']);
        $this->assertEquals(7.00, $result['amount']);
    }
}
