<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Meal;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\OrderItem;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\OrderExpiryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * Tests for order expiration functionality
 * Tests automatic expiration of orders with expired pickup windows
 */
class OrderExpirationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $customer;
    protected User $provider;
    protected Restaurant $restaurant;
    protected Meal $meal;
    protected Category $category;
    protected OrderExpiryService $orderExpiryService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles if they don't exist
        $this->createRoleIfNotExists('provider', 'Service provider');
        $this->createRoleIfNotExists('customer', 'Regular user');

        // Create test users
        $this->customer = User::factory()->create();
        $this->provider = User::factory()->create();

        // Assign provider role
        $providerRole = \App\Models\Role::where('name', 'provider')->first();
        $this->provider->roles()->attach($providerRole->id);

        // Create category
        $this->category = Category::factory()->create();

        // Create restaurant
        $this->restaurant = Restaurant::factory()->create([
            'user_id' => $this->provider->id,
        ]);

        // Create meal
        $this->meal = Meal::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'category_id' => $this->category->id,
            'quantity' => 10,
            'current_price' => 12.50,
            'original_price' => 15.00,
        ]);

        $this->orderExpiryService = app(OrderExpiryService::class);
    }

    /** @test */
    public function expired_orders_are_automatically_cancelled()
    {
        // Create an order with expired pickup window
        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => Order::STATUS_ACCEPTED,
            'total_amount' => 25.00,
            'accepted_at' => now()->subHours(2),
            'pickup_window_start' => now()->subHours(2),
            'pickup_window_end' => now()->subMinutes(20), // Expired 20 minutes ago
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'meal_id' => $this->meal->id,
            'quantity' => 2,
            'price' => 12.50,
        ]);

        // Run the expiry service
        $expiredCount = $this->orderExpiryService->expireOverdue();

        // Verify order was expired
        $this->assertEquals(1, $expiredCount);
        $order->refresh();
        $this->assertEquals(Order::STATUS_EXPIRED, $order->status);
        $this->assertNotNull($order->expired_at);
        $this->assertEquals(Order::CANCELLED_BY_SYSTEM, $order->cancelled_by);
        $this->assertEquals('Order expired after pickup window', $order->cancel_reason);

        // Verify event was logged
        $this->assertDatabaseHas('order_events', [
            'order_id' => $order->id,
            'type' => OrderEvent::TYPE_EXPIRED,
        ]);
    }

    /** @test */
    public function orders_within_grace_period_are_not_expired()
    {
        // Create an order that just expired (within grace period)
        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => Order::STATUS_ACCEPTED,
            'total_amount' => 25.00,
            'accepted_at' => now()->subHours(1),
            'pickup_window_start' => now()->subHours(1),
            'pickup_window_end' => now()->subMinutes(5), // Expired 5 minutes ago (within 10-minute grace period)
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'meal_id' => $this->meal->id,
            'quantity' => 2,
            'price' => 12.50,
        ]);

        // Run the expiry service
        $expiredCount = $this->orderExpiryService->expireOverdue();

        // Verify order was NOT expired (still within grace period)
        $this->assertEquals(0, $expiredCount);
        $order->refresh();
        $this->assertEquals(Order::STATUS_ACCEPTED, $order->status);
        $this->assertNull($order->expired_at);
    }

    /** @test */
    public function only_accepted_and_ready_orders_can_be_expired()
    {
        // Create orders in different statuses
        $pendingOrder = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => Order::STATUS_PENDING,
            'total_amount' => 25.00,
            'pickup_window_end' => now()->subHours(1),
        ]);

        $acceptedOrder = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => Order::STATUS_ACCEPTED,
            'total_amount' => 25.00,
            'accepted_at' => now()->subHours(2),
            'pickup_window_start' => now()->subHours(2),
            'pickup_window_end' => now()->subMinutes(20),
        ]);

        $readyOrder = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => Order::STATUS_READY_FOR_PICKUP,
            'total_amount' => 25.00,
            'accepted_at' => now()->subHours(2),
            'ready_at' => now()->subHours(1),
            'pickup_window_start' => now()->subHours(2),
            'pickup_window_end' => now()->subMinutes(20),
        ]);

        $completedOrder = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => Order::STATUS_COMPLETED,
            'total_amount' => 25.00,
            'completed_at' => now()->subMinutes(30),
            'pickup_window_end' => now()->subHours(1),
        ]);

        // Create order items for all orders
        foreach ([$pendingOrder, $acceptedOrder, $readyOrder, $completedOrder] as $order) {
            OrderItem::factory()->create([
                'order_id' => $order->id,
                'meal_id' => $this->meal->id,
                'quantity' => 2,
                'price' => 12.50,
            ]);
        }

        // Run the expiry service
        $expiredCount = $this->orderExpiryService->expireOverdue();

        // Verify only accepted and ready orders were expired
        $this->assertEquals(2, $expiredCount);
        
        $pendingOrder->refresh();
        $acceptedOrder->refresh();
        $readyOrder->refresh();
        $completedOrder->refresh();

        $this->assertEquals(Order::STATUS_PENDING, $pendingOrder->status); // Should remain pending
        $this->assertEquals(Order::STATUS_EXPIRED, $acceptedOrder->status); // Should be expired
        $this->assertEquals(Order::STATUS_EXPIRED, $readyOrder->status); // Should be expired
        $this->assertEquals(Order::STATUS_COMPLETED, $completedOrder->status); // Should remain completed
    }

    /** @test */
    public function expired_orders_appear_in_cancelled_tab()
    {
        // Create an expired order
        $expiredOrder = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => Order::STATUS_EXPIRED,
            'total_amount' => 25.00,
            'expired_at' => now()->subMinutes(10),
            'cancelled_by' => Order::CANCELLED_BY_SYSTEM,
            'cancel_reason' => 'Order expired after pickup window',
        ]);

        OrderItem::factory()->create([
            'order_id' => $expiredOrder->id,
            'meal_id' => $this->meal->id,
            'quantity' => 2,
            'price' => 12.50,
        ]);

        // Test provider can see expired orders in cancelled tab
        $response = $this->actingAs($this->provider)
            ->getJson('/api/provider/orders?status[]=CANCELLED_BY_CUSTOMER&status[]=CANCELLED_BY_RESTAURANT&status[]=EXPIRED');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'status',
                            'total_amount',
                        ],
                    ],
                ],
            ]);

        $orders = $response->json('data.data');
        $this->assertCount(1, $orders);
        $this->assertEquals($expiredOrder->id, $orders[0]['id']);
        $this->assertEquals(Order::STATUS_EXPIRED, $orders[0]['status']);
    }

    /** @test */
    public function provider_stats_include_cancelled_orders()
    {
        // Create orders in different statuses
        $expiredOrder = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => Order::STATUS_EXPIRED,
            'total_amount' => 25.00,
            'expired_at' => now()->subMinutes(10),
        ]);

        $cancelledOrder = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => Order::STATUS_CANCELLED_BY_CUSTOMER,
            'total_amount' => 30.00,
            'cancelled_at' => now()->subMinutes(5),
        ]);

        $pendingOrder = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => Order::STATUS_PENDING,
            'total_amount' => 35.00,
        ]);

        // Create order items for all orders
        foreach ([$expiredOrder, $cancelledOrder, $pendingOrder] as $order) {
            OrderItem::factory()->create([
                'order_id' => $order->id,
                'meal_id' => $this->meal->id,
                'quantity' => 2,
                'price' => 12.50,
            ]);
        }

        // Test provider stats include cancelled count
        $response = $this->actingAs($this->provider)
            ->getJson('/api/provider/orders/stats');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'pending' => 1,
                    'accepted' => 0,
                    'ready' => 0,
                    'completed_today' => 0,
                    'cancelled' => 2, // expired + cancelled
                ],
            ]);
    }

    /** @test */
    public function expired_orders_cannot_be_completed()
    {
        // Create an expired order
        $expiredOrder = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => Order::STATUS_EXPIRED,
            'total_amount' => 25.00,
            'expired_at' => now()->subMinutes(10),
        ]);

        OrderItem::factory()->create([
            'order_id' => $expiredOrder->id,
            'meal_id' => $this->meal->id,
            'quantity' => 2,
            'price' => 12.50,
        ]);

        // Try to complete expired order
        $response = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$expiredOrder->id}/complete", [
                'code' => '123456',
            ]);

        $response->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function expired_orders_cannot_generate_claim_codes()
    {
        // Create an expired order
        $expiredOrder = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => Order::STATUS_EXPIRED,
            'total_amount' => 25.00,
            'expired_at' => now()->subMinutes(10),
        ]);

        OrderItem::factory()->create([
            'order_id' => $expiredOrder->id,
            'meal_id' => $this->meal->id,
            'quantity' => 2,
            'price' => 12.50,
        ]);

        // Try to generate claim code for expired order
        $response = $this->actingAs($this->customer)
            ->postJson("/api/orders/{$expiredOrder->id}/claim");

        $response->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function order_expiry_with_meal_restocking()
    {
        // Create an order with specific meal quantities
        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => Order::STATUS_ACCEPTED,
            'total_amount' => 25.00,
            'accepted_at' => now()->subHours(2),
            'pickup_window_start' => now()->subHours(2),
            'pickup_window_end' => now()->subMinutes(20),
        ]);

        $meal = Meal::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'category_id' => $this->category->id,
            'quantity' => 5, // Start with 5
            'current_price' => 12.50,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'meal_id' => $meal->id,
            'quantity' => 2, // Order 2
        ]);

        // Verify initial meal quantity
        $this->assertEquals(5, $meal->fresh()->quantity);

        // Run the expiry service
        $expiredCount = $this->orderExpiryService->expireOverdue();

        // Verify order was expired
        $this->assertEquals(1, $expiredCount);
        $order->refresh();
        $this->assertEquals(Order::STATUS_EXPIRED, $order->status);

        // Verify meal was restocked (quantity should be back to 5)
        $meal->refresh();
        $this->assertEquals(5, $meal->quantity);
    }

    /**
     * Helper method to create role if it doesn't exist
     */
    private function createRoleIfNotExists(string $name, string $description): void
    {
        if (! \App\Models\Role::where('name', $name)->exists()) {
            \App\Models\Role::create(['name' => $name, 'description' => $description]);
        }
    }
}
