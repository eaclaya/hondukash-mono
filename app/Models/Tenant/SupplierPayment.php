<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SupplierPayment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'supplier_id',
        'payment_id',
        'purchase_order_id',
        'expense_id',
        'amount_allocated',
        'allocation_date',
        'notes',
    ];

    protected $casts = [
        'amount_allocated' => 'decimal:2',
        'allocation_date' => 'date',
    ];

    // Relationships
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    // Business logic methods
    public static function allocatePaymentToPurchaseOrder(
        string $supplierId,
        string $paymentId,
        string $purchaseOrderId,
        float $amount,
        string $allocationDate = null
    ): self {
        $supplier = Supplier::findOrFail($supplierId);
        $payment = Payment::findOrFail($paymentId);
        $purchaseOrder = PurchaseOrder::findOrFail($purchaseOrderId);
        
        // Validate allocation amount
        $remainingPOAmount = $purchaseOrder->getRemainingBalance();
        $remainingPaymentAmount = $payment->getRemainingAmount();
        
        $allocatedAmount = min($amount, $remainingPOAmount, $remainingPaymentAmount);
        
        return self::create([
            'supplier_id' => $supplierId,
            'payment_id' => $paymentId,
            'purchase_order_id' => $purchaseOrderId,
            'amount_allocated' => $allocatedAmount,
            'allocation_date' => $allocationDate ?: now()->toDateString(),
            'notes' => "Payment allocation: {$payment->payment_number} to PO {$purchaseOrder->po_number}",
        ]);
    }

    public static function allocatePaymentToExpense(
        string $supplierId,
        string $paymentId,
        string $expenseId,
        float $amount,
        string $allocationDate = null
    ): self {
        $supplier = Supplier::findOrFail($supplierId);
        $payment = Payment::findOrFail($paymentId);
        $expense = Expense::findOrFail($expenseId);
        
        // Validate allocation amount
        $remainingExpenseAmount = $expense->getRemainingBalance();
        $remainingPaymentAmount = $payment->getRemainingAmount();
        
        $allocatedAmount = min($amount, $remainingExpenseAmount, $remainingPaymentAmount);
        
        return self::create([
            'supplier_id' => $supplierId,
            'payment_id' => $paymentId,
            'expense_id' => $expenseId,
            'amount_allocated' => $allocatedAmount,
            'allocation_date' => $allocationDate ?: now()->toDateString(),
            'notes' => "Payment allocation: {$payment->payment_number} to Expense {$expense->expense_number}",
        ]);
    }

    public function getAllocatedEntityAttribute()
    {
        if ($this->purchase_order_id) {
            return $this->purchaseOrder;
        }
        
        if ($this->expense_id) {
            return $this->expense;
        }
        
        return null;
    }

    public function getAllocatedEntityTypeAttribute(): string
    {
        if ($this->purchase_order_id) {
            return 'purchase_order';
        }
        
        if ($this->expense_id) {
            return 'expense';
        }
        
        return 'unknown';
    }

    public function getAllocatedEntityDescriptionAttribute(): string
    {
        if ($this->purchase_order_id && $this->purchaseOrder) {
            return "PO #{$this->purchaseOrder->po_number}";
        }
        
        if ($this->expense_id && $this->expense) {
            return "Expense #{$this->expense->expense_number}";
        }
        
        return 'Unknown allocation';
    }

    public function canBeReversed(): bool
    {
        return $this->payment->status !== 'finalized';
    }

    public function reverse(string $reason = null): bool
    {
        if (!$this->canBeReversed()) {
            return false;
        }

        $this->update([
            'notes' => $this->notes . " | REVERSED: " . ($reason ?: 'Manual reversal'),
        ]);

        $this->delete();
        return true;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($allocation) {
            // Ensure allocation date defaults to today
            if (!$allocation->allocation_date) {
                $allocation->allocation_date = now()->toDateString();
            }
            
            // Validate that either purchase_order_id or expense_id is set
            if (!$allocation->purchase_order_id && !$allocation->expense_id) {
                throw new \InvalidArgumentException('Either purchase_order_id or expense_id must be specified');
            }
        });

        static::created(function ($allocation) {
            // Update related entity payment status
            if ($allocation->purchase_order_id) {
                $allocation->purchaseOrder->updatePaymentStatus();
            }
            
            if ($allocation->expense_id) {
                $allocation->expense->updatePaymentStatus();
            }
        });

        static::deleted(function ($allocation) {
            // Update related entity payment status when allocation is removed
            if ($allocation->purchase_order_id && $allocation->purchaseOrder) {
                $allocation->purchaseOrder->updatePaymentStatus();
            }
            
            if ($allocation->expense_id && $allocation->expense) {
                $allocation->expense->updatePaymentStatus();
            }
        });
    }
}