<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Setting extends Model
{
    use HasFactory, HasTranslations;

    protected $table = "settings";

    protected $fillable = ["type", 'value'];

    protected $translatable = [
        "value"
    ];

    /**
     * Get the translatable attributes for the model.
     *
     * @return array
     */
    public function getTranslatableAttributes(): array
    {
        return $this->translatable;
    }

    public function getTypeStringAttribute()
    {
        return trans('app.setting.' . $this->attributes['type']);
    }

    public function getValueEnAttribute(): string
    {
        if (isset($this->attributes['value']['en']))
            return $this->attributes['value']['en'];
        return "";
    }

    public function getValueArAttribute(): string
    {
        if (isset($this->attributes['value']['ar']))
            return $this->attributes['value']['ar'];
        return "";
    }

}
