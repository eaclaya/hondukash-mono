<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'product_id',
        'store_id',
        'movement_type',
        'quantity',
        'reference_type',
        'reference_id',
        'notes',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    /**
     * Movement types
     */
    const TYPE_IN = 'in';
    const TYPE_OUT = 'out';
    const TYPE_TRANSFER = 'transfer';
    const TYPE_ADJUSTMENT = 'adjustment';

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
     * Relationship: User who created this movement
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope: Filter by movement type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('movement_type', $type);
    }

    /**
     * Scope: Filter by product
     */
    public function scopeByProduct($query, string $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope: Filter by store
     */
    public function scopeByStore($query, string $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    /**
     * Scope: Filter by date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope: Filter by reference
     */
    public function scopeByReference($query, string $referenceType, string $referenceId)
    {
        return $query->where('reference_type', $referenceType)
                    ->where('reference_id', $referenceId);
    }

    /**
     * Get the human-readable movement type
     */
    public function getMovementTypeNameAttribute(): string
    {
        return match($this->movement_type) {
            self::TYPE_IN => 'Stock In',
            self::TYPE_OUT => 'Stock Out',
            self::TYPE_TRANSFER => 'Transfer',
            self::TYPE_ADJUSTMENT => 'Adjustment',
            default => ucfirst($this->movement_type),
        };
    }

    /**
     * Check if this is an inbound movement
     */
    public function getIsInboundAttribute(): bool
    {
        return in_array($this->movement_type, [self::TYPE_IN, self::TYPE_ADJUSTMENT]) 
               && $this->quantity > 0;
    }

    /**
     * Check if this is an outbound movement
     */
    public function getIsOutboundAttribute(): bool
    {
        return in_array($this->movement_type, [self::TYPE_OUT, self::TYPE_ADJUSTMENT]) 
               && $this->quantity < 0;
    }

    /**
     * Get the signed quantity (negative for outbound movements)
     */
    public function getSignedQuantityAttribute(): float
    {
        if ($this->movement_type === self::TYPE_OUT) {
            return -$this->quantity;
        }
        
        return $this->quantity;
    }

    /**
     * Get related movements (for transfers)
     */
    public function getRelatedMovements()
    {
        if ($this->movement_type !== self::TYPE_TRANSFER || !$this->reference_id) {
            return collect();
        }

        return static::where('reference_type', 'transfer')
            ->where('reference_id', $this->reference_id)
            ->where('id', '!=', $this->id)
            ->get();
    }
}