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
        Schema::table('accounts', function (Blueprint $table) {
            $table->boolean('is_cash_account')->default(false)->after('is_active');
            $table->boolean('is_bank_account')->default(false)->after('is_cash_account');
            $table->index(['is_cash_account']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropIndex(['is_cash_account']);
            $table->dropColumn(['is_cash_account', 'is_bank_account']);
        });
    }
};
