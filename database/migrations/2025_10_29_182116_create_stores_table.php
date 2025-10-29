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
        Schema::create('stores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('code')->unique(); // Short code for the store (e.g., "MAIN", "BRANCH1")
            $table->text('description')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('postal_code')->nullable();
            $table->decimal('tax_rate', 5, 4)->default(0.0000); // Default tax rate for the store
            $table->string('currency', 3)->default('HNL'); // Currency code (ISO 4217)
            $table->string('timezone')->default('America/Tegucigalpa');
            $table->json('business_hours')->nullable(); // JSON structure for operating hours
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false); // Only one store can be default
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};