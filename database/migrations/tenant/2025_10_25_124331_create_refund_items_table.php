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
        Schema::create('refund_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('refund_id');
            $table->uuid('invoice_item_id');
            $table->uuid('product_id');
            $table->string('description');
            $table->decimal('quantity_refunded', 10, 2);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->text('reason')->nullable();
            $table->timestamps();
            
            $table->foreign('refund_id')->references('id')->on('refunds')->onDelete('cascade');
            $table->foreign('invoice_item_id')->references('id')->on('invoice_items')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            
            $table->index(['refund_id']);
            $table->index(['invoice_item_id']);
            $table->index(['product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refund_items');
    }
};
