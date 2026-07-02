<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\SettingKey;
use App\Enums\TransactionType;
use App\Enums\UserRole;
use App\Http\Resources\CategoryResource;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\HasApiTokens;
use Spatie\ModelStatus\HasStatuses;


class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasStatuses;

    protected $table = "users";
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "name", "email", "phone", "verification_code", "is_verified", "password",
        "role", "profile_photo", "dob", "gender", "completed_profile", "lang", "wallet", "city",
        "latitude", "longitude", "vat_number", "cr_number", "fcm_token", "platform_fees",
        "reason", 'facebook', 'instagram', 'youtube', 'snapchat','whatsapp',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_admin' => 'boolean',
    ];

    /**
     * @return mixed
     */
    public function routeNotificationForFcm()
    {
        return $this->fcm_token;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // [SECURITY] Only explicit admins may access the Filament panel (A1). Previously returned
        // true for EVERY authenticated user (any client/artist could reach /admin and read/write
        // all data). `is_admin` is intentionally NOT mass-assignable. See docs/SECURITY_ISSUES.md A1.
        return (bool) $this->is_admin;
    }

    public function userCategories(): HasMany
    {
        return $this->hasMany(UserCategory::class, "user_id");
    }

    public function userCategoriesList(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'user_categories')
            ->withPivot('subcategory_id')
            ->withTimestamps();
    }

    public function works(): HasMany
    {
        return $this->hasMany(ArtistGallery::class, "user_id");
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class, "artist_id");
    }

    public function supports(): HasMany
    {
        return $this->hasMany(Support::class, "user_id");
    }

    public function activeSupport()
    {
        return $this->supports()->where('is_complete', 0);
    }

    public function cityRelation(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function dates(): HasMany
    {
        return $this->hasMany(UserDate::class, 'user_id');
    }

    public function ads(): MorphMany
    {
        return $this->morphMany(Ad::class, 'adable');
    }

    public function artistOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'artist_id');
    }

    public function artistCompletedOrders(): HasMany
    {
        return $this->artistOrders()->whereHas('statuses', fn($query) => $query->where('name', OrderStatus::COMPLETED->value));
    }

    public function clientOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'client_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'user_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'user_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'user_categories')
            ->withPivot('subcategory_id');
    }

    public function getTotalIncomeAttribute()
    {
        return $this->transactions->where('type', TransactionType::INCOME->value)->sum('amount');
    }

    public function getTotalWithdrawAttribute()
    {
        // [BUG] Was filtering on 'is-completed' (hyphen) — a key no Transaction has — so this
        // ALWAYS returned 0 and the withdrawal balance check (TransactionService) was bypassable.
        // Count every withdrawal request (pending + completed) as committed against the balance,
        // which also stops stacking multiple pending requests. See docs/CODE_REVIEW_FINDINGS.md B1.
        return $this->transactions
            ->where('type', TransactionType::WITHDRAW->value)
            ->sum('amount');
    }


    public function setPasswordAttribute($value): void
    {
        $this->attributes["password"] = Hash::make($value);
    }

    public function setProfilePhotoAttribute($value): void
    {
        $this->attributes['profile_photo'] = $value;
    }

    public function getProfilePhotoStringAttribute(): string
    {
        if ($this->attributes['profile_photo'])
            return Storage::url($this->attributes['profile_photo']);
        else
            return '/images/logo-gold.png';
    }

    public function getRatingValueAttribute()
    {
        return $this->ratings()->avg('stars');
    }

    public function getCategoriesNamesAttribute()
    {
        return $this->userCategories->map(function ($item) {
            return $item->category?->name;
        })->implode(' | ');
    }

    public function getSubcategoriesNamesAttribute()
    {
        return $this->userCategories->map(function ($item) {
            return $item->subcategory?->name;
        })->implode(' | ');
    }

    public function getRandomRangeAttribute()
    {
        return $this->userCategories()->inRandomOrder()->first()?->priceRange;
    }

    public function getPhonePrefixAttribute(): string
    {
        return "+966";
    }

    public function getCategoriesListAttribute()
    {
        return $this->userCategories->groupBy('category_id')->map(function ($group) {
            return [
                'category' => new CategoryResource($group->first()?->category),
                'subcategories' => $group->map(function ($item) {
                    return [
                        'subcategory_id' => $item->subcategory->id,
                        'subcategory_name' => $item->subcategory->name,
                        'range' => $item->priceRange,
                    ];
                })->unique('subcategory_id')->values()
            ];
        })->values();
    }

    public function getPlatformFeesAttribute()
    {
        return $this->attributes['platform_fees'] ?? Setting::where('type', SettingKey::PLATFORM_FEES->value)->first()?->value;
    }

    /**
     * @param $query
     * @return void
     */
    public function scopeClient($query): void
    {
        $query->where('role', UserRole::CLIENT->value)->where('completed_profile', true)->orderByDesc('created_at');
    }

    /**
     * @param $query
     * @return void
     */
    public function scopeArtist($query): void
    {
        $query->where('role', UserRole::ARTIST->value)->orderByDesc('created_at');
    }

    /**
     * @param Builder $query
     * @param $value
     * @return Builder
     */
    public function scopeCategory(Builder $query, $value): Builder
    {
        return $query->whereHas('userCategories', function ($query) use ($value) {
            $query->where('category_id', $value)
                ->orWhere('subcategory_id', $value)
                ->orWhereHas('category', fn($query) => $query->where('name', $value))
                ->orWhereHas('subcategory', fn($query) => $query->where('name', $value));
        });
    }
}
