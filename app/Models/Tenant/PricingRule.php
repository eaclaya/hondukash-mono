<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PricingRule extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'description',
        'rule_type',
        'value',
        'conditions',
        'priority',
        'is_stackable',
        'valid_from',
        'valid_to',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'value' => 'decimal:2',
        'conditions' => 'array',
        'priority' => 'integer',
        'is_stackable' => 'boolean',
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Rule types
     */
    const TYPE_DISCOUNT_PERCENT = 'discount_percent';
    const TYPE_DISCOUNT_FIXED = 'discount_fixed';
    const TYPE_SPECIAL_PRICE = 'special_price';

    /**
     * Scope: Only active rules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Valid at a specific date
     */
    public function scopeValidAt($query, $date = null)
    {
        $date = $date ?? now();
        
        return $query->where(function ($q) use ($date) {
            $q->whereNull('valid_from')->orWhere('valid_from', '<=', $date);
        })->where(function ($q) use ($date) {
            $q->whereNull('valid_to')->orWhere('valid_to', '>=', $date);
        });
    }

    /**
     * Scope: Order by priority (highest first)
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    /**
     * Scope: Stackable rules only
     */
    public function scopeStackable($query)
    {
        return $query->where('is_stackable', true);
    }

    /**
     * Check if rule is valid at a specific date
     */
    public function isValidAt($date = null): bool
    {
        $date = $date ?? now();
        
        if ($this->valid_from && $this->valid_from->gt($date)) {
            return false;
        }
        
        if ($this->valid_to && $this->valid_to->lt($date)) {
            return false;
        }
        
        return $this->is_active;
    }

    /**
     * Evaluate rule conditions against context
     */
    public function evaluateConditions(array $context): bool
    {
        if (empty($this->conditions)) {
            return true;
        }

        return $this->evaluateConditionGroup($this->conditions, $context);
    }

    /**
     * Evaluate a condition group (with AND/OR logic)
     */
    private function evaluateConditionGroup(array $conditions, array $context): bool
    {
        // Handle AND logic
        if (isset($conditions['all'])) {
            foreach ($conditions['all'] as $condition) {
                if (is_array($condition) && isset($condition['field'])) {
                    if (!$this->evaluateCondition($condition, $context)) {
                        return false;
                    }
                } elseif (is_array($condition)) {
                    // Nested condition group
                    if (!$this->evaluateConditionGroup($condition, $context)) {
                        return false;
                    }
                }
            }
            return true;
        }

        // Handle OR logic
        if (isset($conditions['any'])) {
            foreach ($conditions['any'] as $condition) {
                if (is_array($condition) && isset($condition['field'])) {
                    if ($this->evaluateCondition($condition, $context)) {
                        return true;
                    }
                } elseif (is_array($condition)) {
                    // Nested condition group
                    if ($this->evaluateConditionGroup($condition, $context)) {
                        return true;
                    }
                }
            }
            return false;
        }

        // Handle single condition
        if (isset($conditions['field'])) {
            return $this->evaluateCondition($conditions, $context);
        }

        return true;
    }

    /**
     * Evaluate a single condition
     */
    private function evaluateCondition(array $condition, array $context): bool
    {
        $field = $condition['field'];
        $operator = $condition['operator'];
        $value = $condition['value'];
        
        $contextValue = data_get($context, $field);

        return match($operator) {
            'equals', '=' => $contextValue == $value,
            'not_equals', '!=' => $contextValue != $value,
            'greater_than', '>' => $contextValue > $value,
            'greater_than_or_equal', '>=' => $contextValue >= $value,
            'less_than', '<' => $contextValue < $value,
            'less_than_or_equal', '<=' => $contextValue <= $value,
            'contains' => is_string($contextValue) && str_contains($contextValue, $value),
            'not_contains' => is_string($contextValue) && !str_contains($contextValue, $value),
            'in' => is_array($value) && in_array($contextValue, $value),
            'not_in' => is_array($value) && !in_array($contextValue, $value),
            default => false,
        };
    }

    /**
     * Apply the rule to a base price
     */
    public function applyToPrice(float $basePrice): float
    {
        return match($this->rule_type) {
            self::TYPE_DISCOUNT_PERCENT => $basePrice * (1 - $this->value / 100),
            self::TYPE_DISCOUNT_FIXED => max(0, $basePrice - $this->value),
            self::TYPE_SPECIAL_PRICE => $this->value,
            default => $basePrice,
        };
    }

    /**
     * Calculate the discount amount for a base price
     */
    public function calculateDiscount(float $basePrice): float
    {
        $finalPrice = $this->applyToPrice($basePrice);
        return $basePrice - $finalPrice;
    }

    /**
     * Get the human-readable rule type
     */
    public function getRuleTypeNameAttribute(): string
    {
        return match($this->rule_type) {
            self::TYPE_DISCOUNT_PERCENT => 'Percentage Discount',
            self::TYPE_DISCOUNT_FIXED => 'Fixed Discount',
            self::TYPE_SPECIAL_PRICE => 'Special Price',
            default => ucwords(str_replace('_', ' ', $this->rule_type)),
        };
    }
}