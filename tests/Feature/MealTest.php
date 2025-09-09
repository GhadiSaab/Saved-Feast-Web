<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Meal;
use App\Models\Restaurant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class MealTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Create necessary data for tests
        $this->category = Category::factory()->create();
        $this->restaurant = Restaurant::factory()->create();

        // Create roles
        $this->providerRole = Role::firstOrCreate(['name' => 'provider']);
        $this->adminRole = Role::firstOrCreate(['name' => 'admin']);
    }

    public function test_can_get_meals_list()
    {
        // Create some meals
        Meal::factory()->count(3)->create([
            'restaurant_id' => $this->restaurant->id,
        ]);

        $response = $this->getJson('/api/meals');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data',
                'pagination' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                    'from',
                    'to',
                    'has_more_pages',
                ],
                'filters_applied',
            ]);
    }

    public function test_can_filter_meals_by_restaurant()
    {
        $restaurant2 = Restaurant::factory()->create();

        // Create meals in different restaurants
        Meal::factory()->create([
            'restaurant_id' => $this->restaurant->id,
        ]);

        Meal::factory()->create([
            'restaurant_id' => $restaurant2->id,
        ]);

        $response = $this->getJson('/api/meals?restaurant_id='.$this->restaurant->id);

        $response->assertStatus(200);
        $this->assertEquals(1, count($response->json('data')));
    }

    public function test_can_get_meal_filters()
    {
        $response = $this->getJson('/api/meals/filters');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'categories',
                    'price_range',
                    'sort_options',
                    'sort_orders',
                ],
            ]);
    }

    public function test_can_search_meals()
    {
        Meal::factory()->create([
            'title' => 'Special Pizza',
            'restaurant_id' => $this->restaurant->id,
        ]);

        Meal::factory()->create([
            'title' => 'Regular Burger',
            'restaurant_id' => $this->restaurant->id,
        ]);

        $response = $this->getJson('/api/meals?search=pizza');

        $response->assertStatus(200);
        $this->assertEquals(1, count($response->json('data')));
        $this->assertEquals('Special Pizza', $response->json('data.0.title'));
    }

    public function test_can_search_meals_by_restaurant_name()
    {
        $restaurant = Restaurant::factory()->create(['name' => 'Mario\'s Pizza']);

        Meal::factory()->create([
            'title' => 'Margherita Pizza',
            'restaurant_id' => $restaurant->id,
        ]);

        $response = $this->getJson('/api/meals?search=mario');

        $response->assertStatus(200);
        $this->assertEquals(1, count($response->json('data')));
        $this->assertEquals('Margherita Pizza', $response->json('data.0.title'));
    }

    public function test_can_search_meals_by_category_name()
    {
        $category = Category::factory()->create(['name' => 'Italian Cuisine']);

        Meal::factory()->create([
            'title' => 'Pasta Carbonara',
            'category_id' => $category->id,
            'restaurant_id' => $this->restaurant->id,
        ]);

        $response = $this->getJson('/api/meals?search=italian');

        $response->assertStatus(200);
        $this->assertEquals(1, count($response->json('data')));
        $this->assertEquals('Pasta Carbonara', $response->json('data.0.title'));
    }

    public function test_can_filter_available_meals()
    {
        // Create available meal
        Meal::factory()->create([
            'title' => 'Available Meal',
            'restaurant_id' => $this->restaurant->id,
            'status' => 'available',
            'quantity' => 5,
            'available_from' => now()->subHour(),
            'available_until' => now()->addDays(7),
        ]);

        // Create expired meal
        Meal::factory()->create([
            'title' => 'Expired Meal',
            'restaurant_id' => $this->restaurant->id,
            'status' => 'expired',
            'quantity' => 0,
            'available_from' => now()->subDays(2),
            'available_until' => now()->subDay(),
        ]);

        $response = $this->getJson('/api/meals?available=true');

        $response->assertStatus(200);
        $this->assertEquals(1, count($response->json('data')));
        $this->assertEquals('Available Meal', $response->json('data.0.title'));
    }

    public function test_can_filter_unavailable_meals()
    {
        // Create available meal
        Meal::factory()->create([
            'title' => 'Available Meal',
            'restaurant_id' => $this->restaurant->id,
            'status' => 'available',
            'quantity' => 5,
            'available_from' => now()->subHour(),
            'available_until' => now()->addDays(7),
        ]);

        // Create expired meal
        Meal::factory()->create([
            'title' => 'Expired Meal',
            'restaurant_id' => $this->restaurant->id,
            'status' => 'expired',
            'quantity' => 0,
            'available_from' => now()->subDays(2),
            'available_until' => now()->subDay(),
        ]);

        $response = $this->getJson('/api/meals?available=false');

        $response->assertStatus(200);
        $this->assertEquals(1, count($response->json('data')));
        $this->assertEquals('Expired Meal', $response->json('data.0.title'));
    }

    public function test_meal_creation_sets_correct_status()
    {
        // Create a provider user
        $provider = User::factory()->create();
        $provider->roles()->attach($this->providerRole->id);

        $restaurant = Restaurant::factory()->create(['user_id' => $provider->id]);

        $mealData = [
            'title' => 'Test Meal',
            'description' => 'A test meal',
            'current_price' => 15.99,
            'quantity' => 10,
            'category_id' => $this->category->id,
            'available_from' => now()->format('Y-m-d H:i:s'),
            'available_until' => now()->addDays(7)->format('Y-m-d H:i:s'),
        ];

        $response = $this->actingAs($provider, 'sanctum')
            ->postJson('/api/provider/meals', $mealData);

        // Debug the response if it fails
        if ($response->status() !== 201) {
            dump('Response status: '.$response->status());
            dump('Response content: '.$response->getContent());
        }

        $response->assertStatus(201);

        $meal = Meal::where('title', 'Test Meal')->first();
        $this->assertNotNull($meal);
        $this->assertEquals('available', $meal->status);
        $this->assertEquals($restaurant->id, $meal->restaurant_id);
    }

    public function test_meal_creation_sets_default_availability_times()
    {
        // Create a provider user
        $provider = User::factory()->create();
        $provider->roles()->attach($this->providerRole->id);

        $restaurant = Restaurant::factory()->create(['user_id' => $provider->id]);

        $mealData = [
            'title' => 'Test Meal with Default Times',
            'description' => 'A test meal with default availability',
            'current_price' => 12.99,
            'quantity' => 5,
            'category_id' => $this->category->id,
            // Not providing available_from and available_until
        ];

        $response = $this->actingAs($provider, 'sanctum')
            ->postJson('/api/provider/meals', $mealData);

        // Debug the response if it fails
        if ($response->status() !== 201) {
            dump('Response status: '.$response->status());
            dump('Response content: '.$response->getContent());
        }

        $response->assertStatus(201);

        $meal = Meal::where('title', 'Test Meal with Default Times')->first();
        $this->assertNotNull($meal);
        $this->assertEquals('available', $meal->status);

        // Check that default times are set
        $this->assertNotNull($meal->available_from);
        $this->assertNotNull($meal->available_until);

        // available_from should be now or in the past
        $this->assertTrue($meal->available_from <= now());

        // available_until should be 7 days from now
        $this->assertTrue($meal->available_until >= now()->addDays(6));
        $this->assertTrue($meal->available_until <= now()->addDays(8));
    }

    public function test_meal_search_combines_multiple_filters()
    {
        $restaurant = Restaurant::factory()->create(['name' => 'Pizza Palace']);
        $category = Category::factory()->create(['name' => 'Italian']);

        // Create meal that matches all criteria
        Meal::factory()->create([
            'title' => 'Margherita Pizza',
            'restaurant_id' => $restaurant->id,
            'category_id' => $category->id,
            'current_price' => 15.99,
            'status' => 'available',
            'quantity' => 5,
            'available_from' => now()->subHour(),
            'available_until' => now()->addDays(7),
        ]);

        // Create meal that doesn't match price filter
        Meal::factory()->create([
            'title' => 'Expensive Pizza',
            'restaurant_id' => $restaurant->id,
            'category_id' => $category->id,
            'current_price' => 25.99,
            'status' => 'available',
            'quantity' => 3,
            'available_from' => now()->subHour(),
            'available_until' => now()->addDays(7),
        ]);

        $response = $this->getJson('/api/meals?search=pizza&min_price=10&max_price=20&available=true');

        $response->assertStatus(200);
        $this->assertEquals(1, count($response->json('data')));
        $this->assertEquals('Margherita Pizza', $response->json('data.0.title'));
    }

    public function test_meal_search_handles_empty_results()
    {
        $response = $this->getJson('/api/meals?search=nonexistent');

        $response->assertStatus(200);
        $this->assertEquals(0, count($response->json('data')));
        $this->assertEquals(0, $response->json('pagination.total'));
    }

    public function test_meal_search_is_case_insensitive()
    {
        Meal::factory()->create([
            'title' => 'Special Pizza',
            'restaurant_id' => $this->restaurant->id,
        ]);

        $response = $this->getJson('/api/meals?search=PIZZA');

        $response->assertStatus(200);
        $this->assertEquals(1, count($response->json('data')));
        $this->assertEquals('Special Pizza', $response->json('data.0.title'));
    }

    public function test_meal_search_by_description()
    {
        Meal::factory()->create([
            'title' => 'Delicious Meal',
            'description' => 'This is a delicious pizza with fresh ingredients',
            'restaurant_id' => $this->restaurant->id,
        ]);

        $response = $this->getJson('/api/meals?search=fresh ingredients');

        $response->assertStatus(200);
        $this->assertEquals(1, count($response->json('data')));
        $this->assertEquals('Delicious Meal', $response->json('data.0.title'));
    }
}
