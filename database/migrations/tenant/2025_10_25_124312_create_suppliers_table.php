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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('tax_id', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->jsonb('address')->nullable();
            $table->string('payment_terms', 50)->default('net_30'); // net_30, net_60, due_on_receipt, etc.
            $table->decimal('credit_limit', 10, 2)->nullable();
            $table->decimal('balance', 10, 2)->default(0);
            $table->jsonb('bank_details')->nullable(); // Bank account info for payments
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['code', 'is_active']);
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
