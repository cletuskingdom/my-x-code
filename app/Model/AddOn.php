<?php

namespace App\Model;

use App\CentralLogics\Helpers;
use App\Models\AddonCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class AddOn extends Model
{
    protected $casts = [
        'price' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function getPriceAttribute($price): float
    {
        return (float)Helpers::set_price($price);
    }

    public function translations(): MorphMany
    {
        return $this->morphMany('App\Model\Translation', 'translationable');
    }

    public function getNameAttribute($name)
    {
        if (strpos(url()->current(), '/admin')) {
            return $name;
        }
        return $this->translations[0]->value ?? $name;
    }

    protected static function booted()
    {
        static::addGlobalScope('translate', function (Builder $builder) {
            $builder->with(['translations' => function ($query) {
                return $query->where('locale', app()->getLocale());
            }]);
        });
    }
    public function getImageAttribute($value){
        return url('storage/app/public/addon/'.$value);
    }

    // public function getCategoryIdAttribute($value){
    //     $cat = AddonCategory::where('id',$value)->first();
    //     return $cat->name;
    // }

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(AddonCategory::class);
    }
}
