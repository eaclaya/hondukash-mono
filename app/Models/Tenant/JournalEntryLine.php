<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalEntryLine extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'journal_entry_id',
        'account_id',
        'debit',
        'credit',
        'description',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    /**
     * Relationship: Journal entry
     */
    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /**
     * Relationship: Account
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Scope: Debit lines only
     */
    public function scopeDebits($query)
    {
        return $query->where('debit', '>', 0);
    }

    /**
     * Scope: Credit lines only
     */
    public function scopeCredits($query)
    {
        return $query->where('credit', '>', 0);
    }

    /**
     * Scope: Filter by account
     */
    public function scopeByAccount($query, string $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Scope: Filter by date range through journal entry
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereHas('journalEntry', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('entry_date', [$startDate, $endDate]);
        });
    }

    /**
     * Scope: Posted lines only (through journal entry)
     */
    public function scopePosted($query)
    {
        return $query->whereHas('journalEntry', function ($q) {
            $q->where('status', 'posted');
        });
    }

    /**
     * Get the amount (debit or credit)
     */
    public function getAmountAttribute(): float
    {
        return $this->debit ?: $this->credit;
    }

    /**
     * Check if this is a debit line
     */
    public function getIsDebitAttribute(): bool
    {
        return $this->debit > 0;
    }

    /**
     * Check if this is a credit line
     */
    public function getIsCreditAttribute(): bool
    {
        return $this->credit > 0;
    }

    /**
     * Get the line type (debit or credit)
     */
    public function getLineTypeAttribute(): string
    {
        return $this->is_debit ? 'debit' : 'credit';
    }

    /**
     * Get signed amount (positive for debits, negative for credits)
     */
    public function getSignedAmountAttribute(): float
    {
        return $this->debit - $this->credit;
    }

    /**
     * Set amount as debit
     */
    public function setAsDebit(float $amount): void
    {
        $this->update([
            'debit' => $amount,
            'credit' => 0,
        ]);
    }

    /**
     * Set amount as credit
     */
    public function setAsCredit(float $amount): void
    {
        $this->update([
            'debit' => 0,
            'credit' => $amount,
        ]);
    }

    /**
     * Switch the line from debit to credit or vice versa
     */
    public function switchSide(): void
    {
        $amount = $this->amount;
        
        if ($this->is_debit) {
            $this->setAsCredit($amount);
        } else {
            $this->setAsDebit($amount);
        }
    }

    /**
     * Get the account code
     */
    public function getAccountCodeAttribute(): string
    {
        return $this->account->code ?? '';
    }

    /**
     * Get the account name
     */
    public function getAccountNameAttribute(): string
    {
        return $this->account->name ?? '';
    }

    /**
     * Get the account full name (with parent hierarchy)
     */
    public function getAccountFullNameAttribute(): string
    {
        return $this->account->full_name ?? '';
    }

    /**
     * Get the journal entry date
     */
    public function getEntryDateAttribute(): ?\Carbon\Carbon
    {
        return $this->journalEntry->entry_date ?? null;
    }

    /**
     * Get the journal entry status
     */
    public function getEntryStatusAttribute(): ?string
    {
        return $this->journalEntry->status ?? null;
    }

    /**
     * Check if the line can be edited
     */
    public function getCanEditAttribute(): bool
    {
        return $this->journalEntry->can_edit ?? false;
    }

    /**
     * Validate the line data
     */
    public function validate(): array
    {
        $errors = [];

        // Check that either debit or credit is set, but not both
        if ($this->debit > 0 && $this->credit > 0) {
            $errors[] = 'Line cannot have both debit and credit amounts';
        }

        if ($this->debit == 0 && $this->credit == 0) {
            $errors[] = 'Line must have either a debit or credit amount';
        }

        // Check that amounts are positive
        if ($this->debit < 0 || $this->credit < 0) {
            $errors[] = 'Amounts must be positive';
        }

        // Check that account exists and is active
        if (!$this->account) {
            $errors[] = 'Account not found';
        } elseif (!$this->account->is_active) {
            $errors[] = 'Account is inactive';
        }

        return $errors;
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($line) {
            // Ensure only one of debit or credit is set
            if ($line->debit > 0) {
                $line->credit = 0;
            } elseif ($line->credit > 0) {
                $line->debit = 0;
            }
        });
    }
}