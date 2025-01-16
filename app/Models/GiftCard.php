<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class GiftCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'amount', 'is_available', 'image'
    ];

    // You can define relationships if needed
    // public function transactions()
    // {
    //     return $this->hasMany(Transaction::class);
    // }

    public function orders()
    {
        return $this->hasMany(GiftCardOrder::class);
    }

    protected static function booted()
    {
        static::creating(function ($giftCard) {
            // Generate a nicely structured gift card code
            $giftCard->code = 'GC-' . Str::upper(Str::random(4)) . '-' . Str::upper(Str::random(4)) . '-' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        });
    }
}
