<?php

namespace App\Http\Controllers\Api\V1;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Mail\CateringRequest;
use App\Model\CustomerAddress;
use App\Model\Newsletter;
use App\Model\Order;
use App\Model\OrderDetail;
use App\Model\PhoneVerification;
use App\Model\PointTransitions;
use App\Model\Product;
use App\Model\ProductTwo;
use App\Models\GuestUser;
use App\SMS\SendOtp;
use App\User;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    public function __construct(
        private CustomerAddress  $customer_address,
        private Order            $order,
        private OrderDetail      $order_detail,
        private User             $user,
        private PointTransitions $point_transitions,
        private Newsletter       $newsletter,
        private GuestUser       $guest_user,
    )
    {
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function address_list(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'guest_id' => auth('api')->user() ? 'nullable' : 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $user_id = (bool)auth('api')->user() ? auth('api')->user()->id : $request['guest_id'];
        $user_type = (bool)auth('api')->user() ? 0 : 1;

        return response()->json($this->customer_address->where(['user_id' => $user_id, 'is_guest' => $user_type])->latest()->get(), 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function add_new_address(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'contact_person_name' => 'required',
            'address_type' => 'required',
            'contact_person_number' => 'required',
            'address' => 'required',
            'guest_id' => auth('api')->user() ? 'nullable' : 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $user_id = (bool)auth('api')->user() ? auth('api')->user()->id : $request['guest_id'];
        $user_type = (bool)auth('api')->user() ? 0 : 1;

        $this->customer_address->insert([
            //'user_id' => $request->user()->id,
            'user_id' => $user_id,
            'is_guest' => $user_type,
            'contact_person_name' => $request->contact_person_name,
            'contact_person_number' => $request->contact_person_number,
            'floor' => $request->floor,
            'house' => $request->house,
            'road' => $request->road,
            'address_type' => $request->address_type,
            'address' => $request->address,
            'longitude' => $request->longitude,
            'latitude' => $request->latitude,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => translate('added_success')], 200);
    }

    /**
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function update_address(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'contact_person_name' => 'required',
            'address_type' => 'required',
            'contact_person_number' => 'required',
            'address' => 'required',
            'guest_id' => auth('api')->user() ? 'nullable' : 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $user_id = (bool)auth('api')->user() ? auth('api')->user()->id : $request['guest_id'];
        $user_type = (bool)auth('api')->user() ? 0 : 1;

        $this->customer_address->where('id', $id)->update([
            'user_id' => $user_id,
            'is_guest' => $user_type,
            'contact_person_name' => $request->contact_person_name,
            'contact_person_number' => $request->contact_person_number,
            'floor' => $request->floor,
            'house' => $request->house,
            'road' => $request->road,
            'address_type' => $request->address_type,
            'address' => $request->address,
            'longitude' => $request->longitude,
            'latitude' => $request->latitude,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => translate('update_success')], 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function delete_address(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'address_id' => 'required',
            'guest_id' => auth('api')->user() ? 'nullable' : 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $user_id = (bool)auth('api')->user() ? auth('api')->user()->id : $request['guest_id'];
        $user_type = (bool)auth('api')->user() ? 0 : 1;

        if ($this->customer_address->where(['id' => $request['address_id'], 'user_id' => $user_id, 'is_guest' => $user_type])->first()) {
            $this->customer_address->where(['id' => $request['address_id'], 'user_id' => $user_id, 'is_guest' => $user_type])->delete();
            return response()->json(['message' => translate('successfully removed!')], 200);
        }

        return response()->json(['message' => translate('no_data_found')], 404);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function get_order_list(Request $request): JsonResponse
    {
        $orders = $this->order->where(['user_id' => $request->user()->id])->get();
        return response()->json($orders, 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function get_order_details(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $details = $this->order_detail->where(['order_id' => $request['order_id']])->get();
        foreach ($details as $det) {
            $det['product_details'] = Helpers::product_data_formatting(json_decode($det['product_details'], true));
        }

        return response()->json($details, 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function info(Request $request): JsonResponse
    {
        return response()->json($request->user(), 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function update_profile(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'f_name' => 'required',
            'l_name' => 'required',
            'phone' => 'required',
        ], [
            'f_name.required' => translate('first_name_required'),
            'l_name.required' => translate('last_name_required'),
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        if ($request['password'] != null && strlen($request['password']) > 5) {
            $pass = bcrypt($request['password']);
        } else {
            $pass = $request->user()->password;
        }

        $userDetails = [
            'f_name' => $request->f_name,
            'l_name' => $request->l_name,
            'phone' => $request->phone,
            'image' => $request->has('image') ? Helpers::update('profile/', $request->user()->imagee, 'png', $request->file('image')) : $request->user()->image,
            'password' => $pass,
            'updated_at' => now(),
        ];

        $this->user->where(['id' => $request->user()->id])->update($userDetails);

        return response()->json(['message' => translate('update_success')], 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function update_cm_firebase_token(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'cm_firebase_token' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $user = auth('api')->user();
        $guest = $request['guest_id'];

        if (isset($user) && isset($guest)){
            $this->user->where('id', auth('api')->user()->id )->update([
                'cm_firebase_token' => $request['cm_firebase_token'],
                'language_code' => $request->header('X-localization') ?? 'en'
            ]);

            $this->guest_user->where('id', $request['guest_id'])->update([
                'fcm_token' => '@',
            ]);

        }elseif(isset($user)){
            $this->user->where('id', auth('api')->user()->id)->update([
                'cm_firebase_token' => $request['cm_firebase_token'],
                'language_code' => $request->header('X-localization') ?? 'en'
            ]);

        }elseif(isset($guest)){
            $this->guest_user->where('id',  $request['guest_id'])->update([
                'fcm_token' => $request['cm_firebase_token'],
            ]);
        }

        return response()->json(['message' => translate('update_success')], 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function get_transaction_history(Request $request): JsonResponse
    {
        try {
            return response()->json($this->point_transitions->latest()->where(['user_id' => $request->user()->id])->get(), 200);

        } catch (\Exception $e) {
            return response()->json([], 200);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function subscribe_newsletter(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $newsLetter = $this->newsletter->where('email', $request->email)->first();
        if (!isset($newsLetter)) {
            $newsLetter = $this->newsletter;
            $newsLetter->email = $request->email;
            $newsLetter->save();

            return response()->json(['message' => translate('Successfully subscribed')], 200);

        } else {
            return response()->json(['message' => translate('Email Already exists')], 400);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function remove_account(Request $request): JsonResponse
    {
        $customer = $this->user->find($request->user()->id);

        if (isset($customer)) {
            Helpers::file_remover('customer/', $customer->image);
            $customer->delete();

        } else {
            return response()->json(['status_code' => 404, 'message' => translate('Not found')], 200);
        }

        return response()->json(['status_code' => 200, 'message' => translate('Successfully deleted')], 200);
    }

    public function update_phone(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'unique:users,phone'],
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $phone = $request->phone;
        if (preg_match('/^\d{11}$/', $phone)) {
            // Check if the phone number starts with '0'
            if (strpos($phone, '0') === 0) {
                // Replace the first '0' with '234'
                $new_phone = $phone = '234' . substr($phone, 1);
                SendOtp::send($new_phone, 5);
                return response()->json([
                    'status_code' => 200,
                    'message' => 'OTP has been sent to ' . $request->phone,
                ]);
            }
        } else {
            // If the number is not 11 digits, return an error or handle as needed
            return response()->json([
                'errors' => "Invalid phone number"
            ], 403);
        }
    }

    public function update_phone_otp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'otp' => ['required'],
            'phone' => ['required'],
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $verify = PhoneVerification::where(['phone' => $request->phone, 'token' => $request->otp])->first();
        if ($verify) {
            User::whereId(auth()->id())->update([
                'phone' => $verify->phone
            ]);
            $verify->delete();
            
            return response()->json([
                'status' => true, 
                'message' => "Phone number updated successfully",
            ], 200);
        }else{
            return response()->json([
                'status' => false,  
                'message' => "Something went wrong, wrong credentials",
            ], 400);
        }
    }

    public function catering_service_store(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'message' => ['required'],
        ]);
        
        // Return validation errors if any
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        try {
            // Prepare email details
            $details = [
                'email' => $request->email,
                'message' => $request->message
            ];

            $subject = 'Catering Request from ' . $request->email;

            // Send the email
            Mail::to(Helpers::sanitize_input('catering@eat .com'))->send(new CateringRequest($details, $subject));
            // $request->email
            
            // Return a success response
            return response()->json([
                'status_code' => 200,
                'message' => "Email sent successfully!"
            ], 200);

        } catch (\Throwable $th) {
            // Return error response if an exception occurs
            return response()->json([
                'status_code' => 500,
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function getSuggestions($id)
    {
        // Convert the comma-separated list of IDs into an array
        $productIds = explode(',', $id);

        // Custom error messages
        $messages = [
            'product_ids.*.exists' => 'One or more product IDs do not exist in our records.',
            'product_ids.*.integer' => 'Each product ID must be a valid integer.',
            'product_ids.required' => 'Product IDs are required.',
            'product_ids.array' => 'Product IDs must be an array of IDs.',
        ];

        // Validate the product IDs with custom messages
        $validator = Validator::make(['product_ids' => $productIds], [
            'product_ids' => 'required|array',
            'product_ids.*' => 'integer|exists:products,id',
        ], $messages);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // Fetch and decode the category IDs of the provided products
        $categoryIds = Product::whereIn('id', $productIds)
            ->pluck('category_ids')
            ->flatMap(function ($categoryJson) {
                // Decode the JSON and extract the 'id' values
                $categories = json_decode($categoryJson, true);
                return collect($categories)->pluck('id');
            })
            ->unique()
            ->toArray();

        // Fetch products that have the same category IDs and exclude the original product IDs
        $suggestions = Product::whereNotIn('id', $productIds)
            ->where(function ($query) use ($categoryIds) {
                foreach ($categoryIds as $categoryId) {
                    $query->orWhereJsonContains('category_ids', ['id' => (string)$categoryId]);
                }
            })
            ->limit(10)
            ->get();
        return response()->json($suggestions);
    }

    public function getSpecialSuggestions($id)
    {
        if($id != 1 && $id != 2 && $id != 3){
            return response()->json(['errors' => "ID not recognized."], 400);
        }

        if($id == 1){
            // 1 - Breakfast
            $suggestions = ProductTwo::where("grouping_id", "!=", 2)
                ->whereIn('id', [153, 154, 158, 159, 148])->get();
        }elseif($id == 2){
            // 2 - Lunch
            $suggestions = ProductTwo::where("grouping_id", "!=", 2)
                ->whereIn('id', [8, 55, 26, 165])->get();
        }else{
            // 3 - Dinner
            $suggestions = ProductTwo::where("grouping_id", "!=", 2)
                ->whereIn('id', [75, 45, 46, 41, 12])->get();
        }       
        return response()->json($suggestions);
    }
}
