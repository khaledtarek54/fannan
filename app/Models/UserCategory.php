<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCategory extends Model
{
    use HasFactory;

    protected $table = "user_categories";

    public $timestamps = false;

    protected $fillable = ["user_id", "category_id", "subcategory_id", "range_id"];


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, "user_id");
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, "category_id", 'id')->withTrashed();
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class, "subcategory_id", 'id');
    }

    public function priceRange()
    {
        return $this->belongsTo(PriceRange::class, 'range_id')->withTrashed();
    }
}
