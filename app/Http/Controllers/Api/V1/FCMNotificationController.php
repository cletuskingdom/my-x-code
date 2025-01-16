<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class FCMNotificationController extends Controller
{
    public function send($user_id)
    {
        $validator = Validator::make(['user_id' => $user_id], [
            'user_id' => 'required|integer|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        
        // Call the notifyKitchenFirstTimeCustomer function
        $response = Helpers::notifyKitchenFirstTimeCustomer($user_id);

        // Handle the response
        if ($response['status'] == 'success') {
            return response()->json($response['message']); 
        } else {
            return response()->json($response);
        }
    }

}
