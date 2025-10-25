<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'entry_date',
        'description',
        'reference_type',
        'reference_id',
        'status',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'entry_date' => 'date',
    ];

    /**
     * Journal entry statuses
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_POSTED = 'posted';
    const STATUS_REVERSED = 'reversed';

    /**
     * Relationship: User who created this entry
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship: Journal entry lines
     */
    public function lines()
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    /**
     * Scope: Filter by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Posted entries only
     */
    public function scopePosted($query)
    {
        return $query->where('status', self::STATUS_POSTED);
    }

    /**
     * Scope: Draft entries only
     */
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Scope: Filter by date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('entry_date', [$startDate, $endDate]);
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
     * Get total debits
     */
    public function getTotalDebitsAttribute(): float
    {
        return $this->lines()->sum('debit');
    }

    /**
     * Get total credits
     */
    public function getTotalCreditsAttribute(): float
    {
        return $this->lines()->sum('credit');
    }

    /**
     * Check if journal entry is balanced
     */
    public function getIsBalancedAttribute(): bool
    {
        return abs($this->total_debits - $this->total_credits) < 0.01; // Allow for minor rounding differences
    }

    /**
     * Get the difference between debits and credits
     */
    public function getBalanceDifferenceAttribute(): float
    {
        return $this->total_debits - $this->total_credits;
    }

    /**
     * Check if entry can be posted
     */
    public function getCanPostAttribute(): bool
    {
        return $this->status === self::STATUS_DRAFT 
               && $this->is_balanced 
               && $this->lines()->count() >= 2;
    }

    /**
     * Check if entry can be reversed
     */
    public function getCanReverseAttribute(): bool
    {
        return $this->status === self::STATUS_POSTED;
    }

    /**
     * Check if entry can be edited
     */
    public function getCanEditAttribute(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Get the human-readable status
     */
    public function getStatusNameAttribute(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_POSTED => 'Posted',
            self::STATUS_REVERSED => 'Reversed',
            default => ucfirst($this->status),
        };
    }

    /**
     * Post the journal entry
     */
    public function post(): bool
    {
        if (!$this->can_post) {
            return false;
        }

        $this->update(['status' => self::STATUS_POSTED]);
        
        return true;
    }

    /**
     * Reverse the journal entry
     */
    public function reverse(): bool
    {
        if (!$this->can_reverse) {
            return false;
        }

        \DB::transaction(function () {
            // Create a new journal entry with reversed amounts
            $reversalEntry = static::create([
                'entry_date' => now()->toDateString(),
                'description' => "Reversal of: {$this->description}",
                'reference_type' => 'journal_entry_reversal',
                'reference_id' => $this->id,
                'status' => self::STATUS_POSTED,
                'created_by' => auth()->id() ?? $this->created_by,
            ]);

            // Create reversed lines
            foreach ($this->lines as $line) {
                $reversalEntry->lines()->create([
                    'account_id' => $line->account_id,
                    'debit' => $line->credit, // Swap debit and credit
                    'credit' => $line->debit,
                    'description' => "Reversal of: {$line->description}",
                ]);
            }

            // Mark original entry as reversed
            $this->update(['status' => self::STATUS_REVERSED]);
        });

        return true;
    }

    /**
     * Add a debit line to the journal entry
     */
    public function addDebitLine(string $accountId, float $amount, string $description = null): JournalEntryLine
    {
        return $this->lines()->create([
            'account_id' => $accountId,
            'debit' => $amount,
            'credit' => 0,
            'description' => $description,
        ]);
    }

    /**
     * Add a credit line to the journal entry
     */
    public function addCreditLine(string $accountId, float $amount, string $description = null): JournalEntryLine
    {
        return $this->lines()->create([
            'account_id' => $accountId,
            'debit' => 0,
            'credit' => $amount,
            'description' => $description,
        ]);
    }

    /**
     * Clear all lines from the journal entry
     */
    public function clearLines(): void
    {
        if ($this->can_edit) {
            $this->lines()->delete();
        }
    }

    /**
     * Validate that the journal entry is ready for posting
     */
    public function validate(): array
    {
        $errors = [];

        if ($this->lines()->count() < 2) {
            $errors[] = 'Journal entry must have at least 2 lines';
        }

        if (!$this->is_balanced) {
            $errors[] = 'Journal entry is not balanced (debits must equal credits)';
        }

        if ($this->total_debits == 0) {
            $errors[] = 'Journal entry must have non-zero amounts';
        }

        // Check that all accounts exist and are active
        $inactiveAccounts = $this->lines()
            ->whereHas('account', function ($q) {
                $q->where('is_active', false);
            })
            ->with('account')
            ->get();

        if ($inactiveAccounts->count() > 0) {
            $accountNames = $inactiveAccounts->pluck('account.name')->implode(', ');
            $errors[] = "Inactive accounts used: {$accountNames}";
        }

        return $errors;
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($entry) {
            if (!$entry->status) {
                $entry->status = self::STATUS_DRAFT;
            }
        });
    }
}