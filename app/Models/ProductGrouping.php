<?php

namespace App\Models;

use App\Model\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductGrouping extends Model
{
    use HasFactory;

    public function products(){
        return $this->hasMany(Product::class, 'grouping_id', 'id');
    }
}
