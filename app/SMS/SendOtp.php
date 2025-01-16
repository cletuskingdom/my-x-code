<?php

namespace App\SMS;

use App\Models\SmsLog;
use Twilio\TwiML\Voice\Number;
use App\Model\PhoneVerification;
use Zeevx\LaraTermii\LaraTermii;
use Illuminate\Support\Facades\Log;

class SendOtp
{
    public static function send(int $phone_number, int $code_length)
    {

        function generatePIN($code_length)
        {
            $pin = '';
            for ($i = 0; $i < $code_length; $i++) {
                $pin .= rand(0, 9);
            }
            return $pin;
        }

        $code = generatePIN($code_length);
        ## save this code in the db

        try {
            $termii = new LaraTermii(env('TERMII_API_TOKEN'));

            if (strpos($phone_number, '234') === 0) {
                // Replace the first '234' with '0'
                $save_to_db = '0' . substr($phone_number, 3);

                PhoneVerification::create([
                    'phone' => $save_to_db,
                    'token' => $code,
                ]);
                $send_otp = $termii->sendMessage(
                    $phone_number,
                    'N-Alert',
                    "Your   confirmation code is " . $code . ", this is for one-time use only.",
                    "dnd",
                    false,
                    null,
                    null
                );

                Log::info($send_otp);
                return response()->json([
                    'message' => "Sent"
                ]);
            }
            return response()->json([
                'message' => "Not sent"
            ]);

        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return response()->json([
                'message' => $th->getMessage()
            ]);
        }
    }

    public static function sendExistingOtp(int $phone_number, int $OTP)
    {

        try {
            $termii = new LaraTermii(env('TERMII_API_TOKEN'));
            $instance = new self();
            $formattedPhone = $instance->formatPhoneNumber($phone_number);

            $send_otp = $termii->sendMessage(
                $formattedPhone,
                'N-Alert',
                "Your   confirmation code is " . $OTP . ", this is for one-time use only.",
                "dnd",
                false,
                null,
                null
            );

            Log::info($send_otp);
            return response()->json([
                'message' => "Sent"
            ]);
            

        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return response()->json([
                'message' => $th->getMessage()
            ]);
        }
    }




    protected function formatPhoneNumber(int $phoneNumber): string
    {
        // Check if the number starts with '234' or '+234', if not, add it
        if (strpos($phoneNumber, '234') !== 0) {
            return '234' . ltrim($phoneNumber, '0'); // Remove leading 0 if present and add '234'
        }
        return $phoneNumber;
    }


}