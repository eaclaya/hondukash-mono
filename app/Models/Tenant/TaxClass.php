<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TaxClass extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'code',
        'description',
        'tax_rate',
        'calculation_type',
        'compound',
        'rules',
        'is_active',
    ];

    protected $casts = [
        'tax_rate' => 'decimal:4',
        'compound' => 'boolean',
        'rules' => 'array',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
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

    public function scopeByType($query, $type)
    {
        return $query->where('calculation_type', $type);
    }

    // Business logic methods
    public function calculateTax(float $amount, array $context = []): float
    {
        if (!$this->is_active) {
            return 0;
        }

        // Check if any exemption rules apply
        if ($this->hasExemptions($context)) {
            return 0;
        }

        return match($this->calculation_type) {
            'percentage' => $amount * ($this->tax_rate / 100),
            'fixed_amount' => $this->tax_rate,
            default => 0,
        };
    }

    public function calculateTaxIncluded(float $totalAmount): array
    {
        if (!$this->is_active || $this->calculation_type !== 'percentage') {
            return [
                'subtotal' => $totalAmount,
                'tax_amount' => 0,
                'total' => $totalAmount,
            ];
        }

        $taxRate = $this->tax_rate / 100;
        $subtotal = $totalAmount / (1 + $taxRate);
        $taxAmount = $totalAmount - $subtotal;

        return [
            'subtotal' => round($subtotal, 2),
            'tax_amount' => round($taxAmount, 2),
            'total' => $totalAmount,
        ];
    }

    public function getFormattedRateAttribute(): string
    {
        return match($this->calculation_type) {
            'percentage' => number_format($this->tax_rate, 2) . '%',
            'fixed_amount' => '$' . number_format($this->tax_rate, 2),
            default => '',
        };
    }

    public function getEffectiveRateAttribute(): float
    {
        return $this->calculation_type === 'percentage' ? $this->tax_rate : 0;
    }

    public function hasExemptions(array $context = []): bool
    {
        if (!$this->rules || !is_array($this->rules)) {
            return false;
        }

        $exemptions = $this->rules['exemptions'] ?? [];
        
        if (empty($exemptions)) {
            return false;
        }

        foreach ($exemptions as $exemption) {
            if ($this->checkExemptionRule($exemption, $context)) {
                return true;
            }
        }

        return false;
    }

    protected function checkExemptionRule(array $rule, array $context): bool
    {
        $type = $rule['type'] ?? '';
        $value = $rule['value'] ?? '';

        return match($type) {
            'customer_type' => ($context['customer_type'] ?? '') === $value,
            'product_category' => ($context['product_category'] ?? '') === $value,
            'amount_threshold' => ($context['amount'] ?? 0) >= floatval($value),
            'location' => ($context['location'] ?? '') === $value,
            default => false,
        };
    }

    public function getApplicableProductsCountAttribute(): int
    {
        return $this->products()->active()->count();
    }

    public function duplicate(string $newCode = null, string $newName = null): self
    {
        $newTaxClass = $this->replicate();
        $newTaxClass->code = $newCode ?: $this->code . '_copy';
        $newTaxClass->name = $newName ?: $this->name . ' (Copy)';
        $newTaxClass->save();

        return $newTaxClass;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($taxClass) {
            // Ensure unique code
            $originalCode = $taxClass->code;
            $count = 1;
            
            while (static::where('code', $taxClass->code)->exists()) {
                $taxClass->code = $originalCode . '_' . $count;
                $count++;
            }
        });
    }
}