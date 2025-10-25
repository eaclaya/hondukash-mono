<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoicePayment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'invoice_id',
        'payment_id',
        'amount_allocated',
        'allocation_date',
        'notes',
    ];

    protected $casts = [
        'amount_allocated' => 'decimal:2',
        'allocation_date' => 'date',
    ];

    // Relationships
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    // Business logic methods
    public static function allocatePaymentToInvoice(
        string $invoiceId,
        string $paymentId,
        float $amount,
        string $allocationDate = null
    ): self {
        $invoice = Invoice::findOrFail($invoiceId);
        $payment = Payment::findOrFail($paymentId);
        
        // Validate allocation amount
        $remainingInvoiceAmount = $invoice->getRemainingBalance();
        $remainingPaymentAmount = $payment->getRemainingAmount();
        
        $allocatedAmount = min($amount, $remainingInvoiceAmount, $remainingPaymentAmount);
        
        return self::create([
            'invoice_id' => $invoiceId,
            'payment_id' => $paymentId,
            'amount_allocated' => $allocatedAmount,
            'allocation_date' => $allocationDate ?: now()->toDateString(),
            'notes' => "Payment allocation: {$payment->payment_number} to Invoice {$invoice->invoice_number}",
        ]);
    }

    public function canBeReversed(): bool
    {
        // Can reverse if payment hasn't been finalized/reconciled
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
        });

        static::created(function ($allocation) {
            // Update invoice status if fully paid
            $allocation->invoice->updatePaymentStatus();
        });

        static::deleted(function ($allocation) {
            // Update invoice status when allocation is removed
            $allocation->invoice->updatePaymentStatus();
        });
    }
}