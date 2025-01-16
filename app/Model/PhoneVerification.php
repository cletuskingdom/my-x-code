<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class PhoneVerification extends Model
{
    protected $fillable = ['phone', 'token', 'otp_hit_count', 'is_temp_blocked', 'temp_bock_time'];
}
