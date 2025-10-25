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
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->enum('cash_flow_category', ['operating', 'investing', 'financing'])->nullable()->after('status');
            $table->boolean('affects_cash')->default(false)->after('cash_flow_category');
            $table->index(['cash_flow_category']);
            $table->index(['affects_cash']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropIndex(['cash_flow_category']);
            $table->dropIndex(['affects_cash']);
            $table->dropColumn(['cash_flow_category', 'affects_cash']);
        });
    }
};
