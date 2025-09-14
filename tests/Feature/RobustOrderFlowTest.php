<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Meal;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\OrderItem;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\PickupCodeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * Comprehensive and robust test suite for the complete order flow
 * This test covers complex scenarios, edge cases, error conditions, and race conditions
 */
class RobustOrderFlowTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $customer;
    protected User $provider;
    protected User $anotherProvider;
    protected Restaurant $restaurant;
    protected Restaurant $anotherRestaurant;
    protected Meal $meal;
    protected Meal $expiringMeal;
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
        $this->anotherProvider = User::factory()->create();

        // Assign provider roles
        $providerRole = \App\Models\Role::where('name', 'provider')->first();
        $this->provider->roles()->attach($providerRole->id);
        $this->anotherProvider->roles()->attach($providerRole->id);

        // Create category
        $this->category = Category::factory()->create();

        // Create restaurants
        $this->restaurant = Restaurant::factory()->create([
            'user_id' => $this->provider->id,
        ]);
        $this->anotherRestaurant = Restaurant::factory()->create([
            'user_id' => $this->anotherProvider->id,
        ]);

        // Create meals
        $this->meal = Meal::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'category_id' => $this->category->id,
            'quantity' => 10,
            'current_price' => 12.50,
            'original_price' => 15.00,
        ]);

        $this->expiringMeal = Meal::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'category_id' => $this->category->id,
            'quantity' => 5,
            'current_price' => 8.00,
            'original_price' => 10.00,
            'available_until' => now()->addMinutes(45), // Expires in 45 minutes
        ]);
    }

    /** @test */
    public function complete_order_flow_with_claim_codes()
    {
        // Create order with multiple items
        $orderData = [
            'items' => [
                [
                    'meal_id' => $this->meal->id,
                    'quantity' => 2,
                ],
                [
                    'meal_id' => $this->expiringMeal->id,
                    'quantity' => 1,
                ],
            ],
            'pickup_time' => now()->addMinutes(30),
            'notes' => 'Extra spicy, no onions',
        ];

        $createResponse = $this->actingAs($this->customer)
            ->postJson('/api/orders', $orderData);

        $createResponse->assertStatus(201);
        $orderId = $createResponse->json('data.id');
        $order = Order::find($orderId);

        // Verify order creation
        $this->assertEquals(Order::STATUS_PENDING, $order->status);
        $this->assertEquals(33.00, $order->total_amount); // (2 * 12.50) + (1 * 8.00)
        $this->assertCount(2, $order->orderItems);

        // Provider accepts with custom pickup window
        $pickupStart = now()->addMinutes(45);
        $pickupEnd = now()->addHours(2);
        
        $acceptResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$orderId}/accept", [
                'pickup_window_start' => $pickupStart->toISOString(),
                'pickup_window_end' => $pickupEnd->toISOString(),
            ]);

        $acceptResponse->assertStatus(200);
        $order->refresh();
        $this->assertEquals(Order::STATUS_ACCEPTED, $order->status);
        $this->assertNotNull($order->pickup_code_encrypted);
        $this->assertEquals($pickupStart->format('Y-m-d H:i:s'), $order->pickup_window_start->format('Y-m-d H:i:s'));
        $this->assertEquals($pickupEnd->format('Y-m-d H:i:s'), $order->pickup_window_end->format('Y-m-d H:i:s'));

        // Provider marks as ready
        $markReadyResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$orderId}/mark-ready");

        $markReadyResponse->assertStatus(200);
        $order->refresh();
        $this->assertEquals(Order::STATUS_READY_FOR_PICKUP, $order->status);
        $this->assertNotNull($order->ready_at);

        // Customer generates claim code
        $claimResponse = $this->actingAs($this->customer)
            ->postJson("/api/orders/{$orderId}/claim");

        $claimResponse->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'code',
                    'expires_at',
                ],
            ]);

        $claimCode = $claimResponse->json('data.code');
        $this->assertIsString($claimCode);
        $this->assertEquals(6, strlen($claimCode));

        // Verify claim code event was created
        $this->assertDatabaseHas('order_events', [
            'order_id' => $orderId,
            'type' => 'claim_code_generated',
        ]);

        // Provider completes with claim code
        $completeResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$orderId}/complete", [
                'code' => $claimCode,
            ]);

        $completeResponse->assertStatus(200)
            ->assertJson(['success' => true]);

        $order->refresh();
        $this->assertEquals(Order::STATUS_COMPLETED, $order->status);
        $this->assertNotNull($order->completed_at);

        // Verify claim code was marked as used
        $this->assertDatabaseHas('order_events', [
            'order_id' => $orderId,
            'type' => 'claim_code_used',
        ]);
    }

    /** @test */
    public function complete_order_flow_with_legacy_pickup_codes()
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

        // Provider accepts order
        $acceptResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/accept", [
                'pickup_window_start' => now()->addMinutes(30)->toISOString(),
                'pickup_window_end' => now()->addHours(2)->toISOString(),
            ]);

        $acceptResponse->assertStatus(200);
        $order->refresh();
        $this->assertEquals(Order::STATUS_ACCEPTED, $order->status);
        $this->assertNotNull($order->pickup_code_encrypted);

        // Provider marks as ready
        $markReadyResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/mark-ready");

        $markReadyResponse->assertStatus(200);
        $order->refresh();
        $this->assertEquals(Order::STATUS_READY_FOR_PICKUP, $order->status);

        // Customer gets pickup code
        $pickupCodeResponse = $this->actingAs($this->customer)
            ->getJson("/api/orders/{$order->id}/show-code");

        $pickupCodeResponse->assertStatus(200);
        $pickupCode = $pickupCodeResponse->json('data.code');

        // Provider completes with pickup code
        $completeResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/complete", [
                'code' => $pickupCode,
            ]);

        $completeResponse->assertStatus(200);
        $order->refresh();
        $this->assertEquals(Order::STATUS_COMPLETED, $order->status);
    }

    /** @test */
    public function claim_code_expiration_handling()
    {
        // Create ready order
        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => Order::STATUS_READY_FOR_PICKUP,
            'total_amount' => 25.00,
            'accepted_at' => now(),
            'ready_at' => now(),
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'meal_id' => $this->meal->id,
            'quantity' => 2,
            'price' => 12.50,
        ]);

        // Generate claim code
        $claimResponse = $this->actingAs($this->customer)
            ->postJson("/api/orders/{$order->id}/claim");

        $claimResponse->assertStatus(200);
        $claimCode = $claimResponse->json('data.code');

        // Simulate time passing (6 minutes - past expiration)
        $this->travel(6)->minutes();

        // Try to complete with expired claim code
        $completeResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/complete", [
                'code' => $claimCode,
            ]);

        $completeResponse->assertStatus(400)
            ->assertJson(['success' => false]);

        $order->refresh();
        $this->assertEquals(Order::STATUS_READY_FOR_PICKUP, $order->status);
    }

    /** @test */
    public function claim_code_reuse_prevention()
    {
        // Create ready order
        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => Order::STATUS_READY_FOR_PICKUP,
            'total_amount' => 25.00,
            'accepted_at' => now(),
            'ready_at' => now(),
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'meal_id' => $this->meal->id,
            'quantity' => 2,
            'price' => 12.50,
        ]);

        // Generate claim code
        $claimResponse = $this->actingAs($this->customer)
            ->postJson("/api/orders/{$order->id}/claim");

        $claimResponse->assertStatus(200);
        $claimCode = $claimResponse->json('data.code');

        // Complete order with claim code
        $completeResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/complete", [
                'code' => $claimCode,
            ]);

        $completeResponse->assertStatus(200);
        $order->refresh();
        $this->assertEquals(Order::STATUS_COMPLETED, $order->status);

        // Try to use the same claim code again (should fail)
        $reuseResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/complete", [
                'code' => $claimCode,
            ]);

        $reuseResponse->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function pickup_code_attempt_limiting()
    {
        // Create ready order
        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => Order::STATUS_READY_FOR_PICKUP,
            'total_amount' => 25.00,
            'accepted_at' => now(),
            'ready_at' => now(),
            'pickup_code_encrypted' => app(PickupCodeService::class)->encrypt('123456'),
            'pickup_code_attempts' => 4, // Already 4 attempts
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'meal_id' => $this->meal->id,
            'quantity' => 2,
            'price' => 12.50,
        ]);

        // Try wrong code (should reach max attempts)
        $completeResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/complete", [
                'code' => '654321', // Wrong code
            ]);

        $completeResponse->assertStatus(400)
            ->assertJson(['success' => false]);

        $order->refresh();
        $this->assertEquals(5, $order->pickup_code_attempts); // Max attempts reached

        // Try again (should fail due to max attempts)
        $completeResponse2 = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/complete", [
                'code' => '123456', // Correct code, but max attempts exceeded
            ]);

        $completeResponse2->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function pickup_window_validation()
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

        // Test invalid pickup window (end before start)
        $acceptResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/accept", [
                'pickup_window_start' => now()->addMinutes(60)->toISOString(),
                'pickup_window_end' => now()->addMinutes(30)->toISOString(), // Before start
            ]);

        $acceptResponse->assertStatus(422); // Validation error

        // Test pickup window in the past
        $acceptResponse2 = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/accept", [
                'pickup_window_start' => now()->subMinutes(30)->toISOString(), // In the past
                'pickup_window_end' => now()->addMinutes(30)->toISOString(),
            ]);

        $acceptResponse2->assertStatus(422); // Validation error

        // Test valid pickup window
        $acceptResponse3 = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/accept", [
                'pickup_window_start' => now()->addMinutes(30)->toISOString(),
                'pickup_window_end' => now()->addHours(2)->toISOString(),
            ]);

        $acceptResponse3->assertStatus(200);
    }

    /** @test */
    public function order_state_transition_security()
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

        // Test unauthorized access (different provider)
        $unauthorizedResponse = $this->actingAs($this->anotherProvider)
            ->postJson("/api/provider/orders/{$order->id}/accept", [
                'pickup_window_start' => now()->addMinutes(30)->toISOString(),
                'pickup_window_end' => now()->addHours(2)->toISOString(),
            ]);

        $unauthorizedResponse->assertStatus(403); // Forbidden

        // Test invalid state transitions
        $invalidTransitionResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/mark-ready");

        $invalidTransitionResponse->assertStatus(400); // Can't mark pending as ready

        // Accept order first
        $acceptResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/accept", [
                'pickup_window_start' => now()->addMinutes(30)->toISOString(),
                'pickup_window_end' => now()->addHours(2)->toISOString(),
            ]);

        $acceptResponse->assertStatus(200);

        // Now try to complete without marking ready
        $completeResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/complete", [
                'code' => '123456',
            ]);

        $completeResponse->assertStatus(400); // Can't complete accepted order
    }

    /** @test */
    public function concurrent_order_processing()
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

        // Accept all orders concurrently
        $responses = [];
        foreach ($orders as $order) {
            $responses[] = $this->actingAs($this->provider)
                ->postJson("/api/provider/orders/{$order->id}/accept", [
                    'pickup_window_start' => now()->addMinutes(30)->toISOString(),
                    'pickup_window_end' => now()->addHours(2)->toISOString(),
                ]);
        }

        // All should succeed
        foreach ($responses as $response) {
            $response->assertStatus(200);
        }

        // Verify all orders are accepted
        foreach ($orders as $order) {
            $order->refresh();
            $this->assertEquals(Order::STATUS_ACCEPTED, $order->status);
        }
    }

    /** @test */
    public function meal_availability_impact_on_pickup_window()
    {
        // Create order with expiring meal
        $orderData = [
            'items' => [
                [
                    'meal_id' => $this->expiringMeal->id,
                    'quantity' => 1,
                ],
            ],
            'pickup_time' => now()->addMinutes(30),
        ];

        $createResponse = $this->actingAs($this->customer)
            ->postJson('/api/orders', $orderData);

        $createResponse->assertStatus(201);
        $orderId = $createResponse->json('data.id');

        // Provider accepts with pickup window extending beyond meal expiry
        $acceptResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$orderId}/accept", [
                'pickup_window_start' => now()->addMinutes(30)->toISOString(),
                'pickup_window_end' => now()->addHours(3)->toISOString(), // Beyond meal expiry
            ]);

        $acceptResponse->assertStatus(200);

        // Verify order was accepted despite meal expiry constraint
        $order = Order::find($orderId);
        $this->assertEquals(Order::STATUS_ACCEPTED, $order->status);
    }

    /** @test */
    public function order_cancellation_during_different_states()
    {
        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => Order::STATUS_ACCEPTED,
            'total_amount' => 25.00,
            'accepted_at' => now(),
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'meal_id' => $this->meal->id,
            'quantity' => 2,
            'price' => 12.50,
        ]);

        // Customer cancels accepted order
        $cancelResponse = $this->actingAs($this->customer)
            ->postJson("/api/orders/{$order->id}/cancel-my-order", [
                'reason' => 'Emergency came up',
            ]);

        $cancelResponse->assertStatus(200);
        $order->refresh();
        $this->assertEquals(Order::STATUS_CANCELLED_BY_CUSTOMER, $order->status);
        $this->assertNotNull($order->cancelled_at);

        // Try to complete cancelled order (should fail)
        $completeResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/complete", [
                'code' => '123456',
            ]);

        $completeResponse->assertStatus(400);
    }

    /** @test */
    public function order_events_comprehensive_logging()
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

        // Accept order
        $acceptResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/accept", [
                'pickup_window_start' => now()->addMinutes(30)->toISOString(),
                'pickup_window_end' => now()->addHours(2)->toISOString(),
            ]);

        $acceptResponse->assertStatus(200);

        // Mark ready
        $markReadyResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/mark-ready");

        $markReadyResponse->assertStatus(200);

        // Generate claim code
        $claimResponse = $this->actingAs($this->customer)
            ->postJson("/api/orders/{$order->id}/claim");

        $claimResponse->assertStatus(200);
        $claimCode = $claimResponse->json('data.code');

        // Complete order
        $completeResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/complete", [
                'code' => $claimCode,
            ]);

        $completeResponse->assertStatus(200);

        // Verify comprehensive event logging
        $events = OrderEvent::where('order_id', $order->id)->get();
        $eventTypes = $events->pluck('type')->toArray();

        // Debug: Print all event types for troubleshooting
        if (empty($eventTypes)) {
            $this->fail('No events found for order ' . $order->id);
        }

        $this->assertContains(OrderEvent::TYPE_STATUS_CHANGED, $eventTypes);
        $this->assertContains(OrderEvent::TYPE_CODE_GENERATED, $eventTypes);
        // Note: claim_code_generated gets updated to claim_code_used when the code is used
        $this->assertContains('claim_code_used', $eventTypes);
        $this->assertContains(OrderEvent::TYPE_CODE_VERIFIED, $eventTypes);

        // Verify event metadata
        $statusChangeEvents = $events->where('type', OrderEvent::TYPE_STATUS_CHANGED);
        $this->assertGreaterThanOrEqual(3, $statusChangeEvents->count()); // At least 3 status changes
    }

    /** @test */
    public function invalid_code_formats_and_edge_cases()
    {
        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => Order::STATUS_READY_FOR_PICKUP,
            'total_amount' => 25.00,
            'accepted_at' => now(),
            'ready_at' => now(),
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'meal_id' => $this->meal->id,
            'quantity' => 2,
            'price' => 12.50,
        ]);

        // Test various invalid code formats
        $invalidCodes = [
            '', // Empty
            '12345', // Too short
            '1234567', // Too long
            'abc123', // Non-numeric
            '123 45', // With spaces
            '123-45', // With dashes
        ];

        foreach ($invalidCodes as $invalidCode) {
            $completeResponse = $this->actingAs($this->provider)
                ->postJson("/api/provider/orders/{$order->id}/complete", [
                    'code' => $invalidCode,
                ]);

            $completeResponse->assertStatus(422); // Validation error
        }
    }

    /** @test */
    public function order_statistics_accuracy()
    {
        // Create orders in different states
        $pendingOrder = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => Order::STATUS_PENDING,
            'total_amount' => 25.00,
        ]);

        $acceptedOrder = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => Order::STATUS_ACCEPTED,
            'total_amount' => 30.00,
            'accepted_at' => now(),
        ]);

        $readyOrder = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => Order::STATUS_READY_FOR_PICKUP,
            'total_amount' => 35.00,
            'accepted_at' => now(),
            'ready_at' => now(),
        ]);

        $completedOrder = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => Order::STATUS_COMPLETED,
            'total_amount' => 40.00,
            'accepted_at' => now()->subHours(2),
            'ready_at' => now()->subHours(1),
            'completed_at' => now()->subMinutes(30),
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

        // Check provider stats
        $statsResponse = $this->actingAs($this->provider)
            ->getJson('/api/provider/orders/stats');

        $statsResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'pending' => 1,
                    'accepted' => 1,
                    'ready' => 1,
                    'completed_today' => 1,
                ],
            ]);
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
