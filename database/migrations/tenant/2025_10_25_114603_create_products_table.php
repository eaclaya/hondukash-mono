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
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('sku', 100)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->uuid('category_id')->nullable();
            $table->string('unit_of_measure', 20)->default('pcs');
            $table->decimal('base_price', 10, 2)->default(0);
            $table->decimal('base_cost', 10, 2)->default(0);
            $table->uuid('tax_class_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->jsonb('attributes')->default('{}');
            $table->timestamps();
            
            $table->index(['sku', 'is_active']);
            $table->index('category_id');
            $table->index('tax_class_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
