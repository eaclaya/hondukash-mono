<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Refund extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'refund_number',
        'invoice_id',
        'status',
        'type',
        'refund_date',
        'subtotal',
        'tax_amount',
        'total',
        'refund_method',
        'reference_number',
        'reason',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
        'processed_at',
    ];

    protected $casts = [
        'refund_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'approved_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    // Relationships
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RefundItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payment(): HasMany
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
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    public function scopeByInvoice($query, $invoiceId)
    {
        return $query->where('invoice_id', $invoiceId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Business logic methods
    public function calculateTotals(): void
    {
        $this->subtotal = $this->items->sum('total') - $this->items->sum('tax_amount');
        $this->tax_amount = $this->items->sum('tax_amount');
        $this->total = $this->subtotal + $this->tax_amount;
        $this->save();
    }

    public function approve(User $user): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        $this->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        return true;
    }

    public function process(string $refundMethod = null, string $referenceNumber = null): bool
    {
        if ($this->status !== 'approved') {
            return false;
        }

        $updateData = [
            'status' => 'processed',
            'processed_at' => now(),
        ];

        if ($refundMethod) {
            $updateData['refund_method'] = $refundMethod;
        }

        if ($referenceNumber) {
            $updateData['reference_number'] = $referenceNumber;
        }

        $this->update($updateData);

        // Create payment record
        Payment::create([
            'payment_number' => 'REF-' . $this->refund_number,
            'type' => 'refund',
            'payable_id' => $this->id,
            'payable_type' => self::class,
            'method' => $this->refund_method,
            'amount' => $this->total,
            'payment_date' => $this->refund_date,
            'reference_number' => $this->reference_number,
            'notes' => "Refund for invoice #{$this->invoice->invoice_number}",
            'processed_by' => auth()->id(),
        ]);

        // Update inventory
        foreach ($this->items as $item) {
            InventoryMovement::create([
                'product_id' => $item->product_id,
                'store_id' => $this->invoice->store_id,
                'type' => 'refund',
                'quantity' => $item->quantity_refunded,
                'unit_cost' => $item->unit_price,
                'reference_type' => 'refund',
                'reference_id' => $this->id,
                'notes' => "Refund return for #{$this->refund_number}",
            ]);

            // Add back to inventory
            $item->product->addInventoryToStore($this->invoice->store_id, $item->quantity_refunded);
        }

        return true;
    }

    public function reject(string $reason = null): bool
    {
        if (!in_array($this->status, ['pending', 'approved'])) {
            return false;
        }

        $this->update([
            'status' => 'rejected',
            'notes' => $this->notes . ($reason ? " | Rejection reason: {$reason}" : ''),
        ]);

        return true;
    }

    public function cancel(): bool
    {
        if ($this->status === 'processed') {
            return false; // Can't cancel processed refunds
        }

        $this->update(['status' => 'cancelled']);
        return true;
    }

    public function getIsEditableAttribute(): bool
    {
        return $this->status === 'pending';
    }

    public function getIsCancellableAttribute(): bool
    {
        return !in_array($this->status, ['processed', 'cancelled']);
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'yellow',
            'approved' => 'blue',
            'processed' => 'green',
            'rejected' => 'red',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }

    public function getFormattedRefundMethodAttribute(): string
    {
        return match($this->refund_method) {
            'credit_card' => 'Credit Card',
            'bank_transfer' => 'Bank Transfer',
            'store_credit' => 'Store Credit',
            default => ucfirst(str_replace('_', ' ', $this->refund_method ?? '')),
        };
    }
}