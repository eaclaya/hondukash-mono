<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
        'image_url',
        'metadata',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Relationships
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRootCategories($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeBySlug($query, $slug)
    {
        return $query->where('slug', $slug);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // Business logic methods
    public function getFullNameAttribute(): string
    {
        $names = collect([$this->name]);
        $parent = $this->parent;

        while ($parent) {
            $names->prepend($parent->name);
            $parent = $parent->parent;
        }

        return $names->implode(' > ');
    }

    public function getDepthAttribute(): int
    {
        $depth = 0;
        $parent = $this->parent;

        while ($parent) {
            $depth++;
            $parent = $parent->parent;
        }

        return $depth;
    }

    public function getAllChildrenAttribute()
    {
        $children = collect();
        
        foreach ($this->children as $child) {
            $children->push($child);
            $children = $children->merge($child->all_children);
        }

        return $children;
    }

    public function getAllProductsAttribute()
    {
        $products = $this->products;
        
        foreach ($this->all_children as $child) {
            $products = $products->merge($child->products);
        }

        return $products;
    }

    public function getBreadcrumbAttribute(): array
    {
        $breadcrumb = [];
        $current = $this;

        while ($current) {
            array_unshift($breadcrumb, [
                'id' => $current->id,
                'name' => $current->name,
                'slug' => $current->slug,
            ]);
            $current = $current->parent;
        }

        return $breadcrumb;
    }

    public function getActiveChildrenAttribute()
    {
        return $this->children()->active()->ordered()->get();
    }

    public function getActiveProductsCountAttribute(): int
    {
        return $this->products()->active()->count();
    }

    public function getTotalProductsCountAttribute(): int
    {
        return $this->all_products->where('is_active', true)->count();
    }

    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    public function isChildOf(Category $category): bool
    {
        $parent = $this->parent;

        while ($parent) {
            if ($parent->id === $category->id) {
                return true;
            }
            $parent = $parent->parent;
        }

        return false;
    }

    public function canBeParentOf(Category $category): bool
    {
        // Can't be parent of itself
        if ($this->id === $category->id) {
            return false;
        }

        // Can't be parent if the category is already an ancestor
        return !$this->isChildOf($category);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
            
            // Ensure unique slug
            $originalSlug = $category->slug;
            $count = 1;
            
            while (static::where('slug', $category->slug)->exists()) {
                $category->slug = $originalSlug . '-' . $count;
                $count++;
            }
        });

        static::updating(function ($category) {
            if ($category->isDirty('name') && empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }
}