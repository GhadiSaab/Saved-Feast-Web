<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Meal;
use App\Models\Restaurant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase; // Added this import

class RestaurantTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->provider = User::factory()->create();

        // Create provider role and assign it to the provider user
        $providerRole = Role::firstOrCreate(['name' => 'provider']);
        $this->provider->roles()->attach($providerRole->id);

        $this->category = Category::factory()->create();
    }

    public function test_can_get_restaurants_list()
    {
        Restaurant::factory()->count(3)->create();

        $response = $this->getJson('/api/restaurants');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'address',
                        'cuisine_type',
                        'average_rating',
                        'is_active',
                    ],
                ],
            ]);
    }

    public function test_can_get_restaurant_details()
    {
        $restaurant = Restaurant::factory()->create();

        $response = $this->getJson("/api/restaurants/{$restaurant->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'name',
                    'description',
                    'address',
                    'phone',
                    'email',
                    'cuisine_type',
                    'delivery_radius',
                    'average_rating',
                    'is_active',
                    'meals',
                ],
            ]);
    }

    public function test_can_filter_restaurants_by_cuisine()
    {
        Restaurant::factory()->create(['cuisine_type' => 'Italian']);
        Restaurant::factory()->create(['cuisine_type' => 'Mexican']);

        $response = $this->getJson('/api/restaurants?cuisine=Italian');

        $response->assertStatus(200);
        $this->assertEquals(1, count($response->json('data')));
    }

    public function test_can_search_restaurants()
    {
        Restaurant::factory()->create(['name' => 'Pizza Palace']);
        Restaurant::factory()->create(['name' => 'Burger Joint']);

        $response = $this->getJson('/api/restaurants?search=pizza');

        $response->assertStatus(200);
        $this->assertEquals(1, count($response->json('data')));
        $this->assertEquals('Pizza Palace', $response->json('data.0.name'));
    }

    public function test_provider_can_create_restaurant()
    {
        $restaurantData = [
            'name' => 'New Restaurant',
            'description' => 'A great new restaurant',
            'address' => '123 New St',
            'phone' => '+1234567890',
            'email' => 'new@restaurant.com',
            'cuisine_type' => 'Italian',
            'delivery_radius' => 5.0,
        ];

        $response = $this->actingAs($this->provider)
            ->postJson('/api/restaurants', $restaurantData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'name',
                    'description',
                    'address',
                    'user_id',
                ],
            ]);

        $this->assertDatabaseHas('restaurants', [
            'name' => 'New Restaurant',
            'user_id' => $this->provider->id,
        ]);
    }

    public function test_provider_can_update_their_restaurant()
    {
        $restaurant = Restaurant::factory()->create([
            'user_id' => $this->provider->id,
        ]);

        $updateData = [
            'name' => 'Updated Restaurant Name',
            'description' => 'Updated description',
        ];

        $response = $this->actingAs($this->provider)
            ->putJson("/api/restaurants/{$restaurant->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'Restaurant updated successfully',
            ]);

        $this->assertDatabaseHas('restaurants', [
            'id' => $restaurant->id,
            'name' => 'Updated Restaurant Name',
        ]);
    }

    public function test_provider_cannot_update_other_restaurant()
    {
        $otherProvider = User::factory()->create();
        $restaurant = Restaurant::factory()->create([
            'user_id' => $otherProvider->id,
        ]);

        $updateData = [
            'name' => 'Updated Restaurant Name',
        ];

        $response = $this->actingAs($this->provider)
            ->putJson("/api/restaurants/{$restaurant->id}", $updateData);

        $response->assertStatus(403);
    }

    public function test_can_get_restaurant_meals()
    {
        $restaurant = Restaurant::factory()->create();
        $meals = Meal::factory()->count(3)->create([
            'restaurant_id' => $restaurant->id,
        ]);

        $response = $this->getJson("/api/restaurants/{$restaurant->id}/meals");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'current_price',
                        'is_available',
                    ],
                ],
            ]);

        $this->assertEquals(3, count($response->json('data')));
    }

    public function test_restaurant_requires_valid_data()
    {
        $invalidData = [
            'name' => '', // Empty name
            'email' => 'invalid-email', // Invalid email
        ];

        $response = $this->actingAs($this->provider)
            ->postJson('/api/restaurants', $invalidData);

        $response->assertStatus(422);
    }

    public function test_can_get_restaurant_ratings()
    {
        $restaurant = Restaurant::factory()->create();

        $response = $this->getJson("/api/restaurants/{$restaurant->id}/ratings");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'average_rating',
                    'total_reviews',
                    'ratings_breakdown',
                ],
            ]);
    }
}
