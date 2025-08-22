<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Http\Controllers\API\AdminController;

class TestDashboardAPI extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:test-api';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test dashboard API response';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $this->info("=== Testing Dashboard API Response ===\n");

            // Create a mock request
            $request = new \Illuminate\Http\Request();
            
            // Create the admin controller
            $controller = new AdminController();
            
            // Call the dashboard method
            $response = $controller->dashboard();
            
            // Get the response data
            $data = $response->getData(true);
            
            $this->info("API Response Structure:");
            $this->info("Keys: " . implode(', ', array_keys($data)));
            
            if (isset($data['overview'])) {
                $this->info("\nOverview Keys: " . implode(', ', array_keys($data['overview'])));
                
                if (isset($data['overview']['users'])) {
                    $this->info("Users Keys: " . implode(', ', array_keys($data['overview']['users'])));
                    $this->info("Total Users: " . $data['overview']['users']['total']);
                    $this->info("New This Month: " . $data['overview']['users']['new_this_month']);
                    $this->info("Active Users: " . $data['overview']['users']['active']);
                    
                    if (isset($data['overview']['users']['role_distribution'])) {
                        $this->info("\nRole Distribution:");
                        foreach ($data['overview']['users']['role_distribution'] as $role) {
                            $this->info("- {$role['name']}: {$role['count']}");
                        }
                    }
                }
                
                if (isset($data['overview']['orders'])) {
                    $this->info("\nOrders:");
                    $this->info("Total Orders: " . $data['overview']['orders']['total']);
                    $this->info("This Month: " . $data['overview']['orders']['this_month']);
                }
                
                if (isset($data['overview']['revenue'])) {
                    $this->info("\nRevenue:");
                    $this->info("Total Revenue: €" . $data['overview']['revenue']['total']);
                    $this->info("This Month: €" . $data['overview']['revenue']['this_month']);
                }
                
                if (isset($data['overview']['restaurants'])) {
                    $this->info("\nRestaurants:");
                    $this->info("Total Restaurants: " . $data['overview']['restaurants']['total']);
                    $this->info("Active Restaurants: " . $data['overview']['restaurants']['active']);
                }
                
                if (isset($data['overview']['meals'])) {
                    $this->info("\nMeals:");
                    $this->info("Total Meals: " . $data['overview']['meals']['total']);
                    $this->info("Active Meals: " . $data['overview']['meals']['active']);
                    $this->info("Categories: " . $data['overview']['meals']['categories']);
                }
                
                if (isset($data['overview']['reviews'])) {
                    $this->info("\nReviews:");
                    $this->info("Total Reviews: " . $data['overview']['reviews']['total']);
                    $this->info("Average Rating: " . $data['overview']['reviews']['average_rating']);
                }
            }
            
            $this->info("\n=== Full Response (JSON) ===");
            $this->info(json_encode($data, JSON_PRETTY_PRINT));

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
        }
    }
}
