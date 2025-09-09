<?php

namespace Tests\Feature;

use App\Models\Restaurant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AdminProviderCreationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $this->adminRole = Role::firstOrCreate(['name' => 'admin']);
        $this->providerRole = Role::firstOrCreate(['name' => 'provider']);
        $this->customerRole = Role::firstOrCreate(['name' => 'customer']);

        // Create admin user
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($this->adminRole->id);
    }

    public function test_admin_can_create_provider_profile()
    {
        $providerData = [
            // User data
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone' => '+1234567890',
            'address' => '123 Main St, City, State',
            'password' => 'password123',
            'password_confirmation' => 'password123',

            // Restaurant data
            'restaurant_name' => 'John\'s Restaurant',
            'restaurant_description' => 'A great restaurant',
            'restaurant_address' => '456 Restaurant Ave, City, State',
            'restaurant_phone' => '+1234567891',
            'restaurant_email' => 'restaurant@example.com',
            'restaurant_website' => 'https://johnsrestaurant.com',
            'cuisine_type' => 'Italian',
            'delivery_radius' => 10.0,
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/providers', $providerData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                        'roles',
                    ],
                    'restaurant' => [
                        'id',
                        'name',
                        'description',
                        'address',
                        'email',
                        'is_active',
                    ],
                ],
            ]);

        // Verify user was created
        $user = User::where('email', 'john.doe@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('John', $user->first_name);
        $this->assertEquals('Doe', $user->last_name);
        $this->assertTrue($user->hasRole('provider'));

        // Verify restaurant was created
        $restaurant = Restaurant::where('user_id', $user->id)->first();
        $this->assertNotNull($restaurant);
        $this->assertEquals('John\'s Restaurant', $restaurant->name);
        $this->assertEquals('restaurant@example.com', $restaurant->email);
        $this->assertTrue($restaurant->is_active);
    }

    public function test_admin_can_create_user_with_specific_role()
    {
        $userData = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com',
            'phone' => '+1234567892',
            'address' => '789 User St, City, State',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $this->customerRole->id,
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/users', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                    'roles',
                ],
            ]);

        // Verify user was created with correct role
        $user = User::where('email', 'jane.smith@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Jane', $user->first_name);
        $this->assertEquals('Smith', $user->last_name);
        $this->assertTrue($user->hasRole('customer'));
    }

    public function test_admin_can_get_available_roles()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/roles');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                    ],
                ],
            ]);

        $roles = $response->json('data');
        $roleNames = collect($roles)->pluck('name')->toArray();

        $this->assertContains('admin', $roleNames);
        $this->assertContains('provider', $roleNames);
        $this->assertContains('customer', $roleNames);
    }

    public function test_provider_creation_validates_required_fields()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/providers', []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'status',
                'message',
                'errors',
            ]);

        $errors = $response->json('errors');
        $this->assertArrayHasKey('first_name', $errors);
        $this->assertArrayHasKey('last_name', $errors);
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('restaurant_name', $errors);
        $this->assertArrayHasKey('restaurant_email', $errors);
    }

    public function test_provider_creation_validates_unique_emails()
    {
        // Create existing user
        User::factory()->create(['email' => 'existing@example.com']);

        $providerData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'existing@example.com', // Duplicate email
            'phone' => '+1234567890',
            'address' => '123 Main St',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'restaurant_name' => 'Test Restaurant',
            'restaurant_description' => 'Test description',
            'restaurant_address' => '456 Restaurant Ave',
            'restaurant_phone' => '+1234567891',
            'restaurant_email' => 'restaurant@example.com',
            'cuisine_type' => 'Italian',
            'delivery_radius' => 5.0,
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/providers', $providerData);

        $response->assertStatus(422);
        $this->assertArrayHasKey('email', $response->json('errors'));
    }

    public function test_provider_creation_validates_unique_restaurant_emails()
    {
        // Create existing restaurant
        Restaurant::factory()->create(['email' => 'existing@restaurant.com']);

        $providerData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'address' => '123 Main St',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'restaurant_name' => 'Test Restaurant',
            'restaurant_description' => 'Test description',
            'restaurant_address' => '456 Restaurant Ave',
            'restaurant_phone' => '+1234567891',
            'restaurant_email' => 'existing@restaurant.com', // Duplicate restaurant email
            'cuisine_type' => 'Italian',
            'delivery_radius' => 5.0,
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/providers', $providerData);

        $response->assertStatus(422);
        $this->assertArrayHasKey('restaurant_email', $response->json('errors'));
    }

    public function test_provider_creation_validates_password_confirmation()
    {
        $providerData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'address' => '123 Main St',
            'password' => 'password123',
            'password_confirmation' => 'differentpassword', // Mismatched password
            'restaurant_name' => 'Test Restaurant',
            'restaurant_description' => 'Test description',
            'restaurant_address' => '456 Restaurant Ave',
            'restaurant_phone' => '+1234567891',
            'restaurant_email' => 'restaurant@example.com',
            'cuisine_type' => 'Italian',
            'delivery_radius' => 5.0,
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/providers', $providerData);

        $response->assertStatus(422);
        $this->assertArrayHasKey('password', $response->json('errors'));
    }

    public function test_provider_creation_sets_default_delivery_radius()
    {
        $providerData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'address' => '123 Main St',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'restaurant_name' => 'Test Restaurant',
            'restaurant_description' => 'Test description',
            'restaurant_address' => '456 Restaurant Ave',
            'restaurant_phone' => '+1234567891',
            'restaurant_email' => 'restaurant@example.com',
            'cuisine_type' => 'Italian',
            // Not providing delivery_radius
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/providers', $providerData);

        $response->assertStatus(201);

        $user = User::where('email', 'john@example.com')->first();
        $restaurant = Restaurant::where('user_id', $user->id)->first();

        $this->assertEquals(5.0, $restaurant->delivery_radius);
    }

    public function test_non_admin_cannot_create_providers()
    {
        $customer = User::factory()->create();
        $customer->roles()->attach($this->customerRole->id);

        $providerData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'address' => '123 Main St',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'restaurant_name' => 'Test Restaurant',
            'restaurant_description' => 'Test description',
            'restaurant_address' => '456 Restaurant Ave',
            'restaurant_phone' => '+1234567891',
            'restaurant_email' => 'restaurant@example.com',
            'cuisine_type' => 'Italian',
            'delivery_radius' => 5.0,
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/admin/providers', $providerData);

        $response->assertStatus(403);
    }
}
