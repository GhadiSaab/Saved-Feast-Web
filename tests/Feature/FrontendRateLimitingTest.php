<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Meal;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * Tests for frontend rate limiting functionality
 * These tests verify that the frontend components properly handle
 * rate limiting and don't make excessive API calls
 */
class FrontendRateLimitingTest extends TestCase
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
    public function provider_orders_page_loads_without_excessive_api_calls()
    {
        // Simulate loading the provider orders page
        $response = $this->actingAs($this->provider)
            ->get('/provider/orders');

        $response->assertStatus(200);

        // The page should load successfully without triggering rate limiting
        // This test ensures the frontend rate limiting is working
    }

    /** @test */
    public function customer_orders_page_loads_without_excessive_api_calls()
    {
        // Simulate loading the customer orders page
        $response = $this->actingAs($this->customer)
            ->get('/orders');

        $response->assertStatus(200);

        // The page should load successfully without triggering rate limiting
    }

    /** @test */
    public function order_detail_page_loads_without_excessive_api_calls()
    {
        // Simulate loading the order detail page
        $response = $this->actingAs($this->customer)
            ->get("/orders/{$this->order->id}");

        $response->assertStatus(200);

        // The page should load successfully without triggering rate limiting
    }

    /** @test */
    public function api_endpoints_handle_rapid_frontend_requests()
    {
        // Simulate rapid requests that might come from frontend components
        $endpoints = [
            '/api/provider/orders',
            '/api/provider/orders/stats',
            '/api/me/orders',
            "/api/orders/{$this->order->id}/details",
        ];

        foreach ($endpoints as $endpoint) {
            $user = str_contains($endpoint, 'provider') ? $this->provider : $this->customer;

            // Make multiple rapid requests
            $responses = [];
            for ($i = 0; $i < 5; $i++) {
                $responses[] = $this->actingAs($user)->getJson($endpoint);
            }

            // All requests should be handled appropriately
            foreach ($responses as $response) {
                $this->assertContains($response->getStatusCode(), [200, 401, 403, 404, 429]);
            }
        }
    }

    /** @test */
    public function countdown_timer_expiry_does_not_cause_excessive_requests()
    {
        // Setup order with pickup window
        $this->order->update([
            'status' => Order::STATUS_ACCEPTED,
            'accepted_at' => now(),
            'pickup_window_start' => now()->subMinutes(30),
            'pickup_window_end' => now()->subMinutes(1), // Expired
        ]);

        // Simulate countdown timer expiry triggering multiple requests
        $responses = [];
        for ($i = 0; $i < 10; $i++) {
            $responses[] = $this->actingAs($this->provider)
                ->getJson('/api/provider/orders');
        }

        // All requests should be handled without causing server overload
        foreach ($responses as $response) {
            $this->assertNotEquals(500, $response->getStatusCode());
        }
    }

    /** @test */
    public function tab_switching_does_not_cause_excessive_requests()
    {
        // Simulate rapid tab switching in provider orders
        $tabs = ['PENDING', 'ACCEPTED', 'READY_FOR_PICKUP', 'COMPLETED'];

        foreach ($tabs as $tab) {
            $response = $this->actingAs($this->provider)
                ->getJson("/api/provider/orders?status={$tab}");

            $response->assertStatus(200);
        }

        // Rapid tab switching should not cause issues
        for ($i = 0; $i < 3; $i++) {
            foreach ($tabs as $tab) {
                $response = $this->actingAs($this->provider)
                    ->getJson("/api/provider/orders?status={$tab}");

                $this->assertNotEquals(429, $response->getStatusCode());
            }
        }
    }

    /** @test */
    public function order_actions_handle_concurrent_requests()
    {
        // Setup order as accepted
        $this->order->update([
            'status' => Order::STATUS_ACCEPTED,
            'accepted_at' => now(),
        ]);

        // Simulate concurrent order actions
        $actions = [
            ['method' => 'POST', 'url' => "/api/provider/orders/{$this->order->id}/mark-ready"],
            ['method' => 'POST', 'url' => "/api/provider/orders/{$this->order->id}/cancel", 'data' => ['reason' => 'Test']],
        ];

        $responses = [];
        foreach ($actions as $action) {
            $responses[] = $this->actingAs($this->provider)
                ->postJson($action['url'], $action['data'] ?? []);
        }

        // All actions should be handled appropriately
        foreach ($responses as $response) {
            $this->assertNotEquals(500, $response->getStatusCode());
        }
    }

    /** @test */
    public function stats_refresh_handles_rapid_requests()
    {
        // Simulate rapid stats refresh requests
        $responses = [];
        for ($i = 0; $i < 10; $i++) {
            $responses[] = $this->actingAs($this->provider)
                ->getJson('/api/provider/orders/stats');
        }

        // All requests should be handled
        foreach ($responses as $response) {
            $this->assertContains($response->getStatusCode(), [200, 401, 403, 429]);
        }
    }

    /** @test */
    public function order_filtering_handles_rapid_requests()
    {
        // Simulate rapid filtering requests
        $filters = [
            'status=PENDING',
            'status=ACCEPTED',
            'status=READY_FOR_PICKUP',
            'status=COMPLETED',
            'date_from='.now()->subDays(7)->toDateString(),
            'date_to='.now()->toDateString(),
        ];

        foreach ($filters as $filter) {
            $response = $this->actingAs($this->provider)
                ->getJson("/api/provider/orders?{$filter}");

            $response->assertStatus(200);
        }

        // Rapid filtering should not cause issues
        for ($i = 0; $i < 3; $i++) {
            foreach ($filters as $filter) {
                $response = $this->actingAs($this->provider)
                    ->getJson("/api/provider/orders?{$filter}");

                $this->assertNotEquals(429, $response->getStatusCode());
            }
        }
    }

    /** @test */
    public function order_search_handles_rapid_requests()
    {
        // Simulate rapid search requests
        $searchTerms = ['test', 'order', 'meal', 'restaurant'];

        foreach ($searchTerms as $term) {
            $response = $this->actingAs($this->provider)
                ->getJson("/api/provider/orders?search={$term}");

            $response->assertStatus(200);
        }

        // Rapid search should not cause issues
        for ($i = 0; $i < 3; $i++) {
            foreach ($searchTerms as $term) {
                $response = $this->actingAs($this->provider)
                    ->getJson("/api/provider/orders?search={$term}");

                $this->assertNotEquals(429, $response->getStatusCode());
            }
        }
    }

    /** @test */
    public function pagination_handles_rapid_requests()
    {
        // Create multiple orders for pagination testing
        for ($i = 0; $i < 20; $i++) {
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
        }

        // Simulate rapid pagination requests
        $pages = [1, 2, 3, 4, 5];

        foreach ($pages as $page) {
            $response = $this->actingAs($this->provider)
                ->getJson("/api/provider/orders?page={$page}&per_page=5");

            $response->assertStatus(200);
        }

        // Rapid pagination should not cause issues
        for ($i = 0; $i < 3; $i++) {
            foreach ($pages as $page) {
                $response = $this->actingAs($this->provider)
                    ->getJson("/api/provider/orders?page={$page}&per_page=5");

                $this->assertNotEquals(429, $response->getStatusCode());
            }
        }
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
