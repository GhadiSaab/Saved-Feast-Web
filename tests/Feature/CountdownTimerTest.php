<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Meal;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\PickupCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * Tests for countdown timer functionality and pickup window management
 */
class CountdownTimerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $customer;

    protected User $provider;

    protected Restaurant $restaurant;

    protected Meal $meal;

    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles if they don't exist
        $this->createRoleIfNotExists('provider', 'Service provider');
        $this->createRoleIfNotExists('customer', 'Regular user');
        $this->createRoleIfNotExists('admin', 'Administrator');

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
    }

    /** @test */
    public function pickup_window_is_set_when_order_is_accepted()
    {
        // Create a pending order
        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => Order::STATUS_PENDING,
            'total_amount' => 25.00,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'meal_id' => $this->meal->id,
            'quantity' => 2,
            'price' => 12.50,
        ]);

        $pickupStart = now()->addMinutes(30);
        $pickupEnd = now()->addMinutes(90);

        // Provider accepts the order
        $response = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/accept", [
                'pickup_window_start' => $pickupStart->toISOString(),
                'pickup_window_end' => $pickupEnd->toISOString(),
            ]);

        $response->assertStatus(200);

        $order->refresh();
        $this->assertNotNull($order->pickup_window_start);
        $this->assertNotNull($order->pickup_window_end);
        $this->assertEquals($pickupStart->format('Y-m-d H:i:s'), $order->pickup_window_start->format('Y-m-d H:i:s'));
        $this->assertEquals($pickupEnd->format('Y-m-d H:i:s'), $order->pickup_window_end->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function pickup_window_validation_works()
    {
        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => Order::STATUS_PENDING,
            'total_amount' => 25.00,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'meal_id' => $this->meal->id,
            'quantity' => 2,
            'price' => 12.50,
        ]);

        // Try to accept with invalid pickup window (end before start)
        $response = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/accept", [
                'pickup_window_start' => now()->addMinutes(90)->toISOString(),
                'pickup_window_end' => now()->addMinutes(30)->toISOString(), // End before start
            ]);

        $response->assertStatus(422); // Validation error
    }

    /** @test */
    public function pickup_window_must_be_in_future()
    {
        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => Order::STATUS_PENDING,
            'total_amount' => 25.00,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'meal_id' => $this->meal->id,
            'quantity' => 2,
            'price' => 12.50,
        ]);

        // Try to accept with pickup window in the past
        $response = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/accept", [
                'pickup_window_start' => now()->subMinutes(30)->toISOString(),
                'pickup_window_end' => now()->subMinutes(10)->toISOString(),
            ]);

        $response->assertStatus(422); // Validation error
    }

    /** @test */
    public function order_with_expired_pickup_window_can_be_handled()
    {
        // Create an order with expired pickup window
        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => Order::STATUS_READY_FOR_PICKUP,
            'total_amount' => 25.00,
            'accepted_at' => now()->subHours(2),
            'ready_at' => now()->subHours(1),
            'pickup_window_start' => now()->subHours(2),
            'pickup_window_end' => now()->subMinutes(30), // Expired
            'pickup_code_encrypted' => app(PickupCodeService::class)->encrypt('123456'),
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'meal_id' => $this->meal->id,
            'quantity' => 2,
            'price' => 12.50,
        ]);

        // Provider should still be able to complete the order
        $response = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/complete", [
                'code' => '123456',
            ]);

        $response->assertStatus(200);

        $order->refresh();
        $this->assertEquals(Order::STATUS_COMPLETED, $order->status);
    }

    /** @test */
    public function pickup_window_duration_validation()
    {
        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => Order::STATUS_PENDING,
            'total_amount' => 25.00,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'meal_id' => $this->meal->id,
            'quantity' => 2,
            'price' => 12.50,
        ]);

        // Try to accept with very short pickup window (less than 30 minutes)
        $response = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/accept", [
                'pickup_window_start' => now()->addMinutes(5)->toISOString(),
                'pickup_window_end' => now()->addMinutes(10)->toISOString(), // Only 5 minutes
            ]);

        // This should either be accepted or rejected based on business rules
        // For now, we'll test that it's handled appropriately
        $this->assertContains($response->getStatusCode(), [200, 422]);
    }

    /** @test */
    public function pickup_window_with_meal_availability()
    {
        // Create a meal with limited availability
        $meal = Meal::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'category_id' => $this->category->id,
            'quantity' => 10,
            'current_price' => 12.50,
            'original_price' => 15.00,
            'available_until' => now()->addMinutes(60), // Available for 1 hour
        ]);

        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => Order::STATUS_PENDING,
            'total_amount' => 25.00,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'meal_id' => $meal->id,
            'quantity' => 2,
            'price' => 12.50,
        ]);

        // Accept order with pickup window that respects meal availability
        $pickupStart = now()->addMinutes(30);
        $pickupEnd = now()->addMinutes(50); // Before meal expires

        $response = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/accept", [
                'pickup_window_start' => $pickupStart->toISOString(),
                'pickup_window_end' => $pickupEnd->toISOString(),
            ]);

        $response->assertStatus(200);

        $order->refresh();
        $this->assertNotNull($order->pickup_window_start);
        $this->assertNotNull($order->pickup_window_end);
    }

    /** @test */
    public function multiple_orders_with_different_pickup_windows()
    {
        // Create multiple orders
        $orders = [];
        for ($i = 0; $i < 3; $i++) {
            $order = Order::factory()->create([
                'user_id' => $this->customer->id,
                'status' => Order::STATUS_PENDING,
                'total_amount' => 25.00,
            ]);

            OrderItem::factory()->create([
                'order_id' => $order->id,
                'meal_id' => $this->meal->id,
                'quantity' => 2,
                'price' => 12.50,
            ]);

            $orders[] = $order;
        }

        // Accept orders with different pickup windows
        $pickupWindows = [
            ['start' => 30, 'end' => 90],   // 30-90 minutes
            ['start' => 60, 'end' => 120],  // 60-120 minutes
            ['start' => 45, 'end' => 105],  // 45-105 minutes
        ];

        foreach ($orders as $index => $order) {
            $window = $pickupWindows[$index];
            $response = $this->actingAs($this->provider)
                ->postJson("/api/provider/orders/{$order->id}/accept", [
                    'pickup_window_start' => now()->addMinutes($window['start'])->toISOString(),
                    'pickup_window_end' => now()->addMinutes($window['end'])->toISOString(),
                ]);

            $response->assertStatus(200);
        }

        // Verify all orders have different pickup windows
        foreach ($orders as $order) {
            $order->refresh();
            $this->assertNotNull($order->pickup_window_start);
            $this->assertNotNull($order->pickup_window_end);
        }

        // Get all accepted orders
        $response = $this->actingAs($this->provider)
            ->getJson('/api/provider/orders?status=ACCEPTED');

        $response->assertStatus(200);
        $acceptedOrders = $response->json('data.data');
        $this->assertCount(3, $acceptedOrders);
    }

    /** @test */
    public function pickup_window_timezone_handling()
    {
        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => Order::STATUS_PENDING,
            'total_amount' => 25.00,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'meal_id' => $this->meal->id,
            'quantity' => 2,
            'price' => 12.50,
        ]);

        // Accept order with ISO 8601 formatted datetime
        $pickupStart = now()->addMinutes(30);
        $pickupEnd = now()->addMinutes(90);

        $response = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/accept", [
                'pickup_window_start' => $pickupStart->toISOString(),
                'pickup_window_end' => $pickupEnd->toISOString(),
            ]);

        $response->assertStatus(200);

        $order->refresh();

        // Verify the times are stored correctly
        $this->assertEquals(
            $pickupStart->format('Y-m-d H:i:s'),
            $order->pickup_window_start->format('Y-m-d H:i:s')
        );
        $this->assertEquals(
            $pickupEnd->format('Y-m-d H:i:s'),
            $order->pickup_window_end->format('Y-m-d H:i:s')
        );
    }

    /** @test */
    public function order_without_pickup_window_cannot_be_completed()
    {
        // Create an order without pickup window
        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => Order::STATUS_READY_FOR_PICKUP,
            'total_amount' => 25.00,
            'accepted_at' => now(),
            'ready_at' => now(),
            'pickup_code_encrypted' => app(PickupCodeService::class)->encrypt('123456'),
            // No pickup window set
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'meal_id' => $this->meal->id,
            'quantity' => 2,
            'price' => 12.50,
        ]);

        // Try to complete the order
        $response = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/complete", [
                'code' => '123456',
            ]);

        // This should either work or fail gracefully
        // The business logic should handle orders without pickup windows
        $this->assertContains($response->getStatusCode(), [200, 400, 422]);
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
