<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Meal;
use App\Models\Restaurant;
use App\Models\User;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->category = Category::factory()->create();
        $this->restaurant = Restaurant::factory()->create();
        $this->user = User::factory()->create();
        
        // Create meals with different prices
        $this->meal1 = Meal::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'category_id' => $this->category->id,
            'current_price' => 15.99,
            'original_price' => 15.99
        ]);
        
        $this->meal2 = Meal::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'category_id' => $this->category->id,
            'current_price' => 12.50,
            'original_price' => 12.50
        ]);
    }

    public function test_order_can_be_created_with_items()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'total_amount' => 0
        ]);

        // Add items to order
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'meal_id' => $this->meal1->id,
            'quantity' => 2,
            'price' => $this->meal1->current_price,
            'original_price' => $this->meal1->original_price
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'meal_id' => $this->meal2->id,
            'quantity' => 1,
            'price' => $this->meal2->current_price,
            'original_price' => $this->meal2->original_price
        ]);

        // Calculate expected total
        $expectedTotal = ($this->meal1->current_price * 2) + $this->meal2->current_price;
        
        $this->assertEquals(2, $order->orderItems()->count());
        $this->assertEquals($expectedTotal, $order->orderItems->sum(function($item) {
            return $item->price * $item->quantity;
        }));
    }

    public function test_order_item_total_calculation()
    {
        $orderItem = OrderItem::factory()->create([
            'quantity' => 3,
            'price' => 10.00
        ]);

        $expectedTotal = 3 * 10.00;
        $this->assertEquals($expectedTotal, $orderItem->price * $orderItem->quantity);
    }

    public function test_order_status_validation()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending'
        ]);

        $this->assertTrue(in_array($order->status, ['pending', 'completed', 'cancelled']));
    }

    public function test_order_belongs_to_user()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id
        ]);

        $this->assertEquals($this->user->id, $order->user->id);
    }

    public function test_order_has_many_items()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id
        ]);

        OrderItem::factory()->count(3)->create([
            'order_id' => $order->id
        ]);

        $this->assertEquals(3, $order->orderItems()->count());
    }

    public function test_order_total_amount_is_positive()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'total_amount' => 25.50
        ]);

        $this->assertGreaterThan(0, $order->total_amount);
    }

    public function test_order_can_be_cancelled()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending'
        ]);

        $order->update(['status' => 'cancelled']);

        $this->assertEquals('cancelled', $order->fresh()->status);
    }

    public function test_order_can_be_completed()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending'
        ]);

        $order->update(['status' => 'completed']);

        $this->assertEquals('completed', $order->fresh()->status);
    }
}
