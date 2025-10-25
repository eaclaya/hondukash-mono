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
        Schema::create('tax_classes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('code', 20)->unique();
            $table->text('description')->nullable();
            $table->decimal('tax_rate', 5, 4); // 0.0825 for 8.25%
            $table->enum('calculation_type', ['percentage', 'fixed_amount'])->default('percentage');
            $table->boolean('compound', false)->default(false); // For tax-on-tax scenarios
            $table->jsonb('rules')->default('{}'); // Complex tax rules, exemptions, etc.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['code', 'is_active']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_classes');
    }
};
