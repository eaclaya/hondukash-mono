<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'code',
        'name',
        'type',
        'parent_id',
        'is_active',
        'is_cash_account',
        'is_bank_account',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'is_cash_account' => 'boolean',
        'is_bank_account' => 'boolean',
    ];

    /**
     * Account types
     */
    const TYPE_ASSET = 'asset';
    const TYPE_LIABILITY = 'liability';
    const TYPE_EQUITY = 'equity';
    const TYPE_REVENUE = 'revenue';
    const TYPE_EXPENSE = 'expense';

    /**
     * Relationship: Parent account
     */
    public function parent()
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    /**
     * Relationship: Child accounts
     */
    public function children()
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    /**
     * Relationship: Journal entry lines
     */
    public function journalEntryLines()
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    /**
     * Relationship: Expenses
     */
    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Scope: Only active accounts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Filter by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Assets only
     */
    public function scopeAssets($query)
    {
        return $query->where('type', self::TYPE_ASSET);
    }

    /**
     * Scope: Liabilities only
     */
    public function scopeLiabilities($query)
    {
        return $query->where('type', self::TYPE_LIABILITY);
    }

    /**
     * Scope: Equity accounts only
     */
    public function scopeEquity($query)
    {
        return $query->where('type', self::TYPE_EQUITY);
    }

    /**
     * Scope: Revenue accounts only
     */
    public function scopeRevenue($query)
    {
        return $query->where('type', self::TYPE_REVENUE);
    }

    /**
     * Scope: Expense accounts only
     */
    public function scopeExpense($query)
    {
        return $query->where('type', self::TYPE_EXPENSE);
    }

    /**
     * Scope: Parent accounts only (no parent)
     */
    public function scopeParents($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope: Child accounts only (has parent)
     */
    public function scopeChildren($query)
    {
        return $query->whereNotNull('parent_id');
    }

    /**
     * Get the full account path (parent codes + this code)
     */
    public function getFullCodeAttribute(): string
    {
        $codes = [];
        $account = $this;

        while ($account) {
            array_unshift($codes, $account->code);
            $account = $account->parent;
        }

        return implode('-', $codes);
    }

    /**
     * Get the full account name with parent names
     */
    public function getFullNameAttribute(): string
    {
        $names = [];
        $account = $this;

        while ($account) {
            array_unshift($names, $account->name);
            $account = $account->parent;
        }

        return implode(' > ', $names);
    }

    /**
     * Check if account is a parent account
     */
    public function getIsParentAttribute(): bool
    {
        return $this->children()->count() > 0;
    }

    /**
     * Check if account is a leaf account (no children)
     */
    public function getIsLeafAttribute(): bool
    {
        return $this->children()->count() === 0;
    }

    /**
     * Get account balance
     */
    public function getBalance($startDate = null, $endDate = null): float
    {
        $query = $this->journalEntryLines()
            ->whereHas('journalEntry', function ($q) use ($startDate, $endDate) {
                $q->where('status', 'posted');
                
                if ($startDate) {
                    $q->where('entry_date', '>=', $startDate);
                }
                
                if ($endDate) {
                    $q->where('entry_date', '<=', $endDate);
                }
            });

        $debits = $query->sum('debit');
        $credits = $query->sum('credit');

        // Return balance based on account type
        // Assets and Expenses: Debit increases balance
        // Liabilities, Equity, and Revenue: Credit increases balance
        if (in_array($this->type, [self::TYPE_ASSET, self::TYPE_EXPENSE])) {
            return $debits - $credits;
        } else {
            return $credits - $debits;
        }
    }

    /**
     * Get debit balance
     */
    public function getDebitBalance($startDate = null, $endDate = null): float
    {
        $balance = $this->getBalance($startDate, $endDate);
        return max(0, $balance);
    }

    /**
     * Get credit balance
     */
    public function getCreditBalance($startDate = null, $endDate = null): float
    {
        $balance = $this->getBalance($startDate, $endDate);
        return max(0, -$balance);
    }

    /**
     * Check if account has transactions
     */
    public function hasTransactions(): bool
    {
        return $this->journalEntryLines()->count() > 0;
    }

    /**
     * Get all descendant accounts (children and their children)
     */
    public function getDescendants(): \Illuminate\Support\Collection
    {
        $descendants = collect();
        
        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->getDescendants());
        }

        return $descendants;
    }

    /**
     * Get consolidated balance including all descendant accounts
     */
    public function getConsolidatedBalance($startDate = null, $endDate = null): float
    {
        $balance = $this->getBalance($startDate, $endDate);
        
        foreach ($this->getDescendants() as $descendant) {
            $balance += $descendant->getBalance($startDate, $endDate);
        }

        return $balance;
    }

    /**
     * Get the human-readable account type
     */
    public function getAccountTypeNameAttribute(): string
    {
        return match($this->type) {
            self::TYPE_ASSET => 'Asset',
            self::TYPE_LIABILITY => 'Liability',
            self::TYPE_EQUITY => 'Equity',
            self::TYPE_REVENUE => 'Revenue',
            self::TYPE_EXPENSE => 'Expense',
            default => ucfirst($this->type),
        };
    }

    /**
     * Check if this account type normally has a debit balance
     */
    public function getHasDebitBalanceAttribute(): bool
    {
        return in_array($this->type, [self::TYPE_ASSET, self::TYPE_EXPENSE]);
    }

    /**
     * Check if this account type normally has a credit balance
     */
    public function getHasCreditBalanceAttribute(): bool
    {
        return in_array($this->type, [self::TYPE_LIABILITY, self::TYPE_EQUITY, self::TYPE_REVENUE]);
    }

    /**
     * Scope: Cash accounts only
     */
    public function scopeCashAccounts($query)
    {
        return $query->where('is_cash_account', true);
    }

    /**
     * Scope: Bank accounts only
     */
    public function scopeBankAccounts($query)
    {
        return $query->where('is_bank_account', true);
    }

    /**
     * Get cash flow activities for this account
     */
    public function getCashFlowActivities($startDate, $endDate, $cashFlowCategory = null)
    {
        $query = $this->journalEntryLines()
            ->whereHas('journalEntry', function ($q) use ($startDate, $endDate, $cashFlowCategory) {
                $q->where('status', 'posted')
                  ->where('affects_cash', true)
                  ->whereBetween('entry_date', [$startDate, $endDate]);
                
                if ($cashFlowCategory) {
                    $q->where('cash_flow_category', $cashFlowCategory);
                }
            })
            ->with(['journalEntry']);

        return $query->get();
    }

    /**
     * Get net cash flow for this account in a period
     */
    public function getNetCashFlow($startDate, $endDate, $cashFlowCategory = null): float
    {
        $activities = $this->getCashFlowActivities($startDate, $endDate, $cashFlowCategory);
        
        $netFlow = 0;
        foreach ($activities as $line) {
            if ($this->has_debit_balance) {
                $netFlow += ($line->debit - $line->credit);
            } else {
                $netFlow += ($line->credit - $line->debit);
            }
        }

        return $netFlow;
    }
}