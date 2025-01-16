<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class BusinessSetting extends Model
{
    protected $fillable = ['key', 'value'];
    
    public function translations(): MorphMany
    {
        return $this->morphMany('App\Model\Translation', 'translationable');
    }
}
