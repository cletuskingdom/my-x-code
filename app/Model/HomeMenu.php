<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomeMenu extends Model
{
    use HasFactory;

    public function category(){
        return $this->belongsTo(Category::class);
    }

    //define an accessor function for the image property
    public function getImageAttribute($value){
        return url('storage/app/public/category/menu/'.$value);
    }
}
