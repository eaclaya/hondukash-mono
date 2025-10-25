<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Supplier extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'code',
        'name',
        'company_name',
        'tax_id',
        'email',
        'phone',
        'address',
        'payment_terms',
        'credit_limit',
        'balance',
        'bank_details',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'address' => 'array',
        'bank_details' => 'array',
        'credit_limit' => 'decimal:2',
        'balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'payable_id')->where('payable_type', self::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }

    // Business logic methods
    public function getAvailableCreditAttribute(): float
    {
        if (!$this->credit_limit) {
            return 0;
        }
        
        return max(0, $this->credit_limit - $this->balance);
    }

    public function addToBalance(float $amount): void
    {
        $this->increment('balance', $amount);
    }

    public function subtractFromBalance(float $amount): void
    {
        $this->decrement('balance', $amount);
    }

    public function hasAvailableCredit(float $amount): bool
    {
        if (!$this->credit_limit) {
            return true; // No credit limit means unlimited
        }
        
        return $this->available_credit >= $amount;
    }

    public function getFormattedAddressAttribute(): string
    {
        if (!$this->address) {
            return '';
        }

        $parts = array_filter([
            $this->address['street'] ?? '',
            $this->address['city'] ?? '',
            $this->address['state'] ?? '',
            $this->address['postal_code'] ?? '',
            $this->address['country'] ?? '',
        ]);

        return implode(', ', $parts);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->company_name ?: $this->name;
    }

    /**
     * Get aging report for this supplier
     */
    public function getAgingReport($asOfDate = null): array
    {
        $asOfDate = $asOfDate ?: now()->toDateString();
        
        // Get unpaid purchase orders and expenses
        $purchaseOrders = $this->purchaseOrders()
            ->whereIn('status', ['approved', 'received', 'partial'])
            ->where('order_date', '<=', $asOfDate)
            ->with('supplierPayments')
            ->get();

        $expenses = Expense::where('vendor_name', $this->name)
            ->orWhere('vendor_name', $this->company_name)
            ->whereIn('status', ['pending', 'approved'])
            ->where('expense_date', '<=', $asOfDate)
            ->with('supplierPayments')
            ->get();

        $aging = [
            'current' => 0,
            'days_1_30' => 0,
            'days_31_60' => 0,
            'days_61_90' => 0,
            'days_over_90' => 0,
            'total_outstanding' => 0,
            'details' => []
        ];

        // Process purchase orders
        foreach ($purchaseOrders as $po) {
            $remainingBalance = $po->getRemainingBalance();
            
            if ($remainingBalance <= 0) {
                continue;
            }

            $paymentTermsDays = $this->getPaymentTermsDays();
            $dueDate = $po->order_date->addDays($paymentTermsDays);
            $daysPastDue = max(0, now()->parse($asOfDate)->diffInDays($dueDate, false));
            
            $bucket = match(true) {
                $daysPastDue <= 0 => 'current',
                $daysPastDue <= 30 => 'days_1_30',
                $daysPastDue <= 60 => 'days_31_60',
                $daysPastDue <= 90 => 'days_61_90',
                default => 'days_over_90'
            };

            $aging[$bucket] += $remainingBalance;
            $aging['total_outstanding'] += $remainingBalance;

            $aging['details'][] = [
                'type' => 'purchase_order',
                'id' => $po->id,
                'number' => $po->po_number,
                'date' => $po->order_date,
                'due_date' => $dueDate,
                'total_amount' => $po->total,
                'remaining_balance' => $remainingBalance,
                'days_past_due' => $daysPastDue,
                'aging_bucket' => $bucket
            ];
        }

        // Process expenses
        foreach ($expenses as $expense) {
            $remainingBalance = $expense->getRemainingBalance();
            
            if ($remainingBalance <= 0) {
                continue;
            }

            $paymentTermsDays = $this->getPaymentTermsDays();
            $dueDate = $expense->expense_date->addDays($paymentTermsDays);
            $daysPastDue = max(0, now()->parse($asOfDate)->diffInDays($dueDate, false));
            
            $bucket = match(true) {
                $daysPastDue <= 0 => 'current',
                $daysPastDue <= 30 => 'days_1_30',
                $daysPastDue <= 60 => 'days_31_60',
                $daysPastDue <= 90 => 'days_61_90',
                default => 'days_over_90'
            };

            $aging[$bucket] += $remainingBalance;
            $aging['total_outstanding'] += $remainingBalance;

            $aging['details'][] = [
                'type' => 'expense',
                'id' => $expense->id,
                'number' => $expense->expense_number,
                'date' => $expense->expense_date,
                'due_date' => $dueDate,
                'total_amount' => $expense->amount + $expense->tax_amount,
                'remaining_balance' => $remainingBalance,
                'days_past_due' => $daysPastDue,
                'aging_bucket' => $bucket
            ];
        }

        return $aging;
    }

    /**
     * Get payment terms in days
     */
    public function getPaymentTermsDays(): int
    {
        return match($this->payment_terms) {
            'due_on_receipt' => 0,
            'net_15' => 15,
            'net_30' => 30,
            'net_60' => 60,
            'net_90' => 90,
            default => 30,
        };
    }

    /**
     * Relationship: Supplier payments for this supplier
     */
    public function supplierPayments()
    {
        return $this->hasMany(SupplierPayment::class);
    }
}