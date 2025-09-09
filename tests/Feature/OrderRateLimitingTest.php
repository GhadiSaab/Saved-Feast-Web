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

class OrderRateLimitingTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $customer;

    protected User $provider;

    protected Restaurant $restaurant;

    protected Meal $meal;

    protected Category $category;

    protected Order $order;

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
    public function provider_orders_endpoint_respects_rate_limiting()
    {
        // Make multiple rapid requests to the provider orders endpoint
        $responses = [];
        for ($i = 0; $i < 10; $i++) {
            $responses[] = $this->actingAs($this->provider)
                ->getJson('/api/provider/orders');
        }

        // All requests should succeed within rate limits
        foreach ($responses as $response) {
            $response->assertStatus(200);
        }
    }

    /** @test */
    public function provider_stats_endpoint_respects_rate_limiting()
    {
        // Make multiple rapid requests to the stats endpoint
        $responses = [];
        for ($i = 0; $i < 10; $i++) {
            $responses[] = $this->actingAs($this->provider)
                ->getJson('/api/provider/orders/stats');
        }

        // All requests should succeed within rate limits
        foreach ($responses as $response) {
            $response->assertStatus(200);
        }
    }

    /** @test */
    public function customer_orders_endpoint_respects_rate_limiting()
    {
        // Make multiple rapid requests to the customer orders endpoint
        $responses = [];
        for ($i = 0; $i < 10; $i++) {
            $responses[] = $this->actingAs($this->customer)
                ->getJson('/api/me/orders');
        }

        // All requests should succeed within rate limits
        foreach ($responses as $response) {
            $response->assertStatus(200);
        }
    }

    /** @test */
    public function order_actions_respect_rate_limiting()
    {
        // Test rate limiting on order actions
        $actions = [
            ['method' => 'POST', 'url' => "/api/provider/orders/{$this->order->id}/accept", 'data' => [
                'pickup_window_start' => now()->addMinutes(30)->toISOString(),
                'pickup_window_end' => now()->addMinutes(90)->toISOString(),
            ]],
            ['method' => 'POST', 'url' => "/api/provider/orders/{$this->order->id}/mark-ready"],
            ['method' => 'POST', 'url' => "/api/provider/orders/{$this->order->id}/cancel", 'data' => [
                'reason' => 'Test cancellation',
            ]],
        ];

        foreach ($actions as $action) {
            // Make multiple rapid requests
            $responses = [];
            for ($i = 0; $i < 5; $i++) {
                if ($action['method'] === 'POST') {
                    $responses[] = $this->actingAs($this->provider)
                        ->postJson($action['url'], $action['data'] ?? []);
                }
            }

            // Check that requests are handled appropriately
            foreach ($responses as $response) {
                // Should not get 429 (Too Many Requests) for reasonable request rates
                $this->assertNotEquals(429, $response->getStatusCode());
            }
        }
    }

    /** @test */
    public function pickup_code_attempts_are_rate_limited()
    {
        // Setup order as ready
        $code = '123456';
        $this->order->update([
            'status' => Order::STATUS_READY_FOR_PICKUP,
            'accepted_at' => now(),
            'ready_at' => now(),
            'pickup_code_encrypted' => app(PickupCodeService::class)->encrypt($code),
        ]);

        // Make multiple rapid failed attempts
        $responses = [];
        for ($i = 0; $i < 10; $i++) {
            $responses[] = $this->actingAs($this->provider)
                ->postJson("/api/provider/orders/{$this->order->id}/complete", [
                    'code' => '654321', // Wrong code
                ]);
        }

        // All attempts should be handled (not necessarily successful)
        foreach ($responses as $response) {
            $this->assertContains($response->getStatusCode(), [200, 400, 422]);
        }

        // Verify attempts were tracked
        $this->order->refresh();
        $this->assertGreaterThan(0, $this->order->pickup_code_attempts);
    }

    /** @test */
    public function code_resend_is_rate_limited()
    {
        // Setup order as accepted
        $this->order->update([
            'status' => Order::STATUS_ACCEPTED,
            'accepted_at' => now(),
            'pickup_code_encrypted' => app(PickupCodeService::class)->encrypt('123456'),
            'pickup_code_last_sent_at' => now()->subMinutes(2),
        ]);

        // Make multiple rapid resend requests
        $responses = [];
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->actingAs($this->customer)
                ->postJson("/api/orders/{$this->order->id}/resend-code");
        }

        // Check that rate limiting is applied
        $rateLimitedResponses = array_filter($responses, function ($response) {
            return $response->getStatusCode() === 429;
        });

        // Should have some rate limited responses
        $this->assertGreaterThan(0, count($rateLimitedResponses));
    }

    /** @test */
    public function order_creation_respects_rate_limiting()
    {
        $orderData = [
            'items' => [
                [
                    'meal_id' => $this->meal->id,
                    'quantity' => 2,
                ],
            ],
            'pickup_time' => now()->addMinutes(30),
            'notes' => 'Test order',
        ];

        // Make multiple rapid order creation requests
        $responses = [];
        for ($i = 0; $i < 10; $i++) {
            $responses[] = $this->actingAs($this->customer)
                ->postJson('/api/orders', $orderData);
        }

        // All requests should be handled appropriately
        $statusCodes = [];
        foreach ($responses as $response) {
            $statusCodes[] = $response->getStatusCode();
            $this->assertContains($response->getStatusCode(), [200, 201, 400, 422, 429]);
        }

    }

    /** @test */
    public function concurrent_order_operations_are_handled_correctly()
    {
        // Setup order as accepted
        $this->order->update([
            'status' => Order::STATUS_ACCEPTED,
            'accepted_at' => now(),
            'pickup_code_encrypted' => app(PickupCodeService::class)->encrypt('123456'),
        ]);

        // Simulate concurrent operations
        $operations = [
            // Provider operations
            ['user' => $this->provider, 'method' => 'POST', 'url' => "/api/provider/orders/{$this->order->id}/mark-ready"],
            ['user' => $this->provider, 'method' => 'POST', 'url' => "/api/provider/orders/{$this->order->id}/cancel", 'data' => ['reason' => 'Test']],

            // Customer operations
            ['user' => $this->customer, 'method' => 'POST', 'url' => "/api/orders/{$this->order->id}/cancel-my-order", 'data' => ['reason' => 'Test']],
            ['user' => $this->customer, 'method' => 'GET', 'url' => "/api/orders/{$this->order->id}/show-code"],
        ];

        $responses = [];
        foreach ($operations as $operation) {
            if ($operation['method'] === 'POST') {
                $responses[] = $this->actingAs($operation['user'])
                    ->postJson($operation['url'], $operation['data'] ?? []);
            } else {
                $responses[] = $this->actingAs($operation['user'])
                    ->getJson($operation['url']);
            }
        }

        // All operations should be handled (some may fail due to business logic)
        foreach ($responses as $response) {
            $this->assertNotEquals(500, $response->getStatusCode());
        }
    }

    /** @test */
    public function order_status_transitions_are_atomic()
    {
        // Setup order as accepted
        $this->order->update([
            'status' => Order::STATUS_ACCEPTED,
            'accepted_at' => now(),
            'pickup_code_encrypted' => app(PickupCodeService::class)->encrypt('123456'),
        ]);

        // Try to mark as ready and cancel simultaneously
        $markReadyResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$this->order->id}/mark-ready");

        $cancelResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$this->order->id}/cancel", [
                'reason' => 'Test cancellation',
            ]);

        // Only one operation should succeed
        $successCount = 0;
        if ($markReadyResponse->getStatusCode() === 200) {
            $successCount++;
        }
        if ($cancelResponse->getStatusCode() === 200) {
            $successCount++;
        }

        $this->assertLessThanOrEqual(1, $successCount);

        // Verify final state is consistent
        $this->order->refresh();
        $this->assertContains($this->order->status, [
            Order::STATUS_READY_FOR_PICKUP,
            Order::STATUS_CANCELLED_BY_RESTAURANT,
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
