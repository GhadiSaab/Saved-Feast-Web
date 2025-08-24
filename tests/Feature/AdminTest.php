<?php

namespace Tests\Feature;

use App\Models\Meal;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin role and user
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole->id);

        // Create regular user
        $this->user = User::factory()->create();

        // Create some test data
        $this->restaurant = Restaurant::factory()->create();
        $this->meal = Meal::factory()->create(['restaurant_id' => $this->restaurant->id]);
        $this->order = Order::factory()->create();
    }

    public function test_admin_can_access_dashboard()
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'overview' => [
                    'users',
                    'orders',
                    'revenue',
                    'restaurants',
                    'meals',
                    'reviews',
                ],
                'recent_activity',
                'analytics',
            ]);
    }

    public function test_regular_user_cannot_access_admin_dashboard()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/dashboard');

        $response->assertStatus(403);
    }

    public function test_admin_can_view_all_users()
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                        'roles',
                        'created_at',
                    ],
                ],
            ]);
    }

    public function test_admin_can_view_user_details()
    {
        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/users/{$this->user->id}");

        $response->assertStatus(200);
    }

    public function test_admin_can_update_user_role()
    {
        $providerRole = Role::firstOrCreate(['name' => 'provider']);

        $updateData = [
            'roles' => [$providerRole->id],
        ];

        $response = $this->actingAs($this->admin)
            ->putJson("/api/admin/users/{$this->user->id}/roles", $updateData);

        $response->assertStatus(200);

        $this->assertTrue($this->user->fresh()->hasRole('provider'));
    }

    public function test_admin_can_view_all_restaurants()
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/restaurants');

        $response->assertStatus(200);
    }

    public function test_admin_can_approve_restaurant()
    {
        $restaurant = Restaurant::factory()->create([
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->putJson("/api/admin/restaurants/{$restaurant->id}/approve");

        $response->assertStatus(200);

        $this->assertTrue($restaurant->fresh()->is_active);
    }

    public function test_admin_can_reject_restaurant()
    {
        $restaurant = Restaurant::factory()->create([
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->putJson("/api/admin/restaurants/{$restaurant->id}/reject");

        $response->assertStatus(200);

        $this->assertFalse($restaurant->fresh()->is_active);
    }

    public function test_admin_can_view_all_orders()
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/orders');

        $response->assertStatus(200);
    }

    public function test_admin_can_update_order_status()
    {
        $updateData = [
            'status' => 'completed',
        ];

        $response = $this->actingAs($this->admin)
            ->putJson("/api/admin/orders/{$this->order->id}", $updateData);

        $response->assertStatus(200);

        $this->assertEquals('completed', $this->order->fresh()->status);
    }

    public function test_admin_can_view_analytics()
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/analytics');

        $response->assertStatus(200);
    }

    public function test_admin_can_export_data()
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/export/users');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_admin_can_manage_system_settings()
    {
        $settingsData = [
            'delivery_fee' => 5.00,
            'tax_rate' => 8.5,
            'min_order_amount' => 10.00,
        ];

        $response = $this->actingAs($this->admin)
            ->putJson('/api/admin/settings', $settingsData);

        $response->assertStatus(200);
    }
}
