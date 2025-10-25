<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'code',
        'name',
        'type',
        'tax_id',
        'email',
        'phone',
        'address',
        'tags',
        'credit_limit',
        'balance',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'address' => 'array',
        'tags' => 'array',
        'credit_limit' => 'decimal:2',
        'balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Client types
     */
    const TYPE_INDIVIDUAL = 'individual';
    const TYPE_COMPANY = 'company';

    /**
     * Relationship: Invoices for this client
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Scope: Only active clients
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
     * Scope: Individual clients only
     */
    public function scopeIndividuals($query)
    {
        return $query->where('type', self::TYPE_INDIVIDUAL);
    }

    /**
     * Scope: Company clients only
     */
    public function scopeCompanies($query)
    {
        return $query->where('type', self::TYPE_COMPANY);
    }

    /**
     * Scope: Clients with overdue invoices
     */
    public function scopeWithOverdueInvoices($query)
    {
        return $query->whereHas('invoices', function ($q) {
            $q->where('status', 'overdue');
        });
    }

    /**
     * Scope: Clients over credit limit
     */
    public function scopeOverCreditLimit($query)
    {
        return $query->whereNotNull('credit_limit')
                    ->whereRaw('balance > credit_limit');
    }

    /**
     * Check if client has a specific tag
     */
    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags ?? []);
    }

    /**
     * Check if client has any of the given tags
     */
    public function hasAnyTag(array $tags): bool
    {
        return !empty(array_intersect($tags, $this->tags ?? []));
    }

    /**
     * Add a tag to the client
     */
    public function addTag(string $tag): void
    {
        $tags = $this->tags ?? [];
        if (!in_array($tag, $tags)) {
            $tags[] = $tag;
            $this->update(['tags' => $tags]);
        }
    }

    /**
     * Remove a tag from the client
     */
    public function removeTag(string $tag): void
    {
        $tags = $this->tags ?? [];
        $tags = array_values(array_filter($tags, fn($t) => $t !== $tag));
        $this->update(['tags' => $tags]);
    }

    /**
     * Check if client is over credit limit
     */
    public function isOverCreditLimit(): bool
    {
        if (!$this->credit_limit) {
            return false;
        }

        return $this->balance > $this->credit_limit;
    }

    /**
     * Get available credit
     */
    public function getAvailableCreditAttribute(): float
    {
        if (!$this->credit_limit) {
            return PHP_FLOAT_MAX;
        }

        return max(0, $this->credit_limit - $this->balance);
    }

    /**
     * Get total outstanding amount (unpaid invoices)
     */
    public function getTotalOutstandingAttribute(): float
    {
        return $this->invoices()
            ->whereIn('status', ['sent', 'overdue'])
            ->sum('total');
    }

    /**
     * Get total paid amount
     */
    public function getTotalPaidAttribute(): float
    {
        return $this->invoices()
            ->where('status', 'paid')
            ->sum('total');
    }

    /**
     * Get overdue invoices
     */
    public function getOverdueInvoices()
    {
        return $this->invoices()
            ->where('status', 'overdue')
            ->orWhere(function ($q) {
                $q->where('status', 'sent')
                  ->where('due_date', '<', now());
            })
            ->get();
    }

    /**
     * Get the full address as a formatted string
     */
    public function getFormattedAddressAttribute(): ?string
    {
        if (!$this->address) {
            return null;
        }

        $parts = array_filter([
            $this->address['street'] ?? null,
            $this->address['city'] ?? null,
            $this->address['state'] ?? null,
            $this->address['postal_code'] ?? null,
            $this->address['country'] ?? null,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Update client balance
     */
    public function updateBalance(float $amount): void
    {
        $this->increment('balance', $amount);
    }

    /**
     * Get the human-readable client type
     */
    public function getClientTypeNameAttribute(): string
    {
        return match($this->type) {
            self::TYPE_INDIVIDUAL => 'Individual',
            self::TYPE_COMPANY => 'Company',
            default => ucfirst($this->type),
        };
    }

    /**
     * Get aging report for this client
     */
    public function getAgingReport($asOfDate = null): array
    {
        $asOfDate = $asOfDate ?: now()->toDateString();
        
        $invoices = $this->invoices()
            ->whereIn('status', ['sent', 'overdue'])
            ->where('issue_date', '<=', $asOfDate)
            ->with('invoicePayments')
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

        foreach ($invoices as $invoice) {
            $remainingBalance = $invoice->getRemainingBalance();
            
            if ($remainingBalance <= 0) {
                continue;
            }

            $daysPastDue = max(0, now()->parse($asOfDate)->diffInDays($invoice->due_date, false));
            
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
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'issue_date' => $invoice->issue_date,
                'due_date' => $invoice->due_date,
                'total_amount' => $invoice->total,
                'remaining_balance' => $remainingBalance,
                'days_past_due' => $daysPastDue,
                'aging_bucket' => $bucket
            ];
        }

        return $aging;
    }

    /**
     * Relationship: Invoice payments for this client
     */
    public function invoicePayments()
    {
        return $this->hasManyThrough(InvoicePayment::class, Invoice::class);
    }
}