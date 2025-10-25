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
        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('supplier_id');
            $table->uuid('payment_id');
            $table->uuid('purchase_order_id')->nullable(); // Payment for specific PO
            $table->uuid('expense_id')->nullable(); // Payment for specific expense
            $table->decimal('amount_allocated', 10, 2);
            $table->date('allocation_date');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
            $table->foreign('payment_id')->references('id')->on('payments')->onDelete('cascade');
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('set null');
            $table->foreign('expense_id')->references('id')->on('expenses')->onDelete('set null');
            
            $table->index(['supplier_id']);
            $table->index(['payment_id']);
            $table->index(['purchase_order_id']);
            $table->index(['expense_id']);
            $table->index(['allocation_date']);
            
            // Ensure at least one reference (PO or expense)
            // This will be enforced at application level
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_payments');
    }
};
