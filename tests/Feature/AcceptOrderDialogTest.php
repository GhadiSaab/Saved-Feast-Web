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
 * Tests for the AcceptOrderDialog functionality
 * Tests pickup window setting, validation, and provider workflow
 */
class AcceptOrderDialogTest extends TestCase
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
    public function provider_can_set_custom_pickup_window()
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

        // Provider sets custom pickup window (1 hour from now to 3 hours from now)
        $pickupStart = now()->addHour();
        $pickupEnd = now()->addHours(3);

        $acceptResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/accept", [
                'pickup_window_start' => $pickupStart->toISOString(),
                'pickup_window_end' => $pickupEnd->toISOString(),
            ]);

        $acceptResponse->assertStatus(200)
            ->assertJson(['success' => true]);

        $order->refresh();
        $this->assertEquals(Order::STATUS_ACCEPTED, $order->status);
        $this->assertEquals($pickupStart->format('Y-m-d H:i:s'), $order->pickup_window_start->format('Y-m-d H:i:s'));
        $this->assertEquals($pickupEnd->format('Y-m-d H:i:s'), $order->pickup_window_end->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function pickup_window_validation_rules()
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

        // Test 1: Pickup window in the past
        $acceptResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/accept", [
                'pickup_window_start' => now()->subMinutes(30)->toISOString(),
                'pickup_window_end' => now()->addMinutes(30)->toISOString(),
            ]);

        $acceptResponse->assertStatus(422)
            ->assertJsonValidationErrors(['pickup_window_start']);

        // Test 2: End time before start time
        $acceptResponse2 = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/accept", [
                'pickup_window_start' => now()->addMinutes(60)->toISOString(),
                'pickup_window_end' => now()->addMinutes(30)->toISOString(),
            ]);

        $acceptResponse2->assertStatus(422)
            ->assertJsonValidationErrors(['pickup_window_end']);

        // Test 3: Missing pickup window times
        $acceptResponse3 = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/accept", []);

        $acceptResponse3->assertStatus(422)
            ->assertJsonValidationErrors(['pickup_window_start', 'pickup_window_end']);
    }

    /** @test */
    public function pickup_window_minimum_duration_validation()
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

        // Test pickup window that's too short (15 minutes)
        $shortStart = now()->addMinutes(30);
        $shortEnd = now()->addMinutes(45); // Only 15 minutes

        $acceptResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/accept", [
                'pickup_window_start' => $shortStart->toISOString(),
                'pickup_window_end' => $shortEnd->toISOString(),
            ]);

        $acceptResponse->assertStatus(422)
            ->assertJsonValidationErrors(['pickup_window_end']);

        // Test valid minimum pickup window (30 minutes)
        $validStart = now()->addMinutes(30);
        $validEnd = now()->addMinutes(60); // 30 minutes

        $acceptResponse2 = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/accept", [
                'pickup_window_start' => $validStart->toISOString(),
                'pickup_window_end' => $validEnd->toISOString(),
            ]);

        $acceptResponse2->assertStatus(200);
    }

    /** @test */
    public function pickup_window_maximum_duration_validation()
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

        // Test pickup window that's too long (25 hours)
        $longStart = now()->addMinutes(30);
        $longEnd = now()->addHours(25); // 25 hours

        $acceptResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/accept", [
                'pickup_window_start' => $longStart->toISOString(),
                'pickup_window_end' => $longEnd->toISOString(),
            ]);

        $acceptResponse->assertStatus(422)
            ->assertJsonValidationErrors(['pickup_window_end']);

        // Test valid maximum pickup window (24 hours)
        $validStart = now()->addMinutes(30);
        $validEnd = now()->addHours(24); // 24 hours

        $acceptResponse2 = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/accept", [
                'pickup_window_start' => $validStart->toISOString(),
                'pickup_window_end' => $validEnd->toISOString(),
            ]);

        $acceptResponse2->assertStatus(200);
    }

    /** @test */
    public function pickup_window_with_different_time_formats()
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

        // Test with ISO 8601 format
        $pickupStart = now()->addMinutes(30);
        $pickupEnd = now()->addHours(2);

        $acceptResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/accept", [
                'pickup_window_start' => $pickupStart->toISOString(),
                'pickup_window_end' => $pickupEnd->toISOString(),
            ]);

        $acceptResponse->assertStatus(200);

        $order->refresh();
        $this->assertNotNull($order->pickup_window_start);
        $this->assertNotNull($order->pickup_window_end);
    }

    /** @test */
    public function pickup_window_affects_countdown_display()
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

        // Customer views order with pickup window
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

        // Provider views order with pickup window
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
    public function pickup_window_with_meal_availability_constraints()
    {
        // Create meal that expires in 2 hours
        $expiringMeal = Meal::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'category_id' => $this->category->id,
            'quantity' => 5,
            'current_price' => 8.00,
            'available_until' => now()->addHours(2),
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

        // Provider sets pickup window within meal availability
        $pickupStart = now()->addMinutes(30);
        $pickupEnd = now()->addHours(1); // Within meal availability

        $acceptResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/accept", [
                'pickup_window_start' => $pickupStart->toISOString(),
                'pickup_window_end' => $pickupEnd->toISOString(),
            ]);

        $acceptResponse->assertStatus(200);

        $order->refresh();
        $this->assertEquals(Order::STATUS_ACCEPTED, $order->status);
    }

    /** @test */
    public function pickup_window_modification_not_allowed_after_acceptance()
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

        // Try to modify pickup window after acceptance
        $modifyResponse = $this->actingAs($this->provider)
            ->postJson("/api/provider/orders/{$order->id}/accept", [
                'pickup_window_start' => now()->addMinutes(60)->toISOString(),
                'pickup_window_end' => now()->addHours(3)->toISOString(),
            ]);

        $modifyResponse->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function pickup_window_unauthorized_access()
    {
        $anotherProvider = User::factory()->create();
        $providerRole = \App\Models\Role::where('name', 'provider')->first();
        $anotherProvider->roles()->attach($providerRole->id);

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

        // Try to accept order from different provider
        $unauthorizedResponse = $this->actingAs($anotherProvider)
            ->postJson("/api/provider/orders/{$order->id}/accept", [
                'pickup_window_start' => now()->addMinutes(30)->toISOString(),
                'pickup_window_end' => now()->addHours(2)->toISOString(),
            ]);

        $unauthorizedResponse->assertStatus(403);
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
