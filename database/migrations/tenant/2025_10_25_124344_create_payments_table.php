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
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('payment_number', 50)->unique();
            $table->enum('type', ['invoice_payment', 'refund', 'purchase_payment', 'expense_payment']);
            $table->uuid('payable_id'); // Polymorphic - can be invoice, purchase_order, expense, etc.
            $table->string('payable_type'); // Model class name
            $table->enum('method', ['cash', 'credit_card', 'debit_card', 'bank_transfer', 'check', 'store_credit', 'other']);
            $table->decimal('amount', 10, 2);
            $table->date('payment_date');
            $table->string('reference_number')->nullable(); // Check number, transaction ID, etc.
            $table->jsonb('payment_details')->nullable(); // Card last 4 digits, bank info, etc.
            $table->text('notes')->nullable();
            $table->uuid('processed_by');
            $table->timestamps();
            
            $table->foreign('processed_by')->references('id')->on('users')->onDelete('cascade');
            
            $table->index(['payable_type', 'payable_id']);
            $table->index(['payment_date', 'method']);
            $table->index('payment_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
