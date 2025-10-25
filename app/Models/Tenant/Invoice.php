<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'invoice_number',
        'client_id',
        'store_id',
        'status',
        'issue_date',
        'due_date',
        'subtotal',
        'tax_amount',
        'total',
        'notes',
        'tags',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'tags' => 'array',
    ];

    /**
     * Invoice statuses
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_SENT = 'sent';
    const STATUS_PAID = 'paid';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Relationship: Client
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Relationship: Store
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Relationship: User who created this invoice
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship: Invoice items
     */
    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Scope: Filter by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Draft invoices
     */
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Scope: Sent invoices
     */
    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    /**
     * Scope: Paid invoices
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * Scope: Overdue invoices
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_OVERDUE)
                    ->orWhere(function ($q) {
                        $q->where('status', self::STATUS_SENT)
                          ->where('due_date', '<', now());
                    });
    }

    /**
     * Scope: Filter by date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('issue_date', [$startDate, $endDate]);
    }

    /**
     * Scope: Filter by client
     */
    public function scopeByClient($query, string $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Scope: Filter by store
     */
    public function scopeByStore($query, string $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    /**
     * Check if invoice is overdue
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->status === self::STATUS_SENT && $this->due_date < now();
    }

    /**
     * Check if invoice can be edited
     */
    public function getCanEditAttribute(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if invoice can be sent
     */
    public function getCanSendAttribute(): bool
    {
        return $this->status === self::STATUS_DRAFT && $this->items()->count() > 0;
    }

    /**
     * Check if invoice can be paid
     */
    public function getCanPayAttribute(): bool
    {
        return in_array($this->status, [self::STATUS_SENT, self::STATUS_OVERDUE]);
    }

    /**
     * Check if invoice can be cancelled
     */
    public function getCanCancelAttribute(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SENT]);
    }

    /**
     * Get days until due date
     */
    public function getDaysUntilDueAttribute(): int
    {
        return now()->diffInDays($this->due_date, false);
    }

    /**
     * Get the human-readable status
     */
    public function getStatusNameAttribute(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_SENT => 'Sent',
            self::STATUS_PAID => 'Paid',
            self::STATUS_OVERDUE => 'Overdue',
            self::STATUS_CANCELLED => 'Cancelled',
            default => ucfirst($this->status),
        };
    }

    /**
     * Send the invoice
     */
    public function send(): bool
    {
        if (!$this->can_send) {
            return false;
        }

        $this->update(['status' => self::STATUS_SENT]);
        
        // TODO: Send email notification
        
        return true;
    }

    /**
     * Mark the invoice as paid
     */
    public function markAsPaid(): bool
    {
        if (!$this->can_pay) {
            return false;
        }

        $this->update(['status' => self::STATUS_PAID]);
        
        // Update client balance
        $this->client->updateBalance(-$this->total);
        
        return true;
    }

    /**
     * Cancel the invoice
     */
    public function cancel(): bool
    {
        if (!$this->can_cancel) {
            return false;
        }

        $this->update(['status' => self::STATUS_CANCELLED]);
        
        return true;
    }

    /**
     * Calculate totals from items
     */
    public function calculateTotals(): void
    {
        $subtotal = $this->items()->sum('total');
        $taxAmount = $this->items()->sum('tax_amount');
        $total = $subtotal + $taxAmount;

        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
        ]);
    }

    /**
     * Generate the next invoice number
     */
    public static function generateInvoiceNumber(): string
    {
        $year = now()->year;
        $lastInvoice = static::whereYear('created_at', $year)
            ->orderBy('invoice_number', 'desc')
            ->first();

        if (!$lastInvoice) {
            return "INV-{$year}-0001";
        }

        // Extract the sequence number from the last invoice
        $lastNumber = (int) substr($lastInvoice->invoice_number, -4);
        $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);

        return "INV-{$year}-{$nextNumber}";
    }

    /**
     * Relationship: Invoice payments
     */
    public function invoicePayments()
    {
        return $this->hasMany(InvoicePayment::class);
    }

    /**
     * Get remaining balance after payments
     */
    public function getRemainingBalance(): float
    {
        $totalPaid = $this->invoicePayments()->sum('amount_allocated');
        return max(0, $this->total - $totalPaid);
    }

    /**
     * Check if invoice is fully paid
     */
    public function isFullyPaid(): bool
    {
        return $this->getRemainingBalance() <= 0.01; // Account for floating point precision
    }

    /**
     * Update payment status based on remaining balance
     */
    public function updatePaymentStatus(): void
    {
        if ($this->isFullyPaid() && $this->status !== self::STATUS_PAID) {
            $this->update(['status' => self::STATUS_PAID]);
        } elseif (!$this->isFullyPaid() && $this->status === self::STATUS_PAID) {
            // If payment was reversed, update status back to sent
            $this->update(['status' => self::STATUS_SENT]);
        }
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (!$invoice->invoice_number) {
                $invoice->invoice_number = static::generateInvoiceNumber();
            }
        });
    }
}