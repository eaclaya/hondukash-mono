<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ProductStore extends Pivot
{
    use HasUuids;

    /**
     * The table associated with the model.
     */
    protected $table = 'product_store';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The data type of the auto-incrementing ID.
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'product_id',
        'store_id',
        'quantity',
        'reserved_quantity',
        'custom_price',
        'custom_cost',
        'min_stock_level',
        'max_stock_level',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'quantity' => 'decimal:2',
        'reserved_quantity' => 'decimal:2',
        'custom_price' => 'decimal:2',
        'custom_cost' => 'decimal:2',
        'min_stock_level' => 'decimal:2',
        'max_stock_level' => 'decimal:2',
    ];

    /**
     * Relationship: Product
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relationship: Store
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get available quantity (quantity - reserved)
     */
    public function getAvailableQuantityAttribute(): float
    {
        return $this->quantity - $this->reserved_quantity;
    }

    /**
     * Get effective price (custom price or product base price)
     */
    public function getEffectivePriceAttribute(): float
    {
        return $this->custom_price ?? $this->product->base_price;
    }

    /**
     * Get effective cost (custom cost or product base cost)
     */
    public function getEffectiveCostAttribute(): float
    {
        return $this->custom_cost ?? $this->product->base_cost;
    }

    /**
     * Check if stock is low
     */
    public function getIsLowStockAttribute(): bool
    {
        if (!$this->min_stock_level) {
            return false;
        }

        return $this->quantity <= $this->min_stock_level;
    }

    /**
     * Check if stock is over maximum
     */
    public function getIsOverStockAttribute(): bool
    {
        if (!$this->max_stock_level) {
            return false;
        }

        return $this->quantity >= $this->max_stock_level;
    }

    /**
     * Get total inventory value (quantity * cost)
     */
    public function getInventoryValueAttribute(): float
    {
        return $this->quantity * $this->effective_cost;
    }

    /**
     * Reserve stock
     */
    public function reserveStock(float $quantity): bool
    {
        if ($this->available_quantity < $quantity) {
            return false;
        }

        $this->increment('reserved_quantity', $quantity);
        return true;
    }

    /**
     * Release reserved stock
     */
    public function releaseStock(float $quantity): bool
    {
        if ($this->reserved_quantity < $quantity) {
            return false;
        }

        $this->decrement('reserved_quantity', $quantity);
        return true;
    }

    /**
     * Adjust stock quantity
     */
    public function adjustStock(float $adjustment): void
    {
        $this->increment('quantity', $adjustment);
    }
}