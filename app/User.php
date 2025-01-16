<?php

namespace App;

use App\Model\Branch;
use App\Model\{ChefBranch, CustomerAddress, Order};
use App\Models\Otp;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'f_name', 'l_name', 'phone', 'email', 'password', 'point', 'is_active', 'user_type', 'refer_code', 'refer_by', 'language_code', 'dob'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_phone_verified' => 'integer',
        'point' => 'integer',
    ];

    /* protected $appends = [ 'branch_id' ];*/

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class, 'user_id');
    }

    public function chefBranch(): HasOne
    {
        return $this->hasOne(ChefBranch::class, 'user_id', 'id');
    }

    public static function get_chef_branch_name($chef)
    {
        $branch = DB::table('chef_branch')->where('user_id', $chef->id)->get();
        foreach ($branch as $value) {
            $branch_name = Branch::where('id', $value->branch_id)->get();
            foreach ($branch_name as $bn) {
                return $bn->name;
            }
        }
    }

    /*public function getBranchIdAttribute()
    {
            $chef = DB::table('chef_branch')->where('user_id', auth()->user()->id)->first('branch_id');
            if (isset($chef)){
                $branch = Branch::where('id', $chef->branch_id)->first();
                return $branch->id;
            }


    }*/

    public function scopeOfType($query, $user_type)
    {
        if ($user_type != 'customer') {
            return $query->where('user_type', $user_type);
        }
    }

    public function otps()
    {
        return $this->morphMany(Otp::class, 'otpable');
    }

    public function email_verification_otp()
    {
        return $this->morphMany(Otp::class, 'otpable')->latest()->where('purpose', 'email_verification');
    }

    public function phone_verification_otp()
    {
        return $this->morphMany(Otp::class, 'otpable')->latest()->where('purpose', 'phone_verification');
    }

    public function password_reset_otp()
    {
        return $this->morphMany(Otp::class, 'otpable')->latest()->where('purpose', 'password_reset');
    }

    public function password_change_otp()
    {
        return $this->morphMany(Otp::class, 'otpable')->latest()->where('purpose', 'password_change');
    }


}
