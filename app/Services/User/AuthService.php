<?php
namespace App\Services\User;

use App\User;
use App\SMS\SendOtp;
use Carbon\CarbonInterval;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use Illuminate\Support\Carbon;
use App\Services\NotificationService;
use Illuminate\Support\Facades\{DB, Mail};
use function App\CentralLogics\generateOTP;
use App\Model\{BusinessSetting, PhoneVerification};
use App\Mail\{EmailVerification, PasswordResetMail};


class AuthService
{   
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public static function register(Request $request){
        if ($request->referral_code) {
            $refer_user = User::where(['refer_code' => $request->referral_code])->first();
        }

        $temporary_token = Str::random(40);

        $user = User::create([
            'f_name' => Helpers::sanitize_input($request->f_name),
            'l_name' => Helpers::sanitize_input($request->l_name),
            'email' => Helpers::sanitize_input($request->email),
            'phone' => Helpers::sanitize_input($request->phone),
            'password' => bcrypt(Helpers::sanitize_input($request->password)),
            'temporary_token' => $temporary_token,
            'refer_code' => Helpers::generate_referer_code(),
            'refer_by' => $refer_user->id ?? null,
            'language_code' => Helpers::sanitize_input($request->header('X-localization')) ?? 'en',
            // 'dob'=>"1992-01-01",
            'dob'=>now(),
            // 'dob'=>Helpers::sanitize_input($request->date_of_birth),
        ]);

        $phone_verification = Helpers::get_business_settings('phone_verification');
        // $email_verification = Helpers::get_business_settings('email_verification');
        if ($phone_verification && !$user->is_phone_verified) {
            return response()->json(['token' => $temporary_token], 200);
        }
        // if ($email_verification && $user->email_verified_at == null) {
        //     return response()->json(['temporary_token' => $temporary_token], 200);
        // }


        ## send a welcoome mail

       $phone = $request['phone'];
        if (preg_match('/^\d{11}$/', $phone)) {
            // Check if the phone number starts with '0'
            if (strpos($phone, '0') === 0) {
                // Replace the first '0' with '234'
                $new_phone = $phone = '234' . substr($phone, 1);
                SendOtp::send($new_phone, 5);
            }
        } else {
            // If the number is not 11 digits, return an error or handle as needed
            return response()->json([
                'errors' => "Invalid phone number"
            ], 403);
        }
        $token = $user->createToken('RestaurantCustomerAuth')->accessToken;
        return response()->json(['token' => $token], 200);
    }

