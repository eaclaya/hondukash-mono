<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'expense_number',
        'vendor_name',
        'category_id',
        'account_id',
        'amount',
        'tax_amount',
        'expense_date',
        'description',
        'receipt_url',
        'status',
        'approved_by',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'expense_date' => 'date',
    ];

    /**
     * Expense statuses
     */
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_PAID = 'paid';

    /**
     * Relationship: Account
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Relationship: User who created this expense
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship: User who approved this expense
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope: Filter by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Pending expenses
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: Approved expenses
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope: Rejected expenses
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Scope: Paid expenses
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * Scope: Filter by date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('expense_date', [$startDate, $endDate]);
    }

    /**
     * Scope: Filter by account
     */
    public function scopeByAccount($query, string $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Scope: Filter by category
     */
    public function scopeByCategory($query, string $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope: Filter by creator
     */
    public function scopeByCreator($query, string $userId)
    {
        return $query->where('created_by', $userId);
    }

    /**
     * Get total amount including tax
     */
    public function getTotalAmountAttribute(): float
    {
        return $this->amount + $this->tax_amount;
    }

    /**
     * Check if expense can be approved
     */
    public function getCanApproveAttribute(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if expense can be rejected
     */
    public function getCanRejectAttribute(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if expense can be paid
     */
    public function getCanPayAttribute(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if expense can be edited
     */
    public function getCanEditAttribute(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if expense has a receipt
     */
    public function getHasReceiptAttribute(): bool
    {
        return !empty($this->receipt_url);
    }

    /**
     * Get the human-readable status
     */
    public function getStatusNameAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_PAID => 'Paid',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get days since expense date
     */
    public function getDaysOldAttribute(): int
    {
        return $this->expense_date->diffInDays(now());
    }

    /**
     * Approve the expense
     */
    public function approve(string $approvedBy = null): bool
    {
        if (!$this->can_approve) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $approvedBy ?? auth()->id(),
        ]);

        return true;
    }

    /**
     * Reject the expense
     */
    public function reject(): bool
    {
        if (!$this->can_reject) {
            return false;
        }

        $this->update(['status' => self::STATUS_REJECTED]);

        return true;
    }

    /**
     * Mark the expense as paid
     */
    public function markAsPaid(): bool
    {
        if (!$this->can_pay) {
            return false;
        }

        \DB::transaction(function () {
            // Update expense status
            $this->update(['status' => self::STATUS_PAID]);

            // Create journal entry for the expense
            $journalEntry = JournalEntry::create([
                'entry_date' => $this->expense_date,
                'description' => "Expense payment: {$this->description}",
                'reference_type' => 'expense',
                'reference_id' => $this->id,
                'status' => 'posted',
                'created_by' => auth()->id() ?? $this->created_by,
            ]);

            // Debit the expense account
            $journalEntry->addDebitLine(
                $this->account_id,
                $this->amount,
                "Expense: {$this->vendor_name}"
            );

            // If there's tax, debit the tax account (assuming a tax expense account exists)
            if ($this->tax_amount > 0) {
                // TODO: Configure tax expense account
                $taxAccount = Account::where('code', 'TAX-EXP')->first();
                if ($taxAccount) {
                    $journalEntry->addDebitLine(
                        $taxAccount->id,
                        $this->tax_amount,
                        "Tax on expense: {$this->vendor_name}"
                    );
                }
            }

            // Credit cash/accounts payable (assuming cash account for now)
            // TODO: Implement proper payment method tracking
            $cashAccount = Account::where('code', 'CASH')->first();
            if ($cashAccount) {
                $journalEntry->addCreditLine(
                    $cashAccount->id,
                    $this->total_amount,
                    "Payment to: {$this->vendor_name}"
                );
            }
        });

        return true;
    }

    /**
     * Upload receipt
     */
    public function uploadReceipt(string $url): void
    {
        $this->update(['receipt_url' => $url]);
    }

    /**
     * Generate the next expense number
     */
    public static function generateExpenseNumber(): string
    {
        $year = now()->year;
        $lastExpense = static::whereYear('created_at', $year)
            ->orderBy('expense_number', 'desc')
            ->first();

        if (!$lastExpense) {
            return "EXP-{$year}-0001";
        }

        // Extract the sequence number from the last expense
        $lastNumber = (int) substr($lastExpense->expense_number, -4);
        $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);

        return "EXP-{$year}-{$nextNumber}";
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
        return max(0, $this->total_amount - $totalPaid);
    }

    /**
     * Check if expense is fully paid
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
        if ($this->isFullyPaid() && $this->status !== self::STATUS_PAID) {
            $this->update(['status' => self::STATUS_PAID]);
        }
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($expense) {
            if (!$expense->expense_number) {
                $expense->expense_number = static::generateExpenseNumber();
            }

            if (!$expense->status) {
                $expense->status = self::STATUS_PENDING;
            }
        });
    }
}