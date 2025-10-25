<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password_hash',
        'role',
        'permissions',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'permissions' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the password for the user.
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    /**
     * Get the name of the password column.
     */
    public function getAuthPasswordName()
    {
        return 'password_hash';
    }

    /**
     * Relationship: Inventory movements created by this user
     */
    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class, 'created_by');
    }

    /**
     * Relationship: Invoices created by this user
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'created_by');
    }

    /**
     * Relationship: Journal entries created by this user
     */
    public function journalEntries()
    {
        return $this->hasMany(JournalEntry::class, 'created_by');
    }

    /**
     * Relationship: Expenses created by this user
     */
    public function expensesCreated()
    {
        return $this->hasMany(Expense::class, 'created_by');
    }

    /**
     * Relationship: Expenses approved by this user
     */
    public function expensesApproved()
    {
        return $this->hasMany(Expense::class, 'approved_by');
    }

    /**
     * Check if user has a specific permission
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }

    /**
     * Check if user has any of the given permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        return !empty(array_intersect($permissions, $this->permissions ?? []));
    }

    /**
     * Check if user has all of the given permissions
     */
    public function hasAllPermissions(array $permissions): bool
    {
        return empty(array_diff($permissions, $this->permissions ?? []));
    }
}