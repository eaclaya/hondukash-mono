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
        Schema::create('refunds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('refund_number', 50)->unique();
            $table->uuid('invoice_id');
            $table->enum('status', ['pending', 'approved', 'processed', 'rejected', 'cancelled'])->default('pending');
            $table->enum('type', ['full', 'partial'])->default('partial');
            $table->date('refund_date');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax_amount', 10, 2);
            $table->decimal('total', 10, 2);
            $table->enum('refund_method', ['cash', 'credit_card', 'bank_transfer', 'store_credit', 'check'])->nullable();
            $table->string('reference_number')->nullable(); // Transaction reference for payment gateway
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('created_by');
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            
            $table->index(['refund_number']);
            $table->index(['invoice_id', 'status']);
            $table->index(['status', 'refund_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
