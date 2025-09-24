<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Meal;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Create necessary data for tests
        $this->category = Category::factory()->create();
        $this->restaurant = Restaurant::factory()->create();
        $this->meal = Meal::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'category_id' => $this->category->id,
            'quantity' => 10,
        ]);
        $this->user = User::factory()->create();
    }

    public function test_user_can_create_order()
    {
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

        $response = $this->actingAs($this->user)
            ->postJson('/api/orders', $orderData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'user_id',
                    'status',
                    'total_amount',
                ],
            ]);

        $this->assertDatabaseHas('orders', [
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);
    }

    public function test_user_can_view_their_orders()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'status',
                        'total_amount',
                        'created_at',
                    ],
                ],
            ]);
    }

    public function test_user_can_view_specific_order()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'user_id',
                    'status',
                    'total_amount',
                ],
            ]);
    }

    public function test_user_cannot_view_other_users_order()
    {
        $otherUser = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/orders/{$order->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_cancel_their_order()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'PENDING',
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/orders/{$order->id}/cancel", [
                'reason' => 'Changed my mind',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Order cancelled successfully',
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'CANCELLED_BY_CUSTOMER',
        ]);
    }

    public function test_user_cannot_cancel_completed_order()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'COMPLETED',
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/orders/{$order->id}/cancel", [
                'reason' => 'Changed my mind',
            ]);

        $response->assertStatus(422);
    }

    public function test_order_requires_valid_meal()
    {
        $orderData = [
            'items' => [
                [
                    'meal_id' => 99999, // Non-existent meal
                    'quantity' => 2,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/orders', $orderData);

        $response->assertStatus(422);
    }

    public function test_order_requires_minimum_quantity()
    {
        $orderData = [
            'items' => [
                [
                    'meal_id' => $this->meal->id,
                    'quantity' => 0, // Invalid quantity
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/orders', $orderData);

        $response->assertStatus(422);
    }
}
