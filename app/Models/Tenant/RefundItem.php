<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RefundItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'refund_id',
        'invoice_item_id',
        'product_id',
        'description',
        'quantity_refunded',
        'unit_price',
        'tax_amount',
        'total',
        'reason',
    ];

    protected $casts = [
        'quantity_refunded' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    // Relationships
    public function refund(): BelongsTo
    {
        return $this->belongsTo(Refund::class);
    }

    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Business logic methods
    public function calculateTotal(): void
    {
        $this->total = ($this->quantity_refunded * $this->unit_price) + $this->tax_amount;
        $this->save();
    }

    public function getMaxRefundableQuantityAttribute(): float
    {
        if (!$this->invoiceItem) {
            return 0;
        }

        // Get total already refunded for this invoice item
        $alreadyRefunded = self::where('invoice_item_id', $this->invoice_item_id)
            ->where('id', '!=', $this->id)
            ->whereHas('refund', function ($query) {
                $query->whereIn('status', ['approved', 'processed']);
            })
            ->sum('quantity_refunded');

        return $this->invoiceItem->quantity - $alreadyRefunded;
    }

    public function getIsValidQuantityAttribute(): bool
    {
        return $this->quantity_refunded > 0 && 
               $this->quantity_refunded <= $this->max_refundable_quantity;
    }

    public function getLineSubtotalAttribute(): float
    {
        return $this->quantity_refunded * $this->unit_price;
    }

    public function getLineTotalAttribute(): float
    {
        return $this->line_subtotal + $this->tax_amount;
    }

    public function getTaxRateAttribute(): float
    {
        if ($this->line_subtotal == 0) {
            return 0;
        }

        return ($this->tax_amount / $this->line_subtotal) * 100;
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($refundItem) {
            // Auto-calculate total if not already set
            if (!$refundItem->total) {
                $refundItem->total = ($refundItem->quantity_refunded * $refundItem->unit_price) + $refundItem->tax_amount;
            }
        });
    }
}