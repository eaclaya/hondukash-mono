<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseOrderItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'description',
        'quantity_ordered',
        'quantity_received',
        'unit_cost',
        'tax_amount',
        'total',
    ];

    protected $casts = [
        'quantity_ordered' => 'decimal:2',
        'quantity_received' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    // Relationships
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Business logic methods
    public function calculateTotal(): void
    {
        $this->total = ($this->quantity_ordered * $this->unit_cost) + $this->tax_amount;
        $this->save();
    }

    public function receiveQuantity(float $quantity): bool
    {
        if ($quantity <= 0 || $this->quantity_received + $quantity > $this->quantity_ordered) {
            return false;
        }

        $this->increment('quantity_received', $quantity);

        // Create inventory movement
        InventoryMovement::create([
            'product_id' => $this->product_id,
            'store_id' => $this->purchaseOrder->store_id,
            'type' => 'purchase',
            'quantity' => $quantity,
            'unit_cost' => $this->unit_cost,
            'reference_type' => 'purchase_order',
            'reference_id' => $this->purchase_order_id,
            'notes' => "Received from PO #{$this->purchaseOrder->po_number}",
        ]);

        // Update product inventory in the store
        $this->product->addInventoryToStore($this->purchaseOrder->store_id, $quantity);

        return true;
    }

    public function getRemainingQuantityAttribute(): float
    {
        return $this->quantity_ordered - $this->quantity_received;
    }

    public function getIsFullyReceivedAttribute(): bool
    {
        return $this->quantity_received >= $this->quantity_ordered;
    }

    public function getReceivingProgressAttribute(): float
    {
        if ($this->quantity_ordered == 0) {
            return 0;
        }

        return ($this->quantity_received / $this->quantity_ordered) * 100;
    }

    public function getLineSubtotalAttribute(): float
    {
        return $this->quantity_ordered * $this->unit_cost;
    }

    public function getLineTotalAttribute(): float
    {
        return $this->line_subtotal + $this->tax_amount;
    }
}