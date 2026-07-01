<?php

namespace App\Models;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Spatie\Translatable\HasTranslations;

class Category extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;

    protected $table = "categories";

    protected $fillable = ['name', 'photo'];

    protected $translatable = ['name'];

    public function subCategory(): HasMany
    {
        return $this->hasMany(SubCategory::class, 'category_id');
    }

    public function userCategories(): HasMany
    {
        return $this->hasMany(UserCategory::class, 'category_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_categories')
            ->withPivot('subcategory_id');
    }

    public function getSubCategoriesAttribute()
    {
        return SubCategory::where('category_id', $this->id)->paginate(10);
    }

    public function ads(): MorphMany
    {
        return $this->morphMany(Ad::class, 'adable');
    }

    public function setPhotoAttribute($value): void
    {
        $this->attributes['photo'] = $value;
    }

    public function getPhotoAttribute(): string
    {
        return $this->attributes['photo'] ?? "";
    }

}
