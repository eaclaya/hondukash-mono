<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'payment_number',
        'type',
        'payable_id',
        'payable_type',
        'method',
        'amount',
        'payment_date',
        'reference_number',
        'payment_details',
        'notes',
        'processed_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'payment_details' => 'array',
    ];

    // Relationships
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('method', $method);
    }

    public function scopeInvoicePayments($query)
    {
        return $query->where('type', 'invoice_payment');
    }

    public function scopeRefunds($query)
    {
        return $query->where('type', 'refund');
    }

    public function scopePurchasePayments($query)
    {
        return $query->where('type', 'purchase_payment');
    }

    public function scopeExpensePayments($query)
    {
        return $query->where('type', 'expense_payment');
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }

    // Business logic methods
    public function getFormattedMethodAttribute(): string
    {
        return match($this->method) {
            'credit_card' => 'Credit Card',
            'debit_card' => 'Debit Card',
            'bank_transfer' => 'Bank Transfer',
            'store_credit' => 'Store Credit',
            default => ucfirst(str_replace('_', ' ', $this->method)),
        };
    }

    public function getFormattedTypeAttribute(): string
    {
        return match($this->type) {
            'invoice_payment' => 'Invoice Payment',
            'purchase_payment' => 'Purchase Payment',
            'expense_payment' => 'Expense Payment',
            default => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }

    public function getPayableDescriptionAttribute(): string
    {
        if (!$this->payable) {
            return 'Unknown';
        }

        return match($this->payable_type) {
            Invoice::class => "Invoice #{$this->payable->invoice_number}",
            Refund::class => "Refund #{$this->payable->refund_number}",
            PurchaseOrder::class => "Purchase Order #{$this->payable->po_number}",
            Expense::class => "Expense #{$this->payable->expense_number}",
            default => class_basename($this->payable_type) . " #{$this->payable->id}",
        };
    }

    public function getCardDetailsAttribute(): ?array
    {
        if (!in_array($this->method, ['credit_card', 'debit_card'])) {
            return null;
        }

        return [
            'last_four' => $this->payment_details['last_four'] ?? null,
            'brand' => $this->payment_details['brand'] ?? null,
            'exp_month' => $this->payment_details['exp_month'] ?? null,
            'exp_year' => $this->payment_details['exp_year'] ?? null,
        ];
    }

    public function getMaskedCardNumberAttribute(): ?string
    {
        $cardDetails = $this->card_details;
        
        if (!$cardDetails || !$cardDetails['last_four']) {
            return null;
        }

        return "**** **** **** {$cardDetails['last_four']}";
    }

    public function getBankDetailsAttribute(): ?array
    {
        if ($this->method !== 'bank_transfer') {
            return null;
        }

        return [
            'account_name' => $this->payment_details['account_name'] ?? null,
            'account_number' => $this->payment_details['account_number'] ?? null,
            'routing_number' => $this->payment_details['routing_number'] ?? null,
            'bank_name' => $this->payment_details['bank_name'] ?? null,
        ];
    }

    public function getCheckDetailsAttribute(): ?array
    {
        if ($this->method !== 'check') {
            return null;
        }

        return [
            'check_number' => $this->payment_details['check_number'] ?? $this->reference_number,
            'bank_name' => $this->payment_details['bank_name'] ?? null,
            'memo' => $this->payment_details['memo'] ?? null,
        ];
    }

    public function reverse(string $reason = null): Payment
    {
        $reversalAmount = $this->type === 'refund' ? -$this->amount : $this->amount;
        
        return self::create([
            'payment_number' => 'REV-' . $this->payment_number,
            'type' => $this->type,
            'payable_id' => $this->payable_id,
            'payable_type' => $this->payable_type,
            'method' => $this->method,
            'amount' => $reversalAmount,
            'payment_date' => now()->toDateString(),
            'reference_number' => 'REVERSAL-' . $this->reference_number,
            'notes' => "Reversal of payment #{$this->payment_number}" . ($reason ? " - {$reason}" : ''),
            'processed_by' => auth()->id(),
        ]);
    }

    public static function generatePaymentNumber(string $type): string
    {
        $prefix = match($type) {
            'invoice_payment' => 'PAY',
            'refund' => 'REF',
            'purchase_payment' => 'PUR',
            'expense_payment' => 'EXP',
            default => 'PAY',
        };

        $date = now()->format('Ymd');
        $sequence = self::where('type', $type)
            ->whereDate('created_at', now())
            ->count() + 1;

        return "{$prefix}-{$date}-" . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Relationship: Invoice payment allocations
     */
    public function invoicePayments()
    {
        return $this->hasMany(InvoicePayment::class);
    }

    /**
     * Relationship: Supplier payment allocations
     */
    public function supplierPayments()
    {
        return $this->hasMany(SupplierPayment::class);
    }

    /**
     * Get total allocated amount
     */
    public function getTotalAllocatedAttribute(): float
    {
        $invoiceAllocations = $this->invoicePayments()->sum('amount_allocated');
        $supplierAllocations = $this->supplierPayments()->sum('amount_allocated');
        
        return $invoiceAllocations + $supplierAllocations;
    }

    /**
     * Get remaining unallocated amount
     */
    public function getRemainingAmount(): float
    {
        return max(0, $this->amount - $this->total_allocated);
    }

    /**
     * Check if payment is fully allocated
     */
    public function isFullyAllocated(): bool
    {
        return $this->getRemainingAmount() <= 0.01;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (empty($payment->payment_number)) {
                $payment->payment_number = self::generatePaymentNumber($payment->type);
            }
        });
    }
}