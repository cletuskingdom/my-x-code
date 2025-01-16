<?php

namespace App\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens;
    
    protected $fillable = ['f_name', 'l_name', 'email', 'phone',
    'temporary_token', 'refer_code', 'refer_by', 'language_code', 'dob', 'password', 'cm_firebase_token'];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}
