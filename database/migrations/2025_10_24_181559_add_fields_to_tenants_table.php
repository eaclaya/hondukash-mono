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
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('name')->after('id');
            $table->string('company_name')->after('name');
            $table->string('email')->unique()->after('company_name');
            $table->string('phone')->nullable()->after('email');
            $table->text('address')->nullable()->after('phone');
            $table->string('country')->nullable()->after('address');
            $table->string('timezone')->default('America/Tegucigalpa')->after('country');
            $table->string('currency')->default('HNL')->after('timezone');
            $table->string('language')->default('es')->after('currency');
            $table->string('status')->default('active')->after('language'); // active, suspended, canceled
            $table->timestamp('trial_ends_at')->nullable()->after('status');
            $table->string('plan')->default('basic')->after('trial_ends_at'); // basic, pro, enterprise
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'name', 'company_name', 'email', 'phone', 'address',
                'country', 'timezone', 'currency', 'language', 'status',
                'trial_ends_at', 'plan'
            ]);
        });
    }
};
