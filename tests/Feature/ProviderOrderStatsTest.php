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

class ProviderOrderStatsTest extends TestCase
{
    use RefreshDatabase, WithFaker;

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

        // Create provider user
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
    public function route_exists()
    {
        // Test if the route exists at all
        $response = $this->getJson('/api/provider/orders/stats');
        $this->assertNotEquals(404, $response->getStatusCode(), 'Route should exist (should get 401 or 403, not 404)');
    }

    /** @test */
    public function provider_can_fetch_order_stats()
    {
        // Create orders with different statuses
        $this->createOrderWithStatus(Order::STATUS_PENDING);
        $this->createOrderWithStatus(Order::STATUS_PENDING);
        $this->createOrderWithStatus(Order::STATUS_ACCEPTED);
        $this->createOrderWithStatus(Order::STATUS_READY_FOR_PICKUP);
        $this->createOrderWithStatus(Order::STATUS_READY_FOR_PICKUP);
        $this->createOrderWithStatus(Order::STATUS_COMPLETED, now());
        $this->createOrderWithStatus(Order::STATUS_COMPLETED, now()->subDay()); // Yesterday

        $response = $this->actingAs($this->provider)
            ->getJson('/api/provider/orders/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'pending',
                    'accepted',
                    'ready',
                    'completed_today',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'pending' => 2,
                    'accepted' => 1,
                    'ready' => 2,
                    'completed_today' => 1, // Only today's completed order
                ],
            ]);
    }

    /** @test */
    public function provider_without_restaurants_gets_zero_stats()
    {
        // Create provider without restaurants
        $providerWithoutRestaurant = User::factory()->create();
        $providerRole = \App\Models\Role::where('name', 'provider')->first();
        if ($providerRole) {
            $providerWithoutRestaurant->roles()->attach($providerRole->id);
        }

        $response = $this->actingAs($providerWithoutRestaurant)
            ->getJson('/api/provider/orders/stats');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'pending' => 0,
                    'accepted' => 0,
                    'ready' => 0,
                    'completed_today' => 0,
                ],
            ]);
    }

    /** @test */
    public function provider_only_sees_stats_for_their_restaurants()
    {
        // Create another provider with their own restaurant
        $otherProvider = User::factory()->create();
        $providerRole = \App\Models\Role::where('name', 'provider')->first();
        if ($providerRole) {
            $otherProvider->roles()->attach($providerRole->id);
        }

        $otherRestaurant = Restaurant::factory()->create([
            'user_id' => $otherProvider->id,
        ]);

        $otherMeal = Meal::factory()->create([
            'restaurant_id' => $otherRestaurant->id,
            'category_id' => $this->category->id,
        ]);

        // Create orders for both restaurants
        $this->createOrderWithStatus(Order::STATUS_PENDING); // This provider's restaurant
        $this->createOrderWithStatus(Order::STATUS_PENDING); // This provider's restaurant

        // Create order for other provider's restaurant
        $otherOrder = Order::factory()->create([
            'status' => Order::STATUS_PENDING,
            'total_amount' => 25.00,
        ]);

        OrderItem::factory()->create([
            'order_id' => $otherOrder->id,
            'meal_id' => $otherMeal->id,
            'quantity' => 2,
            'price' => 12.50,
        ]);

        $response = $this->actingAs($this->provider)
            ->getJson('/api/provider/orders/stats');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'pending' => 2, // Only this provider's orders
                    'accepted' => 0,
                    'ready' => 0,
                    'completed_today' => 0,
                ],
            ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_stats()
    {
        $response = $this->getJson('/api/provider/orders/stats');

        $response->assertStatus(401);
    }

    /** @test */
    public function non_provider_user_cannot_access_stats()
    {
        $regularUser = User::factory()->create();

        $response = $this->actingAs($regularUser)
            ->getJson('/api/provider/orders/stats');

        $response->assertStatus(403);
    }

    /** @test */
    public function stats_endpoint_handles_rate_limiting()
    {
        // Make multiple rapid requests
        for ($i = 0; $i < 5; $i++) {
            $response = $this->actingAs($this->provider)
                ->getJson('/api/provider/orders/stats');

            $response->assertStatus(200);
        }

        // The endpoint should still work within rate limits
        $response = $this->actingAs($this->provider)
            ->getJson('/api/provider/orders/stats');

        $response->assertStatus(200);
    }

    /** @test */
    public function stats_include_correct_completed_today_count()
    {
        // Create orders completed today and yesterday
        $today = now();
        $yesterday = now()->subDay();

        $this->createOrderWithStatus(Order::STATUS_COMPLETED, $today);
        $this->createOrderWithStatus(Order::STATUS_COMPLETED, $today);
        $this->createOrderWithStatus(Order::STATUS_COMPLETED, $yesterday);

        $response = $this->actingAs($this->provider)
            ->getJson('/api/provider/orders/stats');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'completed_today' => 2, // Only today's completed orders
                ],
            ]);
    }

    /**
     * Helper method to create an order with specific status
     */
    private function createOrderWithStatus(string $status, $completedAt = null): Order
    {
        $order = Order::factory()->create([
            'status' => $status,
            'total_amount' => 25.00,
            'completed_at' => $completedAt,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'meal_id' => $this->meal->id,
            'quantity' => 2,
            'price' => 12.50,
        ]);

        return $order;
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
