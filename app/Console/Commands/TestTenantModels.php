<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant\{User, Store, Product, Client, Account};

class TestTenantModels extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'test:tenant-models';

    /**
     * The console command description.
     */
    protected $description = 'Test tenant models by creating sample data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing tenant models...');

        try {
            // Create a test user
            $user = User::firstOrCreate(
                ['email' => 'test@tenant.com'],
                [
                    'name' => 'Test User',
                    'password_hash' => bcrypt('password'),
                    'role' => 'admin',
                    'permissions' => ['*']
                ]
            );
            $this->line("✓ Found/Created user: {$user->name} ({$user->id})");

            // Create a test store
            $store = Store::firstOrCreate(
                ['code' => 'MAIN'],
                [
                    'name' => 'Main Store',
                    'type' => 'store',
                    'address' => [
                        'street' => '123 Main St',
                        'city' => 'Test City',
                        'country' => 'Honduras'
                    ]
                ]
            );
            $this->line("✓ Found/Created store: {$store->name} ({$store->id})");

            // Create a test product
            $product = Product::firstOrCreate(
                ['sku' => 'PROD-001'],
                [
                    'name' => 'Test Product',
                    'description' => 'A test product',
                    'base_price' => 100.00,
                    'base_cost' => 60.00,
                    'unit_of_measure' => 'pcs'
                ]
            );
            $this->line("✓ Found/Created product: {$product->name} ({$product->id})");

            // Create a test client
            $client = Client::firstOrCreate(
                ['code' => 'CLIENT-001'],
                [
                    'name' => 'Test Client',
                    'type' => 'company',
                    'email' => 'client@test.com',
                    'tags' => ['vip', 'corporate']
                ]
            );
            $this->line("✓ Found/Created client: {$client->name} ({$client->id})");

            // Create test accounts
            $assetAccount = Account::firstOrCreate(
                ['code' => '1000'],
                [
                    'name' => 'Cash',
                    'type' => 'asset'
                ]
            );
            $this->line("✓ Found/Created account: {$assetAccount->name} ({$assetAccount->code})");

            $revenueAccount = Account::firstOrCreate(
                ['code' => '4000'],
                [
                    'name' => 'Sales Revenue',
                    'type' => 'revenue'
                ]
            );
            $this->line("✓ Found/Created account: {$revenueAccount->name} ({$revenueAccount->code})");

            // Test relationships by adding product to store inventory
            if (!$store->products()->where('product_id', $product->id)->exists()) {
                $store->products()->attach($product->id, [
                    'id' => \Illuminate\Support\Str::uuid(),
                    'quantity' => 100,
                    'custom_price' => 95.00,
                    'min_stock_level' => 10
                ]);
                $this->line("✓ Added product to store inventory");
            } else {
                $this->line("✓ Product already in store inventory");
            }

            // Test product-store relationship
            $inventory = $store->products()->where('product_id', $product->id)->first();
            if ($inventory) {
                $this->line("✓ Retrieved inventory: {$inventory->pivot->quantity} units at {$inventory->pivot->custom_price}");
            }

            $this->info('All tenant models tested successfully!');
            
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}