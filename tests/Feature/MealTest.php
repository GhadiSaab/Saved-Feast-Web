<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Meal;
use App\Models\Category;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class MealTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create necessary data for tests
        $this->category = Category::factory()->create();
        $this->restaurant = Restaurant::factory()->create();
    }

    public function test_can_get_meals_list()
    {
        // Create some meals
        Meal::factory()->count(3)->create([
            'restaurant_id' => $this->restaurant->id
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
                        'has_more_pages'
                    ],
                    'filters_applied'
                ]);
    }

    public function test_can_filter_meals_by_restaurant()
    {
        $restaurant2 = Restaurant::factory()->create();
        
        // Create meals in different restaurants
        Meal::factory()->create([
            'restaurant_id' => $this->restaurant->id
        ]);
        
        Meal::factory()->create([
            'restaurant_id' => $restaurant2->id
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
                        'sort_orders'
                    ]
                ]);
    }

    public function test_can_search_meals()
    {
        Meal::factory()->create([
            'title' => 'Special Pizza',
            'restaurant_id' => $this->restaurant->id
        ]);

        Meal::factory()->create([
            'title' => 'Regular Burger',
            'restaurant_id' => $this->restaurant->id
        ]);

        $response = $this->getJson('/api/meals?search=pizza');

        $response->assertStatus(200);
        $this->assertEquals(1, count($response->json('data')));
        $this->assertEquals('Special Pizza', $response->json('data.0.title'));
    }
}
