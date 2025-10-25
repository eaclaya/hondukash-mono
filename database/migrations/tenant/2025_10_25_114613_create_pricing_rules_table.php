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
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('rule_type', ['discount_percent', 'discount_fixed', 'special_price']);
            $table->decimal('value', 10, 2);
            $table->jsonb('conditions'); // Complex conditions structure
            $table->integer('priority')->default(0);
            $table->boolean('is_stackable')->default(false);
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['is_active', 'priority']);
            $table->index(['rule_type', 'is_active']);
            $table->index(['valid_from', 'valid_to', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};
