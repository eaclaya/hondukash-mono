<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Database\Models\Domain;
use Stancl\Tenancy\Contracts\TenantWithDatabase;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'id',
        'name',
        'company_name',
        'email',
        'phone',
        'address',
        'country',
        'timezone',
        'currency',
        'language',
        'status',
        'trial_ends_at',
        'plan',
        'data',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'trial_ends_at' => 'datetime',
        'data' => 'array',
    ];

    /**
     * The model's default values for attributes.
     */
    protected $attributes = [
        'status' => 'active',
        'currency' => 'HNL',
        'language' => 'es',
        'timezone' => 'America/Tegucigalpa',
        'plan' => 'basic',
    ];

    /**
     * Get the domains for the tenant.
     */
    public function domains()
    {
        return $this->hasMany(Domain::class, 'tenant_id', 'id');
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tenant) {
            if (empty($tenant->id)) {
                $tenant->id = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }

    /**
     * Define which columns are real database columns (not virtual)
     * This tells the VirtualColumn trait to NOT move these to the data JSON column
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'company_name',
            'email',
            'phone',
            'address',
            'country',
            'timezone',
            'currency',
            'language',
            'status',
            'trial_ends_at',
            'plan',
            'data',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * Get the database configuration for this tenant.
     * Required by TenantWithDatabase contract.
     */
    public function database(): \Stancl\Tenancy\DatabaseConfig
    {
        return new \Stancl\Tenancy\DatabaseConfig($this);
    }
}