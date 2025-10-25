<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseOrder extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'po_number',
        'supplier_id',
        'store_id',
        'status',
        'order_date',
        'expected_date',
        'subtotal',
        'tax_amount',
        'shipping_cost',
        'total',
        'notes',
        'shipping_address',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total' => 'decimal:2',
        'shipping_address' => 'array',
        'approved_at' => 'datetime',
    ];

    // Relationships
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'payable_id')->where('payable_type', self::class);
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['draft', 'sent']);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeReceived($query)
    {
        return $query->whereIn('status', ['received', 'partial']);
    }

    public function scopeBySupplier($query, $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeByStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    // Business logic methods
    public function calculateTotals(): void
    {
        $this->subtotal = $this->items->sum('total');
        $this->tax_amount = $this->items->sum('tax_amount');
        $this->total = $this->subtotal + $this->tax_amount + $this->shipping_cost;
        $this->save();
    }

    public function approve(User $user): bool
    {
        if ($this->status !== 'sent') {
            return false;
        }

        $this->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        return true;
    }

    public function markAsReceived(bool $partial = false): void
    {
        $this->update([
            'status' => $partial ? 'partial' : 'received',
        ]);
    }

    public function cancel(): bool
    {
        if (in_array($this->status, ['received', 'partial'])) {
            return false; // Can't cancel received orders
        }

        $this->update(['status' => 'cancelled']);
        return true;
    }

    public function getIsEditableAttribute(): bool
    {
        return in_array($this->status, ['draft']);
    }

    public function getIsCancellableAttribute(): bool
    {
        return !in_array($this->status, ['received', 'partial', 'cancelled']);
    }

    public function getFormattedShippingAddressAttribute(): string
    {
        if (!$this->shipping_address) {
            return '';
        }

        $parts = array_filter([
            $this->shipping_address['street'] ?? '',
            $this->shipping_address['city'] ?? '',
            $this->shipping_address['state'] ?? '',
            $this->shipping_address['postal_code'] ?? '',
            $this->shipping_address['country'] ?? '',
        ]);

        return implode(', ', $parts);
    }

    public function getTotalQuantityOrderedAttribute(): float
    {
        return $this->items->sum('quantity_ordered');
    }

    public function getTotalQuantityReceivedAttribute(): float
    {
        return $this->items->sum('quantity_received');
    }

    public function getReceivingProgressAttribute(): float
    {
        if ($this->total_quantity_ordered == 0) {
            return 0;
        }

        return ($this->total_quantity_received / $this->total_quantity_ordered) * 100;
    }

    /**
     * Relationship: Supplier payments
     */
    public function supplierPayments()
    {
        return $this->hasMany(SupplierPayment::class);
    }

    /**
     * Get remaining balance after payments
     */
    public function getRemainingBalance(): float
    {
        $totalPaid = $this->supplierPayments()->sum('amount_allocated');
        return max(0, $this->total - $totalPaid);
    }

    /**
     * Check if purchase order is fully paid
     */
    public function isFullyPaid(): bool
    {
        return $this->getRemainingBalance() <= 0.01;
    }

    /**
     * Update payment status based on remaining balance
     */
    public function updatePaymentStatus(): void
    {
        // This would typically update a payment_status field if we had one
        // For now, we can add this as an enhancement later
    }
}