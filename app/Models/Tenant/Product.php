<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'sku',
        'name',
        'description',
        'category_id',
        'unit_of_measure',
        'base_price',
        'base_cost',
        'tax_class_id',
        'is_active',
        'attributes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'base_price' => 'decimal:2',
        'base_cost' => 'decimal:2',
        'is_active' => 'boolean',
        'attributes' => 'array',
    ];

    /**
     * Relationship: Stores that have this product (inventory)
     */
    public function stores()
    {
        return $this->belongsToMany(Store::class, 'product_store')
            ->withPivot(['quantity', 'reserved_quantity', 'custom_price', 'custom_cost', 'min_stock_level', 'max_stock_level'])
            ->withTimestamps();
    }

    /**
     * Relationship: Inventory movements for this product
     */
    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    /**
     * Relationship: Invoice items for this product
     */
    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Relationship: Category this product belongs to
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relationship: Tax class for this product
     */
    public function taxClass()
    {
        return $this->belongsTo(TaxClass::class);
    }

    /**
     * Relationship: Purchase order items for this product
     */
    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /**
     * Relationship: Refund items for this product
     */
    public function refundItems()
    {
        return $this->hasMany(RefundItem::class);
    }

    /**
     * Scope: Only active products
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Filter by category
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Get inventory for a specific store
     */
    public function getInventoryForStore(string $storeId)
    {
        return $this->stores()->where('store_id', $storeId)->first();
    }

    /**
     * Get total quantity across all stores
     */
    public function getTotalQuantity()
    {
        return $this->stores()->sum('product_store.quantity');
    }

    /**
     * Get available quantity (total - reserved) across all stores
     */
    public function getAvailableQuantity()
    {
        return $this->stores()
            ->selectRaw('SUM(product_store.quantity - product_store.reserved_quantity) as available')
            ->first()
            ->available ?? 0;
    }

    /**
     * Get the effective price for a specific store
     */
    public function getPriceForStore(string $storeId): float
    {
        $inventory = $this->getInventoryForStore($storeId);
        
        if ($inventory && $inventory->pivot->custom_price) {
            return $inventory->pivot->custom_price;
        }
        
        return $this->base_price;
    }

    /**
     * Get the effective cost for a specific store
     */
    public function getCostForStore(string $storeId): float
    {
        $inventory = $this->getInventoryForStore($storeId);
        
        if ($inventory && $inventory->pivot->custom_cost) {
            return $inventory->pivot->custom_cost;
        }
        
        return $this->base_cost;
    }

    /**
     * Check if product has sufficient stock in a store
     */
    public function hasSufficientStock(string $storeId, float $requiredQuantity): bool
    {
        $inventory = $this->getInventoryForStore($storeId);
        
        if (!$inventory) {
            return false;
        }
        
        $availableQuantity = $inventory->pivot->quantity - $inventory->pivot->reserved_quantity;
        
        return $availableQuantity >= $requiredQuantity;
    }

    /**
     * Get low stock stores for this product
     */
    public function getLowStockStores()
    {
        return $this->stores()
            ->wherePivot('quantity', '<=', \DB::raw('product_store.min_stock_level'))
            ->whereNotNull('product_store.min_stock_level')
            ->get();
    }

    /**
     * Calculate tax amount for this product
     */
    public function calculateTax(float $amount, array $context = []): float
    {
        if (!$this->taxClass) {
            return 0;
        }

        return $this->taxClass->calculateTax($amount, array_merge($context, [
            'product_category' => $this->category?->name,
            'product_id' => $this->id,
        ]));
    }

    /**
     * Get the full category path for this product
     */
    public function getCategoryPathAttribute(): string
    {
        return $this->category?->full_name ?? 'Uncategorized';
    }

    /**
     * Get effective tax rate for this product
     */
    public function getTaxRateAttribute(): float
    {
        return $this->taxClass?->effective_rate ?? 0;
    }

    /**
     * Add inventory to a specific store
     */
    public function addInventoryToStore(string $storeId, float $quantity): void
    {
        $inventory = $this->getInventoryForStore($storeId);
        
        if ($inventory) {
            $this->stores()->updateExistingPivot($storeId, [
                'quantity' => $inventory->pivot->quantity + $quantity,
            ]);
        } else {
            $this->stores()->attach($storeId, [
                'id' => \Illuminate\Support\Str::uuid(),
                'quantity' => $quantity,
                'reserved_quantity' => 0,
            ]);
        }
    }

    /**
     * Remove inventory from a specific store
     */
    public function removeInventoryFromStore(string $storeId, float $quantity): bool
    {
        $inventory = $this->getInventoryForStore($storeId);
        
        if (!$inventory || $inventory->pivot->quantity < $quantity) {
            return false;
        }

        $this->stores()->updateExistingPivot($storeId, [
            'quantity' => $inventory->pivot->quantity - $quantity,
        ]);

        return true;
    }
}