    public static function  check_phone(Request $request){
        $otp_interval_time= Helpers::get_business_settings('otp_resend_time') ?? 60;
        $otp_verification_data= DB::table('phone_verifications')->where('phone', $request['phone'])->first();

        if(isset($otp_verification_data) &&  Carbon::parse($otp_verification_data->created_at)->DiffInSeconds() < $otp_interval_time){
            $time= $otp_interval_time - Carbon::parse($otp_verification_data->created_at)->DiffInSeconds();

            $errors = [];
            $errors[] = [
                'code' => 'otp',
                'message' => translate('please_try_again_after_') . $time . ' ' . translate('seconds')
            ];
            return response()->json([
                'errors' => $errors
            ], 403);
        }

        $token =  generateOTP();

        DB::table('phone_verifications')->updateOrInsert(['phone' => $request['phone']], [
            'phone' => $request['phone'],
            'token' => $token,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::where(['phone' => $request['phone']])->first();
        if($user){
            // $response = NotificationService::sendOtp($request['phone'], $token);

            if (preg_match('/^\d{11}$/', $request['phone'])) {
                // Check if the phone number starts with '0'
                if (strpos($request['phone'], '0') === 0) {
                    // Replace the first '0' with '234'
                    $new_phone = $request['phone'] = '234' . substr($request['phone'], 1);
                    SendOtp::send($new_phone, 5);
                    return self::check_email($user->email, $token);
                }
            } else {
                // If the number is not 11 digits, return an error or handle as needed
                return response()->json([
                    'errors' => "Invalid phone number"
                ], 403);
            }
        }else{
            return response()->json([
                'errors' => "Not found"
            ], 403);
        }

    }

    public static function verify_phone(Request $request){
        $max_otp_hit = Helpers::get_business_settings('maximum_otp_hit') ?? 5;
        $max_otp_hit_time = Helpers::get_business_settings('otp_resend_time') ?? 60;// seconds
        $temp_block_time = Helpers::get_business_settings('temporary_block_time') ?? 600; // seconds

        $verify = PhoneVerification::where(['phone' => $request['phone'], 'token' => $request['token']])->first();

        if ($verify) {
            if(isset($verify->temp_block_time ) && Carbon::parse($verify->temp_block_time)->DiffInSeconds() <= $temp_block_time){
                $time = $temp_block_time - Carbon::parse($verify->temp_block_time)->DiffInSeconds();

                $errors = [];
                $errors[] = ['code' => 'otp_block_time',
                    'message' => translate('please_try_again_after_') . CarbonInterval::seconds($time)->cascade()->forHumans()
                ];
                return response()->json([
                    'errors' => $errors
                ], 403);
            }
            $user = User::where(['phone' => $request['phone']])->first();
            $user->is_phone_verified = 1;
            $user->email_verified_at = time();
            $user->save();

            $verify->delete();

            $token = $user->createToken('RestaurantCustomerAuth')->accessToken;

            return response()->json(['message' => translate('OTP verified!'), 'token' => $token, 'status' => true], 200);
        }
        else{
            $verification_data= DB::table('phone_verifications')->where('phone', $request['phone'])->first();

            if(isset($verification_data)){
                if(isset($verification_data->temp_block_time ) && Carbon::parse($verification_data->temp_block_time)->DiffInSeconds() <= $temp_block_time){
                    $time= $temp_block_time - Carbon::parse($verification_data->temp_block_time)->DiffInSeconds();
                    $errors = [];
                    $errors[] = ['code' => 'otp_block_time',
                        'message' => translate('please_try_again_after_') . CarbonInterval::seconds($time)->cascade()->forHumans()
                    ];
                    return response()->json([
                        'errors' => $errors
                    ], 403);
                }

                if($verification_data->is_temp_blocked == 1 && Carbon::parse($verification_data->updated_at)->DiffInSeconds() >= $temp_block_time){
                    DB::table('phone_verifications')->updateOrInsert(['phone' => $request['phone']],
                        [
                            'otp_hit_count' => 0,
                            'is_temp_blocked' => 0,
                            'temp_block_time' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                }

                if($verification_data->otp_hit_count >= $max_otp_hit &&  Carbon::parse($verification_data->updated_at)->DiffInSeconds() < $max_otp_hit_time &&  $verification_data->is_temp_blocked == 0){

                    DB::table('phone_verifications')->updateOrInsert(['phone' => $request['phone']],
                        [
                            'is_temp_blocked' => 1,
                            'temp_block_time' => now(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                    $time= $temp_block_time - Carbon::parse($verification_data->temp_block_time)->DiffInSeconds();
                    $errors = [];
                    $errors[] = ['code' => 'otp_temp_blocked', 'message' => translate('Too_many_attempts. please_try_again_after_'). CarbonInterval::seconds($time)->cascade()->forHumans()];
                    return response()->json([
                        'errors' => $errors
                    ], 403);
                }

            }

            DB::table('phone_verifications')->updateOrInsert(['phone' => $request['phone']],
                [
                    'otp_hit_count' => DB::raw('otp_hit_count + 1'),
                    'updated_at' => now(),
                    'temp_block_time' => null,
                ]);
        }

        return response()->json(['errors' => [
            ['code' => 'token', 'message' => translate('OTP is not matched!')]
        ]], 403);
    }

    public static function reset_password_request(Request $request){
        $customer = User::where(['email' => $request['email_or_phone']])
            ->orWhere('phone', 'like', "%{$request['email_or_phone']}%")
            ->first();

        
        // $send_by_phone = Helpers::get_business_settings('phone_verification');

        if (isset($customer)) {
            $otp_interval_time= Helpers::get_business_settings('otp_resend_time') ?? 60; // seconds
            $password_verification_data= DB::table('password_resets')->where('email_or_phone', $request['email_or_phone'])->first();

            if(isset($password_verification_data) &&  Carbon::parse($password_verification_data->created_at)->DiffInSeconds() < $otp_interval_time){
                $time= $otp_interval_time - Carbon::parse($password_verification_data->created_at)->DiffInSeconds();

                $errors = [];
                $errors[] = [
                    'code' => 'otp',
                    'message' => translate('please_try_again_after_') . $time . ' ' . translate('seconds')
                ];

                return response()->json([
                    'errors' => $errors
                ], 403);
            }

             $token =  generateOTP();;

            DB::table('password_resets')->updateOrInsert(['email_or_phone' => $request['email_or_phone']], [
                'token' => $token,
                'created_at' => now(),
            ]);

            
            Mail::to($customer['email'])->send(new PasswordResetMail($token, $customer));
            SendOtp::sendExistingOtp($customer['phone'], $token);
            return response()->json(['message' => 'Success'], 200);
        }

        return response()->json(['errors' => [
            ['code' => 'not-found', 'message' => translate('Customer not found!')]
        ]], 404);
    }

    public static function check_email($email, $token) {
        // if (BusinessSetting::where(['key' => 'email_verification'])->first()->value) {

        $otp_interval_time= Helpers::get_business_settings('otp_resend_time') ?? 60;// seconds
        $otp_verification_data= DB::table('email_verifications')->where('email', $email)->first();

        if(isset($otp_verification_data) &&  Carbon::parse($otp_verification_data->created_at)->DiffInSeconds() < $otp_interval_time){
            $time= $otp_interval_time - Carbon::parse($otp_verification_data->created_at)->DiffInSeconds();

            $errors = [];
            $errors[] = [
                'code' => 'otp',
                'message' => translate('please_try_again_after_') . $time . ' ' . translate('seconds')
            ];
            return response()->json([
                'errors' => $errors
            ], 403);
        }

        DB::table('email_verifications')->updateOrInsert(['email' => $email], [
            'email' => $email,
            'token' => $token,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            $lang_code ='en';
            $emailServices = Helpers::get_business_settings('mail_config');
            $mail_status = Helpers::get_business_settings('registration_otp_mail_status_user');

            // if(isset($emailServices['status']) && $emailServices['status'] == 1){
                Mail::to($email)->send(new EmailVerification($token, $lang_code ));
            // }

        } catch (\Exception $exception) {

            return $exception;

            return response()->json([
                'errors' => [
                    ['code' => 'otp', 'message' => translate('Token sent failed!')]
                ],
                'track_trace'=> $exception
            ], 403);

        }

        return response()->json([
            'message' => translate('Email and phone is ready to register'),
            'token' => 'active'
        ], 200);
    }
}
