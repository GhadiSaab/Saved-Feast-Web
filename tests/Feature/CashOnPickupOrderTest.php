<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Meal;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\RestaurantInvoice;
use App\Models\User;
use App\Services\CommissionService;
use App\Services\InvoiceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CashOnPickupOrderTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Restaurant $restaurant;
    protected Meal $meal;
    protected CommissionService $commissionService;
    protected InvoiceService $invoiceService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->user = User::factory()->create();
        $this->restaurant = Restaurant::factory()->create([
            'commission_rate' => 7.0,
        ]);
        
        $category = Category::factory()->create();
        $this->meal = Meal::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'category_id' => $category->id,
            'current_price' => 15.00,
            'quantity' => 10,
        ]);

        $this->commissionService = app(CommissionService::class);
        $this->invoiceService = app(InvoiceService::class);
    }

    public function test_user_can_create_cash_on_pickup_order()
    {
        $orderData = [
            'items' => [
                [
                    'meal_id' => $this->meal->id,
                    'quantity' => 2,
                ],
            ],
            'payment_method' => 'CASH_ON_PICKUP',
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
                    'payment_method',
                    'commission_rate',
                    'commission_amount',
                ],
            ]);

        $this->assertDatabaseHas('orders', [
            'user_id' => $this->user->id,
            'status' => 'pending',
            'payment_method' => 'CASH_ON_PICKUP',
            'commission_rate' => 7.0,
        ]);

        // Verify commission was calculated correctly
        $order = Order::latest()->first();
        $expectedCommission = $this->commissionService->calculateCommission(30.00, 7.0);
        $this->assertEquals($expectedCommission, $order->commission_amount);
    }

    public function test_order_defaults_to_cash_on_pickup_payment_method()
    {
        $orderData = [
            'items' => [
                [
                    'meal_id' => $this->meal->id,
                    'quantity' => 1,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/orders', $orderData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('orders', [
            'user_id' => $this->user->id,
            'payment_method' => 'CASH_ON_PICKUP',
        ]);
    }

    public function test_completing_order_sets_completed_at_timestamp()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending',
            'payment_method' => 'CASH_ON_PICKUP',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/orders/{$order->id}/complete");

        $response->assertStatus(200);

        $order->refresh();
        $this->assertEquals('completed', $order->status);
        $this->assertNotNull($order->completed_at);
    }

    public function test_commission_service_calculates_correctly()
    {
        $total = 100.00;
        $rate = 7.0;
        $expectedCommission = 7.00;

        $commission = $this->commissionService->calculateCommission($total, $rate);
        $this->assertEquals($expectedCommission, $commission);
    }

    public function test_commission_service_uses_restaurant_rate()
    {
        $restaurant = Restaurant::factory()->create(['commission_rate' => 5.0]);
        $rate = $this->commissionService->getCommissionRate($restaurant);
        
        $this->assertEquals(5.0, $rate);
    }

    public function test_commission_service_falls_back_to_default_rate()
    {
        $rate = $this->commissionService->getCommissionRate(null);
        $defaultRate = config('savedfeast.commission.default_rate', 7.0);
        
        $this->assertEquals($defaultRate, $rate);
    }

    public function test_invoice_service_generates_weekly_invoices()
    {
        // Create completed cash-on-pickup orders for last week
        $lastWeek = Carbon::now()->subWeek();
        $periodStart = $lastWeek->copy()->startOfWeek();
        $periodEnd = $lastWeek->copy()->endOfWeek();
        
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'completed',
            'payment_method' => 'CASH_ON_PICKUP',
            'completed_at' => $periodStart->copy()->addDays(2), // Ensure it's within the period
            'commission_rate' => 7.0,
            'commission_amount' => 2.10,
            'total_amount' => 30.00,
        ]);

        // Create order item
        $order->orderItems()->create([
            'meal_id' => $this->meal->id,
            'quantity' => 2,
            'price' => 15.00,
        ]);

        $results = $this->invoiceService->generateWeeklyInvoices($periodStart, $periodEnd);

        $this->assertEquals(1, $results['invoices_created']);
        $this->assertEquals(1, $results['orders_processed']);

        // Verify invoice was created
        $this->assertDatabaseHas('restaurant_invoices', [
            'restaurant_id' => $this->restaurant->id,
            'status' => 'draft',
            'orders_count' => 1,
            'commission_total' => 2.10,
        ]);

        // Verify order was marked as invoiced
        $order->refresh();
        $this->assertNotNull($order->invoiced_at);
    }

    public function test_invoice_service_skips_already_invoiced_orders()
    {
        $lastWeek = Carbon::now()->subWeek();
        $periodStart = $lastWeek->copy()->startOfWeek();
        $periodEnd = $lastWeek->copy()->endOfWeek();
        
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'completed',
            'payment_method' => 'CASH_ON_PICKUP',
            'completed_at' => $periodStart->copy()->addDays(2), // Ensure it's within the period
            'invoiced_at' => now(), // Already invoiced
        ]);

        // Create order item
        $order->orderItems()->create([
            'meal_id' => $this->meal->id,
            'quantity' => 2,
            'price' => 15.00,
        ]);

        $results = $this->invoiceService->generateWeeklyInvoices($periodStart, $periodEnd);

        $this->assertEquals(0, $results['invoices_created']);
        $this->assertEquals(0, $results['orders_processed']);
    }

    public function test_invoice_status_transitions()
    {
        $invoice = RestaurantInvoice::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'status' => 'draft',
        ]);

        // Test draft -> sent
        $this->invoiceService->markInvoiceSent($invoice->id);
        $invoice->refresh();
        $this->assertEquals('sent', $invoice->status);

        // Test sent -> paid
        $this->invoiceService->markInvoicePaid($invoice->id);
        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
    }

    public function test_invalid_invoice_status_transitions()
    {
        $invoice = RestaurantInvoice::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'status' => 'paid',
        ]);

        // Should not be able to mark paid invoice as sent
        $this->expectException(\Exception::class);
        $this->invoiceService->markInvoiceSent($invoice->id);
    }

    public function test_provider_can_view_their_settlements_summary()
    {
        // Create provider role and user
        $providerRole = \App\Models\Role::firstOrCreate(['name' => 'provider']);
        $provider = User::factory()->create();
        $provider->roles()->attach($providerRole->id);
        
        $restaurant = Restaurant::factory()->create([
            'user_id' => $provider->id,
        ]);

        // Create some invoices
        RestaurantInvoice::factory()->create([
            'restaurant_id' => $restaurant->id,
            'status' => 'sent',
            'commission_total' => 50.00,
        ]);

        $response = $this->actingAs($provider)
            ->getJson('/api/provider/settlements/summary');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'amount_owed',
                    'last_invoice_status',
                    'next_invoice_date',
                    'restaurant_id',
                    'restaurant_name',
                ],
            ]);
    }

    public function test_provider_cannot_view_other_restaurants_invoices()
    {
        // Create provider role and user
        $providerRole = \App\Models\Role::firstOrCreate(['name' => 'provider']);
        $provider = User::factory()->create();
        $provider->roles()->attach($providerRole->id);
        
        $otherRestaurant = Restaurant::factory()->create();
        $invoice = RestaurantInvoice::factory()->create([
            'restaurant_id' => $otherRestaurant->id,
        ]);

        $response = $this->actingAs($provider)
            ->getJson("/api/provider/settlements/invoices/{$invoice->id}");

        $response->assertStatus(404);
    }

    public function test_admin_can_generate_weekly_invoices()
    {
        // Create admin role and user
        $adminRole = \App\Models\Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole->id);

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/settlements/generate?period=weekly');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'invoices_created',
                    'orders_processed',
                    'errors',
                ],
            ]);
    }

    public function test_admin_can_mark_invoice_as_paid()
    {
        // Create admin role and user
        $adminRole = \App\Models\Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole->id);
        
        $invoice = RestaurantInvoice::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'status' => 'sent',
        ]);

        $response = $this->actingAs($admin)
            ->postJson("/api/admin/settlements/invoices/{$invoice->id}/mark-paid");

        $response->assertStatus(200);

        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
    }
}
