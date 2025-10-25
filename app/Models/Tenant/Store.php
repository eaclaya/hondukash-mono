<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'code',
        'type',
        'address',
        'is_active',
        'settings',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'address' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Relationship: Products in this store (inventory)
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_store')
            ->withPivot(['quantity', 'reserved_quantity', 'custom_price', 'custom_cost', 'min_stock_level', 'max_stock_level'])
            ->withTimestamps();
    }

    /**
     * Relationship: Inventory movements for this store
     */
    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    /**
     * Relationship: Invoices issued from this store
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Scope: Only active stores
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Only warehouses
     */
    public function scopeWarehouses($query)
    {
        return $query->where('type', 'warehouse');
    }

    /**
     * Scope: Only stores (not warehouses)
     */
    public function scopeStores($query)
    {
        return $query->where('type', 'store');
    }

    /**
     * Get inventory for a specific product
     */
    public function getProductInventory(string $productId)
    {
        return $this->products()->where('product_id', $productId)->first();
    }

    /**
     * Get all low stock items for this store
     */
    public function getLowStockItems()
    {
        return $this->products()
            ->wherePivot('quantity', '<=', \DB::raw('product_store.min_stock_level'))
            ->whereNotNull('product_store.min_stock_level')
            ->get();
    }

    /**
     * Get total inventory value for this store
     */
    public function getTotalInventoryValue()
    {
        return $this->products()
            ->selectRaw('SUM(
                product_store.quantity * 
                COALESCE(product_store.custom_cost, products.base_cost)
            ) as total_value')
            ->first()
            ->total_value ?? 0;
    }
}