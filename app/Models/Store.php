<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Store extends Model
{
    use HasFactory;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'tax_rate',
        'currency',
        'timezone',
        'business_hours',
        'is_active',
        'is_default',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'business_hours' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'tax_rate' => 'decimal:4',
    ];

    /**
     * Boot function to automatically generate UUID for new stores.
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = Str::uuid()->toString();
            }
        });

        // Ensure only one default store exists
        static::saving(function ($model) {
            if ($model->is_default) {
                static::where('is_default', true)
                    ->where('id', '!=', $model->id)
                    ->update(['is_default' => false]);
            }
        });
    }

    /**
     * Scope to get only active stores.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get the default store.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Get the business hours for a specific day.
     *
     * @param string $day
     * @return array|null
     */
    public function getBusinessHoursForDay(string $day): ?array
    {
        return $this->business_hours[$day] ?? null;
    }

    /**
     * Check if the store is open on a given day and time.
     *
     * @param string $day
     * @param string $time
     * @return bool
     */
    public function isOpenAt(string $day, string $time): bool
    {
        $hours = $this->getBusinessHoursForDay($day);
        
        if (!$hours || !$hours['is_open']) {
            return false;
        }

        return $time >= $hours['open'] && $time <= $hours['close'];
    }

    /**
     * Get the formatted address.
     *
     * @return string
     */
    public function getFormattedAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);

        return implode(', ', $parts);
    }
}