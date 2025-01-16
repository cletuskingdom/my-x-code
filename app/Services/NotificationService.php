<?php
namespace App\Services;

use App\User;
use App\Models\Otp;
use App\SMS\SendOtp;

use function App\CentralLogics\generateOTP;

class NotificationService
{
    public static function saveOtp(User $user, String $purpose){
        $otp = new Otp();
        $otp->otpable_id = $user->id;
        $otp->otpable_type = get_class($user);
        $otp->otp = generateOTP();
        $otp->purpose = $purpose;
        $otp->save();
    }

}
