<?php

namespace Tests\Feature;

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

class OrderTrackingTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $customer;

    protected User $provider;

    protected Restaurant $restaurant;

    protected Meal $meal;

    protected Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->customer = User::factory()->create();
        $this->provider = User::factory()->create();

        // Assign provider role
        $providerRole = \App\Models\Role::where('name', 'provider')->first();
        if ($providerRole) {
            $this->provider->roles()->attach($providerRole->id);
        }

        // Create restaurant
        $this->restaurant = Restaurant::factory()->create([
            'user_id' => $this->provider->id,
        ]);

        // Create meal
        $this->meal = Meal::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'quantity' => 10,
        ]);

        // Create order
        $this->order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => Order::STATUS_PENDING,
            'total_amount' => 25.00,
        ]);

        // Create order item
        OrderItem::factory()->create([
            'order_id' => $this->order->id,
            'meal_id' => $this->meal->id,
            'quantity' => 2,
            'price' => 12.50,
        ]);
    }

    /** @test */
    public function customer_can_view_their_orders()
    {
        $response = $this->actingAs($this->customer)
            ->getJson('/api/me/orders');

        $response->assertStatus(200)
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
    }

    /** @test */
    public function provider_can_view_orders_for_their_restaurant()
    {
        $response = $this->actingAs($this->provider)
            ->getJson('/api/provider/orders');

        $response->assertStatus(200)
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
    }

    /** @test */
    public function provider_can_accept_order()
    {
        $response = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$this->order->id}/accept", [
                'pickup_window_start' => now()->addMinutes(30)->toISOString(),
                'pickup_window_end' => now()->addMinutes(90)->toISOString(),
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->order->refresh();
        $this->assertEquals(Order::STATUS_ACCEPTED, $this->order->status);
        $this->assertNotNull($this->order->accepted_at);
        $this->assertNotNull($this->order->pickup_code_encrypted);

        // Check that event was logged
        $this->assertDatabaseHas('order_events', [
            'order_id' => $this->order->id,
            'type' => OrderEvent::TYPE_STATUS_CHANGED,
        ]);
    }

    /** @test */
    public function provider_can_mark_order_as_ready()
    {
        // First accept the order
        $this->order->update([
            'status' => Order::STATUS_ACCEPTED,
            'accepted_at' => now(),
            'pickup_code_encrypted' => app(PickupCodeService::class)->encrypt('123456'),
        ]);

        $response = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$this->order->id}/mark-ready");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->order->refresh();
        $this->assertEquals(Order::STATUS_READY_FOR_PICKUP, $this->order->status);
        $this->assertNotNull($this->order->ready_at);
    }

    /** @test */
    public function provider_can_complete_order_with_correct_code()
    {
        // Setup order as ready
        $code = '123456';
        $this->order->update([
            'status' => Order::STATUS_READY_FOR_PICKUP,
            'accepted_at' => now(),
            'ready_at' => now(),
            'pickup_code_encrypted' => app(PickupCodeService::class)->encrypt($code),
        ]);

        $response = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$this->order->id}/complete", [
                'code' => $code,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->order->refresh();
        $this->assertEquals(Order::STATUS_COMPLETED, $this->order->status);
        $this->assertNotNull($this->order->completed_at);
    }

    /** @test */
    public function provider_cannot_complete_order_with_incorrect_code()
    {
        // Setup order as ready
        $this->order->update([
            'status' => Order::STATUS_READY_FOR_PICKUP,
            'accepted_at' => now(),
            'ready_at' => now(),
            'pickup_code_encrypted' => app(PickupCodeService::class)->encrypt('123456'),
        ]);

        $response = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$this->order->id}/complete", [
                'code' => '654321',
            ]);

        $response->assertStatus(400)
            ->assertJson(['success' => false]);

        $this->order->refresh();
        $this->assertEquals(Order::STATUS_READY_FOR_PICKUP, $this->order->status);
        $this->assertEquals(1, $this->order->pickup_code_attempts);
    }

    /** @test */
    public function customer_can_cancel_pending_order()
    {
        $response = $this->actingAs($this->customer)
            ->postJson("/api/orders/{$this->order->id}/cancel-my-order", [
                'reason' => 'Changed my mind',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->order->refresh();
        $this->assertEquals(Order::STATUS_CANCELLED_BY_CUSTOMER, $this->order->status);
        $this->assertNotNull($this->order->cancelled_at);
        $this->assertEquals('customer', $this->order->cancelled_by);
        $this->assertEquals('Changed my mind', $this->order->cancel_reason);
    }

    /** @test */
    public function provider_can_cancel_order()
    {
        $response = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$this->order->id}/cancel", [
                'reason' => 'Out of ingredients',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->order->refresh();
        $this->assertEquals(Order::STATUS_CANCELLED_BY_RESTAURANT, $this->order->status);
        $this->assertNotNull($this->order->cancelled_at);
        $this->assertEquals('restaurant', $this->order->cancelled_by);
        $this->assertEquals('Out of ingredients', $this->order->cancel_reason);
    }

    /** @test */
    public function customer_can_view_pickup_code_for_accepted_order()
    {
        // Setup order as accepted
        $this->order->update([
            'status' => Order::STATUS_ACCEPTED,
            'accepted_at' => now(),
            'pickup_code_encrypted' => app(PickupCodeService::class)->encrypt('123456'),
        ]);

        $response = $this->actingAs($this->customer)
            ->getJson("/api/orders/{$this->order->id}/show-code");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'code' => '123456',
                ],
            ]);
    }

    /** @test */
    public function customer_cannot_view_pickup_code_for_pending_order()
    {
        $response = $this->actingAs($this->customer)
            ->getJson("/api/orders/{$this->order->id}/show-code");

        $response->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function customer_can_resend_pickup_code()
    {
        // Setup order as accepted
        $this->order->update([
            'status' => Order::STATUS_ACCEPTED,
            'accepted_at' => now(),
            'pickup_code_encrypted' => app(PickupCodeService::class)->encrypt('123456'),
            'pickup_code_last_sent_at' => now()->subMinutes(2), // 2 minutes ago
        ]);

        $response = $this->actingAs($this->customer)
            ->postJson("/api/orders/{$this->order->id}/resend-code");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function customer_cannot_resend_code_too_soon()
    {
        // Setup order as accepted with recent send
        $this->order->update([
            'status' => Order::STATUS_ACCEPTED,
            'accepted_at' => now(),
            'pickup_code_encrypted' => app(PickupCodeService::class)->encrypt('123456'),
            'pickup_code_last_sent_at' => now()->subSeconds(30), // 30 seconds ago
        ]);

        $response = $this->actingAs($this->customer)
            ->postJson("/api/orders/{$this->order->id}/resend-code");

        $response->assertStatus(429)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function unauthorized_user_cannot_access_orders()
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->getJson("/api/orders/{$this->order->id}/details");

        $response->assertStatus(403);
    }

    /** @test */
    public function provider_cannot_access_orders_from_other_restaurants()
    {
        $otherProvider = User::factory()->create();
        $providerRole = \App\Models\Role::where('name', 'provider')->first();
        if ($providerRole) {
            $otherProvider->roles()->attach($providerRole->id);
        }
        $otherRestaurant = Restaurant::factory()->create([
            'user_id' => $otherProvider->id,
        ]);

        $response = $this->actingAs($otherProvider)
            ->getJson("/api/provider/orders/{$this->order->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function order_state_transitions_are_enforced()
    {
        // Try to mark pending order as ready (should fail)
        $response = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$this->order->id}/mark-ready");

        $response->assertStatus(400)
            ->assertJson(['success' => false]);

        // Try to complete pending order (should fail)
        $response = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$this->order->id}/complete", [
                'code' => '123456',
            ]);

        $response->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function pickup_code_attempts_are_tracked()
    {
        // Setup order as ready
        $this->order->update([
            'status' => Order::STATUS_READY_FOR_PICKUP,
            'accepted_at' => now(),
            'ready_at' => now(),
            'pickup_code_encrypted' => app(PickupCodeService::class)->encrypt('123456'),
        ]);

        // Make multiple failed attempts
        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($this->provider)
                ->postJson("/api/provider/orders/{$this->order->id}/complete", [
                    'code' => '654321',
                ]);
        }

        $this->order->refresh();
        $this->assertEquals(3, $this->order->pickup_code_attempts);

        // Check that events were logged
        $this->assertDatabaseHas('order_events', [
            'order_id' => $this->order->id,
            'type' => OrderEvent::TYPE_CODE_ATTEMPT,
        ]);
    }
}
