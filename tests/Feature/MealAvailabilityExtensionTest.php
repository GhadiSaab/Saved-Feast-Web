<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Meal;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class MealAvailabilityExtensionTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::factory()->create();
        $this->restaurant = Restaurant::factory()->create();
    }

    public function test_meal_availability_extension_command()
    {
        // Create expired meals
        $expiredMeal1 = Meal::factory()->create([
            'title' => 'Expired Meal 1',
            'restaurant_id' => $this->restaurant->id,
            'category_id' => $this->category->id,
            'status' => 'expired',
            'quantity' => 5,
            'available_from' => now()->subDays(3),
            'available_until' => now()->subDays(1), // Expired yesterday
        ]);

        $expiredMeal2 = Meal::factory()->create([
            'title' => 'Expired Meal 2',
            'restaurant_id' => $this->restaurant->id,
            'category_id' => $this->category->id,
            'status' => 'expired',
            'quantity' => 3,
            'available_from' => now()->subDays(2),
            'available_until' => now()->subHours(2), // Expired 2 hours ago
        ]);

        // Create a meal with null availability dates
        $nullAvailabilityMeal = Meal::factory()->create([
            'title' => 'Null Availability Meal',
            'restaurant_id' => $this->restaurant->id,
            'category_id' => $this->category->id,
            'status' => 'available',
            'quantity' => 10,
            'available_from' => null,
            'available_until' => null,
        ]);

        // Create a sold out meal (should not be extended)
        $soldOutMeal = Meal::factory()->create([
            'title' => 'Sold Out Meal',
            'restaurant_id' => $this->restaurant->id,
            'category_id' => $this->category->id,
            'status' => 'sold_out',
            'quantity' => 0,
            'available_from' => now()->subDays(3),
            'available_until' => now()->subDays(1),
        ]);

        // Run the command
        $this->artisan('meals:extend-availability', ['--days' => 30])
            ->expectsOutput('Extending meal availability by 30 days...')
            ->expectsOutput('Found 2 expired meals to extend.')
            ->expectsOutput('Extended availability for: Expired Meal 1 (ID: '.$expiredMeal1->id.')')
            ->expectsOutput('Extended availability for: Expired Meal 2 (ID: '.$expiredMeal2->id.')')
            ->expectsOutput('Successfully extended availability for 2 meals.')
            ->expectsOutput('Found 1 meals with null availability dates.')
            ->expectsOutput('Set availability dates for: Null Availability Meal (ID: '.$nullAvailabilityMeal->id.')')
            ->assertExitCode(0);

        // Refresh models from database
        $expiredMeal1->refresh();
        $expiredMeal2->refresh();
        $nullAvailabilityMeal->refresh();
        $soldOutMeal->refresh();

        // Check that expired meals were extended
        $this->assertEquals('available', $expiredMeal1->status);
        $this->assertEquals('available', $expiredMeal2->status);

        // Check that available_until was extended by 30 days
        $this->assertTrue($expiredMeal1->available_until >= now()->addDays(29));
        $this->assertTrue($expiredMeal1->available_until <= now()->addDays(31));

        $this->assertTrue($expiredMeal2->available_until >= now()->addDays(29));
        $this->assertTrue($expiredMeal2->available_until <= now()->addDays(31));

        // Check that null availability meal got dates set
        $this->assertNotNull($nullAvailabilityMeal->available_from);
        $this->assertNotNull($nullAvailabilityMeal->available_until);
        $this->assertTrue($nullAvailabilityMeal->available_from <= now());
        $this->assertTrue($nullAvailabilityMeal->available_until >= now()->addDays(29));

        // Check that sold out meal was not changed
        $this->assertEquals('sold_out', $soldOutMeal->status);
        $this->assertEquals(0, $soldOutMeal->quantity);
    }

    public function test_meal_availability_extension_with_custom_days()
    {
        $expiredMeal = Meal::factory()->create([
            'title' => 'Expired Meal',
            'restaurant_id' => $this->restaurant->id,
            'category_id' => $this->category->id,
            'status' => 'expired',
            'quantity' => 5,
            'available_from' => now()->subDays(3),
            'available_until' => now()->subDays(1),
        ]);

        // Run the command with 7 days
        $this->artisan('meals:extend-availability', ['--days' => 7])
            ->assertExitCode(0);

        $expiredMeal->refresh();

        // Check that available_until was extended by 7 days
        $this->assertTrue($expiredMeal->available_until >= now()->addDays(6));
        $this->assertTrue($expiredMeal->available_until <= now()->addDays(8));
    }

    public function test_meal_availability_extension_with_no_expired_meals()
    {
        // Create only available meals
        Meal::factory()->create([
            'title' => 'Available Meal',
            'restaurant_id' => $this->restaurant->id,
            'category_id' => $this->category->id,
            'status' => 'available',
            'quantity' => 5,
            'available_from' => now()->subHour(),
            'available_until' => now()->addDays(7),
        ]);

        $this->artisan('meals:extend-availability', ['--days' => 30])
            ->expectsOutput('Extending meal availability by 30 days...')
            ->expectsOutput('Found 0 expired meals to extend.')
            ->expectsOutput('No expired meals found to extend.')
            ->assertExitCode(0);
    }

    public function test_meal_availability_extension_handles_available_from_in_past()
    {
        $expiredMeal = Meal::factory()->create([
            'title' => 'Expired Meal',
            'restaurant_id' => $this->restaurant->id,
            'category_id' => $this->category->id,
            'status' => 'expired',
            'quantity' => 5,
            'available_from' => now()->subDays(5), // Available from 5 days ago
            'available_until' => now()->subDays(1), // Expired yesterday
        ]);

        $this->artisan('meals:extend-availability', ['--days' => 30])
            ->assertExitCode(0);

        $expiredMeal->refresh();

        // available_from should be set to now since it was in the past
        $this->assertTrue($expiredMeal->available_from <= now());
        $this->assertTrue($expiredMeal->available_until >= now()->addDays(29));
    }

    public function test_meal_availability_extension_handles_available_from_in_future()
    {
        $expiredMeal = Meal::factory()->create([
            'title' => 'Expired Meal',
            'restaurant_id' => $this->restaurant->id,
            'category_id' => $this->category->id,
            'status' => 'expired',
            'quantity' => 5,
            'available_from' => now()->addDays(2), // Available from 2 days in future
            'available_until' => now()->subDays(1), // Expired yesterday
        ]);

        $this->artisan('meals:extend-availability', ['--days' => 30])
            ->assertExitCode(0);

        $expiredMeal->refresh();

        // available_from should remain in the future
        $this->assertTrue($expiredMeal->available_from > now());
        $this->assertTrue($expiredMeal->available_until >= now()->addDays(29));
    }
}
