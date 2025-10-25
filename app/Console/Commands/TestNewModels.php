<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant\Supplier;
use App\Models\Tenant\Category;
use App\Models\Tenant\TaxClass;
use App\Models\Tenant\PurchaseOrder;
use App\Models\Tenant\Refund;
use App\Models\Tenant\Payment;

class TestNewModels extends Command
{
    protected $signature = 'test:new-models';
    protected $description = 'Test the new tenant models';

    public function handle()
    {
        $this->info('Testing new tenant models...');

        try {
            // Test Category
            $category = Category::firstOrCreate(['name' => 'Electronics', 'slug' => 'electronics'], [
                'description' => 'Electronic products and accessories',
                'sort_order' => 1,
            ]);
            $this->info("✅ Category created/found: {$category->name}");

            // Test TaxClass
            $taxClass = TaxClass::firstOrCreate(['code' => 'STANDARD'], [
                'name' => 'Standard Tax',
                'tax_rate' => 8.25,
                'description' => 'Standard sales tax rate',
            ]);
            $this->info("✅ Tax Class created/found: {$taxClass->name} ({$taxClass->formatted_rate})");

            // Test Supplier
            $supplier = Supplier::firstOrCreate(['code' => 'SUPP001'], [
                'name' => 'Tech Supplies Inc',
                'company_name' => 'Tech Supplies Incorporated',
                'email' => 'orders@techsupplies.com',
                'phone' => '+1-555-0123',
                'payment_terms' => 'net_30',
                'credit_limit' => 50000.00,
            ]);
            $this->info("✅ Supplier created/found: {$supplier->display_name}");

            // Test relationships
            $this->info("\n🔗 Testing relationships...");
            
            $categoryProducts = $category->products()->count();
            $this->info("✅ Category products relationship: {$categoryProducts} products");

            $taxClassProducts = $taxClass->products()->count();
            $this->info("✅ Tax class products relationship: {$taxClassProducts} products");

            $supplierPOs = $supplier->purchaseOrders()->count();
            $this->info("✅ Supplier purchase orders relationship: {$supplierPOs} orders");

            $this->info("\n✅ All new models tested successfully!");

        } catch (\Exception $e) {
            $this->error("❌ Error testing models: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}