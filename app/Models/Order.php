<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\UserRole;
use App\Http\Resources\OrderCategoryResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\ModelStatus\HasStatuses;
use function Livewire\off;

class Order extends Model
{
    use HasFactory, HasStatuses, SoftDeletes;

    protected $fillable = [
        'type', 'name', 'number', 'client_id', 'artist_id', 'address_id',
        'description', 'cost', 'updated_budget', 'coupon_id', 'coupon_amount', 'is_paid',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'artist_id');
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'address_id');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(OrderCategory::class, 'order_id');
    }

    public function dates(): HasMany
    {
        return $this->hasMany(OrderDate::class, 'order_id');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(OrderOffer::class, 'order_id');
    }

    public function biddingOrderArtists(): HasMany
    {
        return $this->hasMany(BiddingOrderArtist::class, 'order_id');
    }

    public function userTransaction(): HasMany
    {
        return $this->hasMany(UserTransaction::class, 'order_id');
    }

    public function acceptedBiddingOrderArtists(): HasMany
    {
        return $this->biddingOrderArtists()->whereHas('statuses', function ($query) {
            $query->where('name', OrderStatus::ACCEPTED->value);
        })->where('is_accepted', 1);
    }


    public function rating(): MorphOne
    {
        return $this->morphOne(Rating::class, 'model');
    }

    public function transaction(): MorphOne
    {
        return $this->morphOne(Transaction::class, 'model');
    }

    public function supports(): MorphMany
    {
        return $this->morphMany(Support::class, 'model');
    }

    public function getStatusValueAttribute()
    {
        $offer = $this->offers->last();
        if ($this->status == OrderStatus::ARTIST_PENDING->value && !$offer)
            return $this->latestStatus()?->name;
        elseif ($this->status == OrderStatus::ARTIST_PENDING->value && $offer)
            return OrderStatus::COUNTER_OFFER->value;
        else
            return $this->status;
    }

    public function getStatusReasonAttribute()
    {
        return $this->latestStatus()?->reason;
    }

    public function getStatusTextAttribute()
    {
        $offer = $this->offers->last();
        if ($this->status == OrderStatus::ARTIST_PENDING->value && !$offer)
            return auth()->user()->role == UserRole::CLIENT->value ? trans('app.' . OrderStatus::ARTIST_PENDING->value) : trans('app.' . OrderStatus::NEW->value);
        elseif ($this->status == OrderStatus::ARTIST_PENDING->value && $offer)
            return trans('app.' . OrderStatus::COUNTER_OFFER->value);
        else
            return trans('app.' . $this->status);

    }

    public function getCostValueAttribute()
    {
        return $this->offers->last()?->cost ?? $this->cost;
    }

    public function getTotalCostAttribute()
    {
        $cost = 0;
        if ($this->attributes['type'] == OrderType::DIRECT->value) {
            $lastOffer = $this->offers->last();
            if ($lastOffer)
                $cost = $lastOffer->cost;
            else
                $cost = $this->cost;
        } else {
            $cost = $this->acceptedBiddingOrderArtists()->get()->sum('cost');
        }
        return $cost;
    }

    public function getSubcategoriesTextAttribute()
    {
        return $this->categories->map(function ($item) {
            return $item->subcategory?->name . ", ";
        });
    }

    public function getImageAttribute()
    {
        return $this->categories->first()?->subcategory?->category?->photo;
    }

    public function getHoursCountAttribute()
    {
        $dates = $this->dates;
        return $dates->reduce(function ($carry, $date) {
            $start = Carbon::parse($date->start_time);
            $end = Carbon::parse($date->end_time);
            return $carry + $start->diffInHours($end);
        }, 0);
    }

    public function getIsCompleteAttribute()
    {
        $lastDate = $this->dates->last();
        $currentDateTime = Carbon::now();
        $endDate = Carbon::parse($lastDate?->end_date . " " . $lastDate?->end_time);
        return $currentDateTime->greaterThan($endDate);
    }

    public function scopeStatus(Builder $query, $value)
    {
        if ($value && $value == OrderStatus::COMPLETED->value) {
            return $query->currentStatus($value);
        } else {
            return $query->whereHas('biddingOrderArtists', function ($query) {
                $query->whereHas('statuses', function ($query) {
                    $query->where('name', OrderStatus::ACCEPTED->value);
                });
            })->orWhereHas('statuses', function ($query) {
                $query->whereNotIn('name', [OrderStatus::COMPLETED->value, OrderStatus::REJECTED->value]);
            });
        }
    }

    public function scopeCity(Builder $query, $value): Builder
    {
        return $query->whereHas('address', function ($query) use ($value) {
            $query->where('city_id', $value);
        });
    }
}
