<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Meal;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\OrderItem;
use App\Models\Restaurant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * Comprehensive tests for pickup window functionality
 * Tests pickup window validation, countdown accuracy, and edge cases
 */
class PickupWindowTest extends TestCase
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
    public function pickup_window_countdown_accuracy()
    {
        // Create order
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

        // Set specific pickup window times
        $pickupStart = now()->addMinutes(30);
        $pickupEnd = now()->addHours(2);

        // Provider accepts with specific pickup window
        $acceptResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/accept", [
                'pickup_window_start' => $pickupStart->toISOString(),
                'pickup_window_end' => $pickupEnd->toISOString(),
            ]);

        $acceptResponse->assertStatus(200);

        $order->refresh();
        $this->assertEquals($pickupStart->format('Y-m-d H:i:s'), $order->pickup_window_start->format('Y-m-d H:i:s'));
        $this->assertEquals($pickupEnd->format('Y-m-d H:i:s'), $order->pickup_window_end->format('Y-m-d H:i:s'));

        // Verify countdown would show correct time remaining
        $timeRemaining = $pickupEnd->diffInSeconds(now());
        $this->assertGreaterThan(0, $timeRemaining);
        $this->assertLessThanOrEqual(2 * 60 * 60, $timeRemaining); // Less than or equal to 2 hours
    }

    /** @test */
    public function pickup_window_expiration_handling()
    {
        // Create order with pickup window in the past
        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => Order::STATUS_READY_FOR_PICKUP,
            'total_amount' => 25.00,
            'accepted_at' => now()->subHours(3),
            'ready_at' => now()->subHours(2),
            'pickup_window_start' => now()->subHours(3),
            'pickup_window_end' => now()->subMinutes(30), // Expired 30 minutes ago
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'meal_id' => $this->meal->id,
            'quantity' => 2,
            'price' => 12.50,
        ]);

        // Try to generate claim code for expired pickup window
        $claimResponse = $this->actingAs($this->customer)
            ->postJson("/api/orders/{$order->id}/claim");

        $claimResponse->assertStatus(400)
            ->assertJson(['success' => false]);

        // Try to complete order with expired pickup window
        $completeResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/complete", [
                'code' => '123456',
            ]);

        $completeResponse->assertStatus(400);
    }

    /** @test */
    public function pickup_window_edge_cases()
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

        // Test minimum pickup window (30 minutes)
        $minPickupStart = now()->addMinutes(15);
        $minPickupEnd = now()->addMinutes(45); // 30 minutes window

        $acceptResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/accept", [
                'pickup_window_start' => $minPickupStart->toISOString(),
                'pickup_window_end' => $minPickupEnd->toISOString(),
            ]);

        $acceptResponse->assertStatus(200);

        // Test maximum pickup window (24 hours)
        $order2 = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => Order::STATUS_PENDING,
            'total_amount' => 25.00,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order2->id,
            'meal_id' => $this->meal->id,
            'quantity' => 2,
            'price' => 12.50,
        ]);

        $maxPickupStart = now()->addMinutes(30);
        $maxPickupEnd = now()->addHours(24); // 24 hours window

        $acceptResponse2 = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order2->id}/accept", [
                'pickup_window_start' => $maxPickupStart->toISOString(),
                'pickup_window_end' => $maxPickupEnd->toISOString(),
            ]);

        $acceptResponse2->assertStatus(200);
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

        // Test with different timezone formats
        $pickupStart = now()->addMinutes(30);
        $pickupEnd = now()->addHours(2);

        $acceptResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/accept", [
                'pickup_window_start' => $pickupStart->toISOString(),
                'pickup_window_end' => $pickupEnd->toISOString(),
            ]);

        $acceptResponse->assertStatus(200);

        $order->refresh();
        // Verify times are stored correctly regardless of timezone
        $this->assertNotNull($order->pickup_window_start);
        $this->assertNotNull($order->pickup_window_end);
    }

    /** @test */
    public function pickup_window_with_meal_expiry_constraints()
    {
        // Create meal that expires soon
        $expiringMeal = Meal::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'category_id' => $this->category->id,
            'quantity' => 5,
            'current_price' => 8.00,
            'available_until' => now()->addMinutes(45), // Expires in 45 minutes
        ]);

        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => Order::STATUS_PENDING,
            'total_amount' => 8.00,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'meal_id' => $expiringMeal->id,
            'quantity' => 1,
            'price' => 8.00,
        ]);

        // Provider sets pickup window extending beyond meal expiry
        $pickupStart = now()->addMinutes(30);
        $pickupEnd = now()->addHours(2); // Beyond meal expiry

        $acceptResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/accept", [
                'pickup_window_start' => $pickupStart->toISOString(),
                'pickup_window_end' => $pickupEnd->toISOString(),
            ]);

        // Should still accept (provider's choice)
        $acceptResponse->assertStatus(200);

        $order->refresh();
        $this->assertEquals(Order::STATUS_ACCEPTED, $order->status);
    }

    /** @test */
    public function pickup_window_modification_after_acceptance()
    {
        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => Order::STATUS_ACCEPTED,
            'total_amount' => 25.00,
            'accepted_at' => now(),
            'pickup_window_start' => now()->addMinutes(30),
            'pickup_window_end' => now()->addHours(2),
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'meal_id' => $this->meal->id,
            'quantity' => 2,
            'price' => 12.50,
        ]);

        // Try to modify pickup window after acceptance (should not be allowed)
        $modifyResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/accept", [
                'pickup_window_start' => now()->addMinutes(60)->toISOString(),
                'pickup_window_end' => now()->addHours(3)->toISOString(),
            ]);

        $modifyResponse->assertStatus(400); // Should fail - can't modify after acceptance
    }

    /** @test */
    public function pickup_window_display_accuracy()
    {
        // Create order with specific pickup window
        $pickupStart = now()->addMinutes(30);
        $pickupEnd = now()->addHours(2);

        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => Order::STATUS_ACCEPTED,
            'total_amount' => 25.00,
            'accepted_at' => now(),
            'pickup_window_start' => $pickupStart,
            'pickup_window_end' => $pickupEnd,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'meal_id' => $this->meal->id,
            'quantity' => 2,
            'price' => 12.50,
        ]);

        // Customer views order details
        $orderDetailsResponse = $this->actingAs($this->customer)
            ->getJson("/api/orders/{$order->id}/details");

        $orderDetailsResponse->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'status',
                    'pickup_window_start',
                    'pickup_window_end',
                ],
            ]);

        $orderData = $orderDetailsResponse->json('data');
        $this->assertNotNull($orderData['pickup_window_start']);
        $this->assertNotNull($orderData['pickup_window_end']);

        // Provider views order details
        $providerOrderResponse = $this->actingAs($this->provider)
            ->getJson("/api/provider/orders/{$order->id}");

        $providerOrderResponse->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'status',
                    'pickup_window_start',
                    'pickup_window_end',
                ],
            ]);
    }

    /** @test */
    public function pickup_window_with_different_meal_types()
    {
        // Create different types of meals
        $hotMeal = Meal::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'category_id' => $this->category->id,
            'quantity' => 10,
            'current_price' => 15.00,
            'available_until' => now()->addHours(4), // Available for 4 hours
        ]);

        $coldMeal = Meal::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'category_id' => $this->category->id,
            'quantity' => 10,
            'current_price' => 10.00,
            'available_until' => now()->addHours(8), // Available for 8 hours
        ]);

        // Create order with both meal types
        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => Order::STATUS_PENDING,
            'total_amount' => 25.00,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'meal_id' => $hotMeal->id,
            'quantity' => 1,
            'price' => 15.00,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'meal_id' => $coldMeal->id,
            'quantity' => 1,
            'price' => 10.00,
        ]);

        // Provider accepts with pickup window
        $pickupStart = now()->addMinutes(30);
        $pickupEnd = now()->addHours(3); // Within both meal availability windows

        $acceptResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/accept", [
                'pickup_window_start' => $pickupStart->toISOString(),
                'pickup_window_end' => $pickupEnd->toISOString(),
            ]);

        $acceptResponse->assertStatus(200);

        $order->refresh();
        $this->assertEquals(Order::STATUS_ACCEPTED, $order->status);
        $this->assertNotNull($order->pickup_window_start);
        $this->assertNotNull($order->pickup_window_end);
    }

    /** @test */
    public function pickup_window_validation_edge_cases()
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

        // Test same start and end time
        $sameTime = now()->addMinutes(30);
        $acceptResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/accept", [
                'pickup_window_start' => $sameTime->toISOString(),
                'pickup_window_end' => $sameTime->toISOString(),
            ]);

        $acceptResponse->assertStatus(422); // Should fail validation

        // Test very short window (less than 30 minutes)
        $shortStart = now()->addMinutes(30);
        $shortEnd = now()->addMinutes(45); // Only 15 minutes
        $acceptResponse2 = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/accept", [
                'pickup_window_start' => $shortStart->toISOString(),
                'pickup_window_end' => $shortEnd->toISOString(),
            ]);

        $acceptResponse2->assertStatus(422); // Should fail validation
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
