<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'invoice_id',
        'product_id',
        'description',
        'quantity',
        'unit_price',
        'discount_amount',
        'tax_amount',
        'total',
        'applied_rules',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'applied_rules' => 'array',
    ];

    /**
     * Relationship: Invoice
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Relationship: Product
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get subtotal before discount and tax
     */
    public function getSubtotalAttribute(): float
    {
        return $this->quantity * $this->unit_price;
    }

    /**
     * Get discounted subtotal
     */
    public function getDiscountedSubtotalAttribute(): float
    {
        return $this->subtotal - $this->discount_amount;
    }

    /**
     * Get discount percentage
     */
    public function getDiscountPercentageAttribute(): float
    {
        if ($this->subtotal == 0) {
            return 0;
        }

        return ($this->discount_amount / $this->subtotal) * 100;
    }

    /**
     * Get tax percentage
     */
    public function getTaxPercentageAttribute(): float
    {
        if ($this->discounted_subtotal == 0) {
            return 0;
        }

        return ($this->tax_amount / $this->discounted_subtotal) * 100;
    }

    /**
     * Calculate line total
     */
    public function calculateTotal(): float
    {
        $subtotal = $this->quantity * $this->unit_price;
        $afterDiscount = $subtotal - $this->discount_amount;
        return $afterDiscount + $this->tax_amount;
    }

    /**
     * Update the total based on quantity, price, discount, and tax
     */
    public function updateTotal(): void
    {
        $this->total = $this->calculateTotal();
        $this->save();
    }

    /**
     * Set quantity and recalculate total
     */
    public function setQuantity(float $quantity): void
    {
        $this->quantity = $quantity;
        $this->updateTotal();
    }

    /**
     * Set unit price and recalculate total
     */
    public function setUnitPrice(float $unitPrice): void
    {
        $this->unit_price = $unitPrice;
        $this->updateTotal();
    }

    /**
     * Set discount amount and recalculate total
     */
    public function setDiscountAmount(float $discountAmount): void
    {
        $this->discount_amount = $discountAmount;
        $this->updateTotal();
    }

    /**
     * Set tax amount and recalculate total
     */
    public function setTaxAmount(float $taxAmount): void
    {
        $this->tax_amount = $taxAmount;
        $this->updateTotal();
    }

    /**
     * Apply a pricing rule to this item
     */
    public function applyPricingRule(PricingRule $rule): void
    {
        $originalPrice = $this->unit_price;
        $newPrice = $rule->applyToPrice($originalPrice);
        $discountAmount = $originalPrice - $newPrice;

        // Update the discount amount
        if ($rule->is_stackable) {
            $this->discount_amount += $discountAmount;
        } else {
            $this->discount_amount = $discountAmount;
        }

        // Track the applied rule
        $appliedRules = $this->applied_rules ?? [];
        $appliedRules[] = [
            'rule_id' => $rule->id,
            'rule_name' => $rule->name,
            'rule_type' => $rule->rule_type,
            'value' => $rule->value,
            'discount_amount' => $discountAmount,
            'applied_at' => now(),
        ];

        $this->applied_rules = $appliedRules;
        $this->updateTotal();
    }

    /**
     * Remove all applied pricing rules
     */
    public function clearPricingRules(): void
    {
        $this->discount_amount = 0;
        $this->applied_rules = [];
        $this->updateTotal();
    }

    /**
     * Get total discount from all applied rules
     */
    public function getTotalRuleDiscountAttribute(): float
    {
        if (!$this->applied_rules) {
            return 0;
        }

        return collect($this->applied_rules)->sum('discount_amount');
    }

    /**
     * Check if a specific rule has been applied
     */
    public function hasAppliedRule(string $ruleId): bool
    {
        if (!$this->applied_rules) {
            return false;
        }

        return collect($this->applied_rules)->contains('rule_id', $ruleId);
    }

    /**
     * Get applied rule names
     */
    public function getAppliedRuleNamesAttribute(): array
    {
        if (!$this->applied_rules) {
            return [];
        }

        return collect($this->applied_rules)->pluck('rule_name')->toArray();
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            if (!$item->total) {
                $item->total = $item->calculateTotal();
            }
        });

        static::updating(function ($item) {
            if ($item->isDirty(['quantity', 'unit_price', 'discount_amount', 'tax_amount'])) {
                $item->total = $item->calculateTotal();
            }
        });
    }
}