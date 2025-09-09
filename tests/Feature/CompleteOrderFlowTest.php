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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * Comprehensive test for the complete order flow from creation to completion
 * This test covers the entire ordering and tracking system we developed
 */
class CompleteOrderFlowTest extends TestCase
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
    public function complete_order_flow_from_creation_to_completion()
    {
        // Step 1: Customer creates an order
        $orderData = [
            'items' => [
                [
                    'meal_id' => $this->meal->id,
                    'quantity' => 2,
                ],
            ],
            'pickup_time' => now()->addMinutes(30),
            'notes' => 'Extra cheese please',
        ];

        $createResponse = $this->actingAs($this->customer)
            ->postJson('/api/orders', $orderData);

        $createResponse->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'user_id',
                    'status',
                    'total_amount',
                    'order_items',
                ],
            ]);

        $orderId = $createResponse->json('data.id');
        $order = Order::find($orderId);

        // Verify order was created with correct status
        $this->assertEquals(Order::STATUS_PENDING, $order->status);
        $this->assertEquals(25.00, $order->total_amount); // 2 * 12.50
        $this->assertEquals($this->customer->id, $order->user_id);

        // Verify order event was logged
        $this->assertDatabaseHas('order_events', [
            'order_id' => $orderId,
            'type' => OrderEvent::TYPE_STATUS_CHANGED,
        ]);

        // Step 2: Provider views their orders and sees the new order
        $providerOrdersResponse = $this->actingAs($this->provider)
            ->getJson('/api/provider/orders?status=PENDING');

        $providerOrdersResponse->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'status',
                            'total_amount',
                            'order_items',
                        ],
                    ],
                ],
            ]);

        // Verify the order appears in provider's pending orders
        $pendingOrders = $providerOrdersResponse->json('data.data');
        $this->assertCount(1, $pendingOrders);
        $this->assertEquals($orderId, $pendingOrders[0]['id']);

        // Step 3: Provider checks order stats
        $statsResponse = $this->actingAs($this->provider)
            ->getJson('/api/provider/orders/stats');

        $statsResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'pending' => 1,
                    'accepted' => 0,
                    'ready' => 0,
                    'completed_today' => 0,
                ],
            ]);

        // Step 4: Provider accepts the order
        $acceptResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$orderId}/accept", [
                'pickup_window_start' => now()->addMinutes(30)->toISOString(),
                'pickup_window_end' => now()->addMinutes(90)->toISOString(),
            ]);

        $acceptResponse->assertStatus(200)
            ->assertJson(['success' => true]);

        $order->refresh();
        $this->assertEquals(Order::STATUS_ACCEPTED, $order->status);
        $this->assertNotNull($order->accepted_at);
        $this->assertNotNull($order->pickup_code_encrypted);
        $this->assertNotNull($order->pickup_window_start);
        $this->assertNotNull($order->pickup_window_end);

        // Step 5: Customer views their order and sees pickup code
        $customerOrderResponse = $this->actingAs($this->customer)
            ->getJson("/api/orders/{$orderId}/show-code");

        $customerOrderResponse->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'code',
                ],
            ]);

        $pickupCode = $customerOrderResponse->json('data.code');
        $this->assertIsString($pickupCode);
        $this->assertEquals(6, strlen($pickupCode)); // 6-digit code

        // Step 6: Provider marks order as ready
        $markReadyResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$orderId}/mark-ready");

        $markReadyResponse->assertStatus(200)
            ->assertJson(['success' => true]);

        $order->refresh();
        $this->assertEquals(Order::STATUS_READY_FOR_PICKUP, $order->status);
        $this->assertNotNull($order->ready_at);

        // Step 7: Provider completes the order with correct code
        $completeResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$orderId}/complete", [
                'code' => $pickupCode,
            ]);

        $completeResponse->assertStatus(200)
            ->assertJson(['success' => true]);

        $order->refresh();
        $this->assertEquals(Order::STATUS_COMPLETED, $order->status);
        $this->assertNotNull($order->completed_at);

        // Step 8: Verify final stats
        $finalStatsResponse = $this->actingAs($this->provider)
            ->getJson('/api/provider/orders/stats');

        $finalStatsResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'pending' => 0,
                    'accepted' => 0,
                    'ready' => 0,
                    'completed_today' => 1,
                ],
            ]);

        // Step 9: Customer can view completed order
        $customerOrdersResponse = $this->actingAs($this->customer)
            ->getJson('/api/me/orders?status=completed');

        $customerOrdersResponse->assertStatus(200);
        $completedOrders = $customerOrdersResponse->json('data.data');
        $this->assertCount(1, $completedOrders);
        $this->assertEquals($orderId, $completedOrders[0]['id']);
        $this->assertEquals(Order::STATUS_COMPLETED, $completedOrders[0]['status']);

        // Step 10: Verify all order events were logged
        $orderEvents = OrderEvent::where('order_id', $orderId)->get();
        $this->assertGreaterThanOrEqual(3, $orderEvents->count()); // At least 3 status changes

        $eventTypes = $orderEvents->pluck('type')->toArray();
        $this->assertContains(OrderEvent::TYPE_STATUS_CHANGED, $eventTypes);
    }

    /** @test */
    public function order_cancellation_flow()
    {
        // Create an order
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

        // Customer cancels the order
        $cancelResponse = $this->actingAs($this->customer)
            ->postJson("/api/orders/{$order->id}/cancel-my-order", [
                'reason' => 'Changed my mind',
            ]);

        $cancelResponse->assertStatus(200)
            ->assertJson(['success' => true]);

        $order->refresh();
        $this->assertEquals(Order::STATUS_CANCELLED_BY_CUSTOMER, $order->status);
        $this->assertNotNull($order->cancelled_at);
        $this->assertEquals('customer', $order->cancelled_by);
        $this->assertEquals('Changed my mind', $order->cancel_reason);

        // Verify order doesn't appear in provider's active orders
        $providerOrdersResponse = $this->actingAs($this->provider)
            ->getJson('/api/provider/orders?status=PENDING');

        $providerOrdersResponse->assertStatus(200);
        $pendingOrders = $providerOrdersResponse->json('data.data');
        $this->assertCount(0, $pendingOrders);
    }

    /** @test */
    public function provider_cancellation_flow()
    {
        // Create an accepted order
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

        // Provider cancels the order
        $cancelResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/cancel", [
                'reason' => 'Out of ingredients',
            ]);

        $cancelResponse->assertStatus(200)
            ->assertJson(['success' => true]);

        $order->refresh();
        $this->assertEquals(Order::STATUS_CANCELLED_BY_RESTAURANT, $order->status);
        $this->assertNotNull($order->cancelled_at);
        $this->assertEquals('restaurant', $order->cancelled_by);
        $this->assertEquals('Out of ingredients', $order->cancel_reason);
    }

    /** @test */
    public function invalid_pickup_code_handling()
    {
        // Create a ready order
        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => Order::STATUS_READY_FOR_PICKUP,
            'total_amount' => 25.00,
            'accepted_at' => now(),
            'ready_at' => now(),
            'pickup_code_encrypted' => app(PickupCodeService::class)->encrypt('123456'),
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'meal_id' => $this->meal->id,
            'quantity' => 2,
            'price' => 12.50,
        ]);

        // Try to complete with wrong code
        $completeResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/complete", [
                'code' => '654321', // Wrong code
            ]);

        $completeResponse->assertStatus(400)
            ->assertJson(['success' => false]);

        $order->refresh();
        $this->assertEquals(Order::STATUS_READY_FOR_PICKUP, $order->status);
        $this->assertEquals(1, $order->pickup_code_attempts);

        // Try with correct code
        $completeResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/complete", [
                'code' => '123456', // Correct code
            ]);

        $completeResponse->assertStatus(200)
            ->assertJson(['success' => true]);

        $order->refresh();
        $this->assertEquals(Order::STATUS_COMPLETED, $order->status);
    }

    /** @test */
    public function order_state_transition_validation()
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

        // Try to mark pending order as ready (should fail)
        $markReadyResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/mark-ready");

        $markReadyResponse->assertStatus(400)
            ->assertJson(['success' => false]);

        // Try to complete pending order (should fail)
        $completeResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/complete", [
                'code' => '123456',
            ]);

        $completeResponse->assertStatus(400)
            ->assertJson(['success' => false]);

        // Accept the order first
        $acceptResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/accept", [
                'pickup_window_start' => now()->addMinutes(30)->toISOString(),
                'pickup_window_end' => now()->addMinutes(90)->toISOString(),
            ]);

        $acceptResponse->assertStatus(200);

        // Now mark as ready should work
        $markReadyResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/mark-ready");

        $markReadyResponse->assertStatus(200);
    }

    /** @test */
    public function order_tracking_with_events()
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

        // Track order through all states
        $states = [
            Order::STATUS_ACCEPTED,
            Order::STATUS_READY_FOR_PICKUP,
            Order::STATUS_COMPLETED,
        ];

        foreach ($states as $state) {
            // Update order to next state
            $order->update(['status' => $state]);

            // Create order event
            OrderEvent::create([
                'order_id' => $order->id,
                'type' => OrderEvent::TYPE_STATUS_CHANGED,
                'data' => ['status' => $state],
            ]);

            // Verify order can be viewed in current state
            $response = $this->actingAs($this->customer)
                ->getJson("/api/orders/{$order->id}/details");

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'id' => $order->id,
                        'status' => $state,
                    ],
                ]);
        }

        // Verify all events were created
        $events = OrderEvent::where('order_id', $order->id)->get();
        $this->assertCount(3, $events);
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
