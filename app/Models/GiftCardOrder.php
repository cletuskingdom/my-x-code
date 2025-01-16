<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GiftCardOrder extends Model
{
    use HasFactory;
    protected $fillable = [
        'from_user', 'to_user', 'gift_card_id', 'amount_paid', 'status', 'is_redeemed'
    ];

    public function giftCard()
    {
        return $this->belongsTo(GiftCard::class);
    }

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user');
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user');
    }
}
