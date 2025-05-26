<?php

namespace App\Models;

use App\Models\Menu;
use App\Models\OrderItem;
use App\Traits\HasBranch;
use App\Models\ItemCategory;
use App\Models\MenuItemVariation;
use App\Models\MenuItemTranslation;
use Illuminate\Support\Facades\Cache;
use App\Scopes\AvailableMenuItemScope;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MenuItem extends Model
{
    use HasFactory, HasBranch, HasTranslations;


    const VEG = 'veg';
    const NONVEG = 'non-veg';
    const EGG = 'egg';

    protected $guarded = ['id'];

    protected $appends = [
        'item_photo_url',
    ];

    protected $with = ['translations'];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new AvailableMenuItemScope());
    }

    public function translations(): HasMany
    {
        return $this->hasMany(MenuItemTranslation::class, 'menu_item_id');
    }

    public function translation($locale = null): HasOne
    {
        return $this->hasOne(MenuItemTranslation::class)->where('locale', $locale ?? app()->getLocale());
    }

    public function getTranslatedValue(string $attribute, ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $cacheKey = "menu_item_{$this->id}_{$attribute}_{$locale}";

        return Cache::remember($cacheKey, 3600, function () use ($locale, $attribute) {
            $translation = $this->translation($locale)->first();
            return $translation?->{$attribute} ?? $this->attributes[$attribute] ?? '';
        });
    }

    public function getItemNameAttribute(): string
    {
        return $this->getTranslatedValue('item_name');
    }

    public function getDescriptionAttribute(): string
    {
        return $this->getTranslatedValue('description');
    }

    public function itemPhotoUrl(): Attribute
    {
        return Attribute::get(function (): string {
            return $this->image ? asset_url_local_s3('item/' . $this->image) : asset('img/food.svg');
        });
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'item_category_id');
    }

    public function variations(): HasMany
    {
        return $this->hasMany(MenuItemVariation::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function modifiers(): HasMany
    {
        return $this->hasMany(ItemModifier::class);
    }

    public function modifierGroups(): BelongsToMany
    {
        return $this->belongsToMany(ModifierGroup::class, 'item_modifiers', 'menu_item_id', 'modifier_group_id');
    }
}
