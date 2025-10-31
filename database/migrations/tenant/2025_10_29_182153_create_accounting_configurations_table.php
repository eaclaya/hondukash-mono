<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('accounting_configurations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('company_name');
            $table->string('company_registration')->nullable(); // RTN, Tax ID, etc.
            $table->string('legal_form')->nullable(); // S.A., S.R.L., etc.
            $table->text('company_address')->nullable();
            $table->string('fiscal_year_start', 5)->default('01-01'); // MM-DD format
            $table->string('accounting_method')->default('accrual'); // accrual, cash
            $table->string('base_currency', 3)->default('HNL'); // ISO 4217
            $table->boolean('multi_currency_enabled')->default(false);
            $table->json('enabled_currencies')->nullable(); // Array of enabled currency codes
            $table->decimal('tax_rate', 5, 4)->default(0.1500); // Default VAT/ISV rate (15%)
            $table->string('tax_number')->nullable(); // VAT/ISV number
            $table->boolean('tax_inclusive_pricing')->default(true); // Prices include tax
            $table->json('chart_of_accounts')->nullable(); // Basic chart of accounts structure
            $table->json('account_numbering_scheme')->nullable(); // How accounts are numbered
            $table->boolean('use_departments')->default(false);
            $table->boolean('use_cost_centers')->default(false);
            $table->boolean('use_projects')->default(false);
            $table->string('invoice_numbering_pattern')->default('INV-{YYYY}-{####}');
            $table->string('receipt_numbering_pattern')->default('REC-{YYYY}-{####}');
            $table->integer('next_invoice_number')->default(1);
            $table->integer('next_receipt_number')->default(1);
            $table->json('backup_settings')->nullable(); // Backup frequency and retention
            $table->json('integration_settings')->nullable(); // Third-party integrations
            $table->boolean('is_configured')->default(false); // Wizard completion status
            $table->timestamp('configured_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_configurations');
    }
};