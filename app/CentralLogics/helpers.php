<?php

namespace App\CentralLogics;

use App\CPU\ImageManager;
use App\Model\AddOn;
use App\Model\BusinessSetting;
use App\Model\Currency;
use App\Model\DMReview;
use App\Model\Order;
use App\Model\Product;
use App\Model\ProductByBranch;
use App\Model\Review;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Validator;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\Notification;

class Helpers
{
    public static function error_processor($validator)
    {
        $err_keeper = [];
        foreach ($validator->errors()->getMessages() as $index => $error) {
            array_push($err_keeper, ['code' => $index, 'message' => $error[0]]);
        }
        return $err_keeper;
    }

    public static function combinations($arrays)
    {
        $result = [[]];
        foreach ($arrays as $property => $property_values) {
            $tmp = [];
            foreach ($result as $result_item) {
                foreach ($property_values as $property_value) {
                    $tmp[] = array_merge($result_item, [$property => $property_value]);
                }
            }
            $result = $tmp;
        }
        return $result;
    }

    public static function variation_price($product, $variation)
    {
        if (empty(json_decode($variation, true))) {
            $result = $product['price'];
        } else {
            $match = json_decode($variation, true)[0];
            $result = 0;
            foreach (json_decode($product['variations'], true) as $property => $value) {
                if ($value['type'] == $match['type']) {
                    $result = $value['price'];
                }
            }
        }
        return self::set_price($result);
    }

    //get new variation price calculation for pos
    public static function new_variation_price($product, $variations)
    {
        $match = $variations;
        $result = 0;

        foreach($product as $product_variation){
            foreach($product_variation['values'] as $option){
                foreach($match as $variation){
                    if($product_variation['name'] == $variation['name'] && isset($variation['values']) && in_array($option['label'], $variation['values']['label'])){
                        $result += $option['optionPrice'];
                    }
                }
            }
        }
        return $result;
    }

    //new variation price calculation for order
    public static function get_varient(array $product_variations, array $variations)
    {
        $result = [];
        $variation_price = 0;

        foreach($variations as $k=> $variation){
            foreach($product_variations as  $product_variation){
                if( isset($variation['values']) && isset($product_variation['values']) && $product_variation['name'] == $variation['name']  ){
                    $result[$k] = $product_variation;
                    $result[$k]['values'] = [];
                    foreach($product_variation['values'] as $key=> $option){
                        if(in_array($option['label'], $variation['values']['label'])){
                            $result[$k]['values'][] = $option;
                            $variation_price += $option['optionPrice'];
                        }
                    }
                }
            }
        }

        return ['price'=>$variation_price,'variations'=>$result];
    }

    public static function product_data_formatting($data, $multi_data = false)
    {
        $storage = [];

        if ($multi_data == true) {
            foreach ($data as $item) {

                $variations = [];
                $item['category_ids'] = json_decode($item['category_ids']);
                $item['attributes'] = json_decode($item['attributes']);
                $item['choice_options'] = json_decode($item['choice_options']);
                $item['add_ons'] = AddOn::whereIn('id', json_decode($item['add_ons']))->whereStatus(true)->with('category')->get();
                /*foreach (json_decode($item['variations'], true) as $var) {
                    $variations[] = [
                        'type' => $var['type'],
                        'price' => (double)$var['price']
                    ];
                }
                $item['variations'] = $variations;*/

                $item['variations'] = json_decode($item['variations'], true);

                if (count($item['translations'])) {
                    foreach ($item['translations'] as $translation) {
                        if ($translation->key == 'name') {
                            $item['name'] = $translation->value;
                        }
                        if ($translation->key == 'description') {
                            $item['description'] = $translation->value;
                        }
                    }
                }
                unset($item['translations']);
                $storage[] = $item;
            }
            $data = $storage;
        } else {
            $data_addons = $data['add_ons'];
            $addon_ids = [];
            if(gettype($data_addons) != 'array') {
                $addon_ids = json_decode($data_addons);

            } elseif(gettype($data_addons) == 'array' && isset($data_addons[0]['id'])) {
                foreach($data_addons as $addon) {
                    $addon_ids[] = $addon['id'];
                }

            } else {
                $addon_ids = $data_addons;
            }

            $variations = [];
            $data['category_ids'] = gettype($data['category_ids']) != 'array' ? json_decode($data['category_ids']) : $data['category_ids'];
            $data['attributes'] = gettype($data['attributes']) != 'array' ? json_decode($data['attributes']) : $data['attributes'];
            $data['choice_options'] = gettype($data['choice_options']) != 'array' ? json_decode($data['choice_options']) : $data['choice_options'];
            $data['add_ons'] = AddOn::whereIn('id', $addon_ids)->whereStatus(true)->get();

            /*foreach (gettype($data['variations']) != 'array' ? json_decode($data['variations'], true) : $data['variations'] as $var) {
                array_push($variations, [
                    'type' => $var['type'],
                    'price' => (double)$var['price']
                ]);
            }*/

            //$data['variations'] = $variations;

            $data['variations'] = json_decode($data['variations'], true);

            if (count($data['translations']) > 0) {
                foreach ($data['translations'] as $translation) {
                    if ($translation->key == 'name') {
                        $data['name'] = $translation->value;
                    }
                    if ($translation->key == 'description') {
                        $data['description'] = $translation->value;
                    }
                }
            }
        }

        return $data;
    }

    public static function order_data_formatting($data, $multi_data = false)
    {
        $storage = [];
        if ($multi_data) {
            foreach ($data as $item) {
                $item['add_on_ids'] = json_decode($item['add_on_ids']);
                $storage[] = $item;
            }
            $data = $storage;
        } else {
            $data['add_on_ids'] = json_decode($data['add_on_ids']);

            foreach ($data->details as $detail) {
                $detail->product_details = gettype($detail->product_details) != 'array' ? json_decode($detail->product_details) : $detail->product_details;

                $detail->product_details->add_ons = gettype($detail->product_details->add_ons) != 'array' ? json_decode($detail->product_details->add_ons) : $detail->product_details->add_ons;
                $detail->product_details->variations = gettype($detail->product_details->variations) != 'array' ? json_decode($detail->product_details->variations) : $detail->product_details->variations;
                $detail->product_details->attributes = gettype($detail->product_details->attributes) != 'array' ? json_decode($detail->product_details->attributes) : $detail->product_details->attributes;
                $detail->product_details->category_ids = gettype($detail->product_details->category_ids) != 'array' ? json_decode($detail->product_details->category_ids) : $detail->product_details->category_ids;
                $detail->product_details->choice_options = gettype($detail->product_details->choice_options) != 'array' ? json_decode($detail->product_details->choice_options) : $detail->product_details->choice_options;

                $detail->variation = gettype($detail->variation) != 'array' ? json_decode($detail->variation) : $detail->variation;
                $detail->add_on_ids = gettype($detail->add_on_ids) != 'array' ? json_decode($detail->add_on_ids) : $detail->add_on_ids;
                $detail->variant = gettype($detail->variant) != 'array' ? json_decode($detail->variant) : $detail->variant;
                $detail->add_on_qtys = gettype($detail->add_on_qtys) != 'array' ? json_decode($detail->add_on_qtys) : $detail->add_on_qtys;
            }
        }

        return $data;
    }

    public static function get_business_settings($name)
    {
        $config = null;
        $data = \App\Model\BusinessSetting::where(['key' => $name])->first();
        if (isset($data)) {
            $config = json_decode($data['value'], true);
            if (is_null($config)) {
                $config = $data['value'];
            }
        }
        return $config;
    }

    public static function currency_code()
    {
        $currency_code = BusinessSetting::where(['key' => 'currency'])->first()->value;
        return $currency_code;
    }

    public static function currency_symbol()
    {
        $currency_symbol = Currency::where(['currency_code' => Helpers::currency_code()])->first()->currency_symbol;
        return $currency_symbol;
    }

    public static function set_symbol($amount)
    {
        $decimal_point_settings = Helpers::get_business_settings('decimal_point_settings');
        $position = Helpers::get_business_settings('currency_symbol_position');
        if (!is_null($position) && $position == 'left') {
            $string = self::currency_symbol() . '' . number_format($amount, $decimal_point_settings);
        } else {
            $string = number_format($amount, $decimal_point_settings) . '' . self::currency_symbol();
        }
        return $string;
    }

    public static function set_price($amount)
    {
        $decimal_point_settings = Helpers::get_business_settings('decimal_point_settings');
        $amount = number_format($amount, $decimal_point_settings, '.', '');

        return $amount;
    }

    public static function send_push_notif_to_device($fcm_token, $data)
    {
        $key = self::get_business_settings('push_notification_key');
        $url = "https://fcm.googleapis.com/fcm/send";
        $header = array("authorization: key=" . $key . "",
            "content-type: application/json"
        );

        $postdata = '{
            "to" : "' . $fcm_token . '",
            "mutable_content": true,
            "data" : {
                "title":"' . $data['title'] . '",
                "body" : "' . $data['description'] . '",
                "image" : "' . $data['image'] . '",
                "order_id":"' . $data['order_id'] . '",
                "type":"' . $data['type'] . '",
                "is_read": 0
            },
            "notification" : {
                "title" :"' . $data['title'] . '",
                "body" : "' . $data['description'] . '",
                "image" : "' . $data['image'] . '",
                "order_id":"' . $data['order_id'] . '",
                "title_loc_key":"' . $data['order_id'] . '",
                "body_loc_key":"' . $data['type'] . '",
                "type":"' . $data['type'] . '",
                "is_read": 0,
                "icon" : "new",
                "sound": "notification",
                "android_channel_id": "efood"
            }
        }';
        $ch = curl_init();
        $timeout = 120;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        // Get URL content
        $result = curl_exec($ch);
        // close handle to release resources
        curl_close($ch);

        return $result;
    }

    public static function send_push_notif_to_topic($data, $topic, $type)
    {
        $key = BusinessSetting::where(['key' => 'push_notification_key'])->first()->value;
        $url = "https://fcm.googleapis.com/fcm/send";
        $header = array("authorization: key=" . $key . "",
            "content-type: application/json"
        );
        
        if (isset($data['order_id'])) {
            $postdata = '{
                "to" : "/topics/' . $topic . '",
                "mutable_content": true,
                "data" : {
                    "title":"' . $data['title'] . '",
                    "body" : "' . $data['description'] . '",
                    "image" : "' . $data['image'] . '",
                    "order_id":"' . $data['order_id'] . '",
                    "is_read": 0,
                    "type":"' . $type . '"
                },
                "notification" : {
                    "title":"' . $data['title'] . '",
                    "body" : "' . $data['description'] . '",
                    "image" : "' . $data['image'] . '",
                    "order_id":"' . $data['order_id'] . '",
                    "title_loc_key":"' . $data['order_id'] . '",
                    "body_loc_key":"' . $type . '",
                    "type":"' . $type . '",
                    "is_read": 0,
                    "icon" : "new",
                    "sound": "notification",
                    "android_channel_id": "efood"
                  }
            }';
        } else {
            $postdata = '{
                "to" : "/topics/' . $topic . '",
                "mutable_content": true,
                "data" : {
                    "title":"' . $data['title'] . '",
                    "body" : "' . $data['description'] . '",
                    "image" : "' . $data['image'] . '",
                    "is_read": 0,
                    "type":"' . $type . '",

                },
                "notification" : {
                    "title":"' . $data['title'] . '",
                    "body" : "' . $data['description'] . '",
                    "image" : "' . $data['image'] . '",
                    "body_loc_key":"' . $type . '",
                    "type":"' . $type . '",
                    "is_read": 0,
                    "icon" : "new",
                    "sound": "notification",
                    "android_channel_id": "efood"
                  }
            }';
        }

        $ch = curl_init();
        $timeout = 120;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        // Get URL content
        $result = curl_exec($ch);
        // close handle to release resources
        curl_close($ch);

        return $result;
    }

    public static function send_push_notif_to_specific_user($token, $title, $body)
    {
        // Define validation rules
        $validator = Validator::make(
            compact('token', 'title', 'body'),
            [
                'title' => 'required|string',
                'token' => 'required|string',
                'body' => 'required|string',
            ]
        );

        // Check if validation fails
        if ($validator->fails()) {
            return [
                'status' => 'error',
                'code' => 400,
                'errors' => $validator->errors()
            ];
        }
        
        try {
            $firebase = (new Factory)->withServiceAccount(base_path('config/firebase_credentials.json'));
            $messaging = $firebase->createMessaging();

            $message = CloudMessage::withTarget('token', str_replace(' ', '-', $token))
                ->withNotification(['title' => $title, 'body' => $body]);

            // Send the message and get the response
            $response = $messaging->send($message);

            return [
                'status' => 'success',
                'code' => 200,
                'message' => 'Push notification sent successfully',
                'firebase_response' => $response
            ];
        } catch (\Kreait\Firebase\Exception\Messaging\NotFound $e) {
            // Handle the case where the token is invalid or the entity is not found
            return [
                'status' => 'error',
                'code' => 404,
                'message' => 'Failed to send push notification',
                'details' => 'The requested entity was not found: ' . $e->getMessage()
            ];
        } catch (\Kreait\Firebase\Exception\MessagingException $e) {
            // Handle general Firebase Messaging exceptions
            return [
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to send push notification_',
                'details' => $e->getMessage()
            ];
        } catch (\Throwable $th) {
            // Handle any other exceptions
            return [
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to send push notification',
                'details' => $th->getMessage()
            ];
        }
    }

    public static function send_push_notif_to_all_users($title, $body)
    {
        // Define validation rules
        $validator = Validator::make(
            compact('title', 'body'),
            [
                'title' => 'required|string',
                'body' => 'required|string',
            ]
        );

        // Check if validation fails
        if ($validator->fails()) {
            return [
                'status' => 'error',
                'code' => 400,
                'errors' => $validator->errors()
            ];
        }
        
        try {
            $firebase = (new Factory)->withServiceAccount(base_path('config/firebase_credentials.json'));
            $messaging = $firebase->createMessaging();

            $message = CloudMessage::withTarget('topic', "notify")
                ->withNotification(['title' => $title, 'body' => $body]);

            // Send the message and get the response
            $response = $messaging->send($message);

            return [
                'status' => 'success',
                'code' => 200,
                'message' => 'Push notification sent successfully',
                'firebase_response' => $response
            ];
        } catch (\Kreait\Firebase\Exception\Messaging\NotFound $e) {
            // Handle the case where the token is invalid or the entity is not found
            return [
                'status' => 'error',
                'code' => 404,
                'message' => 'Failed to send push notification',
                'details' => 'The requested entity was not found: ' . $e->getMessage()
            ];
        } catch (\Kreait\Firebase\Exception\MessagingException $e) {
            // Handle general Firebase Messaging exceptions
            return [
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to send push notification_',
                'details' => $e->getMessage()
            ];
        } catch (\Throwable $th) {
            // Handle any other exceptions
            return [
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to send push notification',
                'details' => $th->getMessage()
            ];
        }
    }

    public static function send_push_notif_to_topic_new($topic, $title, $body)
    {
        // Define validation rules
        $validator = Validator::make(
            compact('title', 'body'),
            [
                'title' => 'required|string',
                'body' => 'required|string',
            ]
        );

        // Check if validation fails
        if ($validator->fails()) {
            return [
                'status' => 'error',
                'code' => 400,
                'errors' => $validator->errors()
            ];
        }
        
        try {
            $firebase = (new Factory)->withServiceAccount(base_path('config/firebase_credentials.json'));
            $messaging = $firebase->createMessaging();

            $message = CloudMessage::withTarget('topic', $topic)
                ->withNotification(['title' => $title, 'body' => $body]);

            // Send the message and get the response
            $response = $messaging->send($message);

            return [
                'status' => 'success',
                'code' => 200,
                'message' => 'Push notification sent successfully',
                'firebase_response' => $response
            ];
        } catch (\Kreait\Firebase\Exception\Messaging\NotFound $e) {
            // Handle the case where the token is invalid or the entity is not found
            return [
                'status' => 'error',
                'code' => 404,
                'message' => 'Failed to send push notification',
                'details' => 'The requested entity was not found: ' . $e->getMessage()
            ];
        } catch (\Kreait\Firebase\Exception\MessagingException $e) {
            // Handle general Firebase Messaging exceptions
            return [
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to send push notification_',
                'details' => $e->getMessage()
            ];
        } catch (\Throwable $th) {
            // Handle any other exceptions
            return [
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to send push notification',
                'details' => $th->getMessage()
            ];
        }
    }


    public static function rating_count($product_id, $rating)
    {
        return Review::where(['product_id' => $product_id, 'rating' => $rating])->count();
    }

    public static function dm_rating_count($deliveryman_id, $rating)
    {
        return DMReview::where(['delivery_man_id' => $deliveryman_id, 'rating' => $rating])->count();
    }

    public static function tax_calculate($product, $price)
    {
        if ($product['tax_type'] == 'percent') {
            $price_tax = ($price / 100) * $product['tax'];
        } else {
            $price_tax = $product['tax'];
        }
        return self::set_price($price_tax);
    }

    public static function discount_calculate($product, $price)
    {
        if ($product['discount_type'] == 'percent') {
            $price_discount = ($price / 100) * $product['discount'];
        } else {
            $price_discount = $product['discount'];
        }
        return self::set_price($price_discount);
    }

    public static function max_earning()
    {
        $data = Order::where(['order_status' => 'delivered'])->select('id', 'created_at', 'order_amount')
            ->get()
            ->groupBy(function ($date) {
                return Carbon::parse($date->created_at)->format('m');
            });

        $max = 0;
        foreach ($data as $month) {
            $count = 0;
            foreach ($month as $order) {
                $count += $order['order_amount'];
            }
            if ($count > $max) {
                $max = $count;
            }
        }
        return $max;
    }

    public static function max_orders()
    {
        $data = Order::select('id', 'created_at')
            ->get()
            ->groupBy(function ($date) {
                return Carbon::parse($date->created_at)->format('m');
            });

        $max = 0;
        foreach ($data as $month) {
            $count = 0;
            foreach ($month as $order) {
                $count += 1;
            }
            if ($count > $max) {
                $max = $count;
            }
        }
        return $max;
    }

    public static function order_status_update_message($status)
    {
        if ($status == 'pending') {
            $data = self::get_business_settings('order_pending_message');
        } elseif ($status == 'confirmed') {
            $data = self::get_business_settings('order_confirmation_msg');
        } elseif ($status == 'processing') {
            $data = self::get_business_settings('order_processing_message');
        } elseif ($status == 'out_for_delivery') {
            $data = self::get_business_settings('out_for_delivery_message');
        } elseif ($status == 'delivered') {
            $data = self::get_business_settings('order_delivered_message');
        } elseif ($status == 'delivery_boy_delivered') {
            $data = self::get_business_settings('delivery_boy_delivered_message');
        } elseif ($status == 'del_assign') {
            $data = self::get_business_settings('delivery_boy_assign_message');
        } elseif ($status == 'ord_start') {
            $data = self::get_business_settings('delivery_boy_start_message');
        } elseif ($status == 'returned') {
            $data = self::get_business_settings('returned_message');
        } elseif ($status == 'failed') {
            $data = self::get_business_settings('failed_message');
        } elseif ($status == 'canceled') {
            $data = self::get_business_settings('canceled_message');
        } elseif ($status == 'customer_notify_message') {
            $data = self::get_business_settings('customer_notify_message');
        } elseif ($status == 'customer_notify_message_for_time_change') {
            $data = self::get_business_settings('customer_notify_message_for_time_change');
        } elseif ($status == 'add_wallet_message') {
            $data = self::get_business_settings('add_wallet_message');
        } elseif ($status == 'add_wallet_bonus_message') {
            $data = self::get_business_settings('add_wallet_bonus_message');
        } else {
            $data['status'] = 0;
            $data['message'] = "";
//            $data = '{"status":"0","message":""}';

        }

        if ($data == null || (array_key_exists('status', $data) && $data['status'] == 0)) {
            return 0;
        }

        return $data['message'];
    }

    public static function day_part()
    {
        $part = "";
        $morning_start = date("h:i:s", strtotime("5:00:00"));
        $afternoon_start = date("h:i:s", strtotime("12:01:00"));
        $evening_start = date("h:i:s", strtotime("17:01:00"));
        $evening_end = date("h:i:s", strtotime("21:00:00"));

        if (time() >= $morning_start && time() < $afternoon_start) {
            $part = "morning";
        } elseif (time() >= $afternoon_start && time() < $evening_start) {
            $part = "afternoon";
        } elseif (time() >= $evening_start && time() <= $evening_end) {
            $part = "evening";
        } else {
            $part = "night";
        }

        return $part;
    }

    public static function env_update($key, $value)
    {
        $path = base_path('.env');
        if (file_exists($path)) {
            file_put_contents($path, str_replace(
                $key . '=' . env($key), $key . '=' . $value, file_get_contents($path)
            ));
        }
    }

    public static function env_key_replace($key_from, $key_to, $value)
    {
        $path = base_path('.env');
        if (file_exists($path)) {
            file_put_contents($path, str_replace(
                $key_from . '=' . env($key_from), $key_to . '=' . $value, file_get_contents($path)
            ));
        }
    }

    public static function remove_dir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir . "/" . $object) == "dir") Helpers::remove_dir($dir . "/" . $object); else unlink($dir . "/" . $object);
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }

    public static function get_language_name($key)
    {
        $languages = array(
            "af" => "Afrikaans",
            "sq" => "Albanian - shqip",
            "am" => "Amharic - አማርኛ",
            "ar" => "Arabic - العربية",
            "an" => "Aragonese - aragonés",
            "hy" => "Armenian - հայերեն",
            "ast" => "Asturian - asturianu",
            "az" => "Azerbaijani - azərbaycan dili",
            "eu" => "Basque - euskara",
            "be" => "Belarusian - беларуская",
            "bn" => "Bengali - বাংলা",
            "bs" => "Bosnian - bosanski",
            "br" => "Breton - brezhoneg",
            "bg" => "Bulgarian - български",
            "ca" => "Catalan - català",
            "ckb" => "Central Kurdish - کوردی (دەستنوسی عەرەبی)",
            "zh" => "Chinese - 中文",
            "zh-HK" => "Chinese (Hong Kong) - 中文（香港）",
            "zh-CN" => "Chinese (Simplified) - 中文（简体）",
            "zh-TW" => "Chinese (Traditional) - 中文（繁體）",
            "co" => "Corsican",
            "hr" => "Croatian - hrvatski",
            "cs" => "Czech - čeština",
            "da" => "Danish - dansk",
            "nl" => "Dutch - Nederlands",
            "en" => "English",
            "en-AU" => "English (Australia)",
            "en-CA" => "English (Canada)",
            "en-IN" => "English (India)",
            "en-NZ" => "English (New Zealand)",
            "en-ZA" => "English (South Africa)",
            "en-GB" => "English (United Kingdom)",
            "en-US" => "English (United States)",
            "eo" => "Esperanto - esperanto",
            "et" => "Estonian - eesti",
            "fo" => "Faroese - føroyskt",
            "fil" => "Filipino",
            "fi" => "Finnish - suomi",
            "fr" => "French - français",
            "fr-CA" => "French (Canada) - français (Canada)",
            "fr-FR" => "French (France) - français (France)",
            "fr-CH" => "French (Switzerland) - français (Suisse)",
            "gl" => "Galician - galego",
            "ka" => "Georgian - ქართული",
            "de" => "German - Deutsch",
            "de-AT" => "German (Austria) - Deutsch (Österreich)",
            "de-DE" => "German (Germany) - Deutsch (Deutschland)",
            "de-LI" => "German (Liechtenstein) - Deutsch (Liechtenstein)",
            "de-CH" => "German (Switzerland) - Deutsch (Schweiz)",
            "el" => "Greek - Ελληνικά",
            "gn" => "Guarani",
            "gu" => "Gujarati - ગુજરાતી",
            "ha" => "Hausa",
            "haw" => "Hawaiian - ʻŌlelo Hawaiʻi",
            "he" => "Hebrew - עברית",
            "hi" => "Hindi - हिन्दी",
            "hu" => "Hungarian - magyar",
            "is" => "Icelandic - íslenska",
            "id" => "Indonesian - Indonesia",
            "ia" => "Interlingua",
            "ga" => "Irish - Gaeilge",
            "it" => "Italian - italiano",
            "it-IT" => "Italian (Italy) - italiano (Italia)",
            "it-CH" => "Italian (Switzerland) - italiano (Svizzera)",
            "ja" => "Japanese - 日本語",
            "kn" => "Kannada - ಕನ್ನಡ",
            "kk" => "Kazakh - қазақ тілі",
            "km" => "Khmer - ខ្មែរ",
            "ko" => "Korean - 한국어",
            "ku" => "Kurdish - Kurdî",
            "ky" => "Kyrgyz - кыргызча",
            "lo" => "Lao - ລາວ",
            "la" => "Latin",
            "lv" => "Latvian - latviešu",
            "ln" => "Lingala - lingála",
            "lt" => "Lithuanian - lietuvių",
            "mk" => "Macedonian - македонски",
            "ms" => "Malay - Bahasa Melayu",
            "ml" => "Malayalam - മലയാളം",
            "mt" => "Maltese - Malti",
            "mr" => "Marathi - मराठी",
            "mn" => "Mongolian - монгол",
            "ne" => "Nepali - नेपाली",
            "no" => "Norwegian - norsk",
            "nb" => "Norwegian Bokmål - norsk bokmål",
            "nn" => "Norwegian Nynorsk - nynorsk",
            "oc" => "Occitan",
            "or" => "Oriya - ଓଡ଼ିଆ",
            "om" => "Oromo - Oromoo",
            "ps" => "Pashto - پښتو",
            "fa" => "Persian - فارسی",
            "pl" => "Polish - polski",
            "pt" => "Portuguese - português",
            "pt-BR" => "Portuguese (Brazil) - português (Brasil)",
            "pt-PT" => "Portuguese (Portugal) - português (Portugal)",
            "pa" => "Punjabi - ਪੰਜਾਬੀ",
            "qu" => "Quechua",
            "ro" => "Romanian - română",
            "mo" => "Romanian (Moldova) - română (Moldova)",
            "rm" => "Romansh - rumantsch",
            "ru" => "Russian - русский",
            "gd" => "Scottish Gaelic",
            "sr" => "Serbian - српски",
            "sh" => "Serbo-Croatian - Srpskohrvatski",
            "sn" => "Shona - chiShona",
            "sd" => "Sindhi",
            "si" => "Sinhala - සිංහල",
            "sk" => "Slovak - slovenčina",
            "sl" => "Slovenian - slovenščina",
            "so" => "Somali - Soomaali",
            "st" => "Southern Sotho",
            "es" => "Spanish - español",
            "es-AR" => "Spanish (Argentina) - español (Argentina)",
            "es-419" => "Spanish (Latin America) - español (Latinoamérica)",
            "es-MX" => "Spanish (Mexico) - español (México)",
            "es-ES" => "Spanish (Spain) - español (España)",
            "es-US" => "Spanish (United States) - español (Estados Unidos)",
            "su" => "Sundanese",
            "sw" => "Swahili - Kiswahili",
            "sv" => "Swedish - svenska",
            "tg" => "Tajik - тоҷикӣ",
            "ta" => "Tamil - தமிழ்",
            "tt" => "Tatar",
            "te" => "Telugu - తెలుగు",
            "th" => "Thai - ไทย",
            "ti" => "Tigrinya - ትግርኛ",
            "to" => "Tongan - lea fakatonga",
            "tr" => "Turkish - Türkçe",
            "tk" => "Turkmen",
            "tw" => "Twi",
            "uk" => "Ukrainian - українська",
            "ur" => "Urdu - اردو",
            "ug" => "Uyghur",
            "uz" => "Uzbek - o‘zbek",
            "vi" => "Vietnamese - Tiếng Việt",
            "wa" => "Walloon - wa",
            "cy" => "Welsh - Cymraeg",
            "fy" => "Western Frisian",
            "xh" => "Xhosa",
            "yi" => "Yiddish",
            "yo" => "Yoruba - Èdè Yorùbá",
            "zu" => "Zulu - isiZulu",
        );
        return array_key_exists($key, $languages) ? $languages[$key] : $key;
    }

    public static function language_load()
    {
        if (\session()->has('language_settings')) {
            $language = \session('language_settings');
        } else {
            $language = BusinessSetting::where('key', 'language')->first();
            \session()->put('language_settings', $language);
        }
        return $language;
    }

    public static function upload(string $dir, string $format, $image = null)
    {
        if ($image != null) {
            $imageName = \Carbon\Carbon::now()->toDateString() . "-" . uniqid() . "." . $format;
            if (!Storage::disk('public')->exists($dir)) {
                Storage::disk('public')->makeDirectory($dir);
            }
            Storage::disk('public')->put($dir . $imageName, file_get_contents($image));
        } else {
            $imageName = 'def.png';
        }

        return $imageName;
    }

    public static function update(string $dir, $old_image, string $format, $image = null)
    {
        if (Storage::disk('public')->exists($dir . $old_image)) {
            Storage::disk('public')->delete($dir . $old_image);
        }
        $imageName = Helpers::upload($dir, $format, $image);
        return $imageName;
    }

    public static function delete($full_path)
    {
        if (Storage::disk('public')->exists($full_path)) {
            Storage::disk('public')->delete($full_path);
        }
        return [
            'success' => 1,
            'message' => 'Removed successfully !'
        ];
    }

    public static function setEnvironmentValue($envKey, $envValue)
    {
        $envFile = app()->environmentFilePath();
        $str = file_get_contents($envFile);
        if (is_bool(env($envKey))) {
            $oldValue = var_export(env($envKey), true);
        } else {
            $oldValue = env($envKey);
        }
//        $oldValue = var_export(env($envKey), true);

        if (strpos($str, $envKey) !== false) {
            $str = str_replace("{$envKey}={$oldValue}", "{$envKey}={$envValue}", $str);

//            dd("{$envKey}={$envValue}");
//            dd($str);
        } else {
            $str .= "{$envKey}={$envValue}\n";
        }
        $fp = fopen($envFile, 'w');
        fwrite($fp, $str);
        fclose($fp);
        return $envValue;
    }

    public static function requestSender($request): array
    {
		return [
			'active' => 1
		];
    }

    public static function getPagination()
    {
        $pagination_limit = Helpers::get_business_settings('pagination_limit');
        return $pagination_limit ?? 25;
    }

    public static function remove_invalid_charcaters($str)
    {
        return str_ireplace(['\'', '"', ';', '<', '>'], ' ', $str);
    }

    public static function get_delivery_charge($distance)
    {
        $config = self::get_business_settings('delivery_management');

        if ($config['status'] != 1) {
            $delivery_charge = BusinessSetting::where(['key' => 'delivery_charge'])->first()->value;
            return $delivery_charge;
        } else {
            $delivery_charge = 0;
            $min_shipping_charge = $config['min_shipping_charge'];
            $shipping_per_km = $config['shipping_per_km'];

            $delivery_charge = $shipping_per_km * $distance;

            if ($delivery_charge > $min_shipping_charge) {
                return self::set_price($delivery_charge);
            } else {
                return self::set_price($min_shipping_charge);
            }
        }
    }

    public static function calculate_addon_price($addons, $add_on_qtys)
    {
        $add_ons_cost = 0;
        $data = [];
        if ($addons) {
            foreach ($addons as $key2 => $addon) {
                if ($add_on_qtys == null) {
                    $add_on_qty = 1;
                } else {
                    $add_on_qty = $add_on_qtys[$key2];
                }
                $data[] = $addon->id;
                $add_ons_cost += $addon['price'] * $add_on_qty;
            }
            return ['addons' => $data, 'total_add_on_price' => self::set_price($add_ons_cost)];
        }
        return null;
    }


    public static function get_default_language()
    {
        $data = self::get_business_settings('language');
        $default_lang = 'en';
        if ($data && array_key_exists('code', $data)) {
            foreach ($data as $lang) {
                if ($lang['default'] == true) {
                    $default_lang = $lang['code'];
                }
            }
        }

        return $default_lang;
    }

    public static function module_permission_check($mod_name)
    {
        $permission = auth('admin')->user()->role->module_access??null;
        if (isset($permission) && in_array($mod_name, (array)json_decode($permission)) == true) {
            return true;
        }

        if (auth('admin')->user()->admin_role_id == 1) {
            return true;
        }
        return false;
    }

    public static function file_remover(string $dir, $image)
    {
        if (!isset($image)) return true;

        if (Storage::disk('public')->exists($dir . $image)) Storage::disk('public')->delete($dir . $image);

        return true;
    }

    public static function order_details_formatter($details)
    {
        if ($details->count() > 0) {
            foreach ($details as $detail) {
                $detail['product_details'] = gettype($detail['product_details']) != 'array' ? (array) json_decode($detail['product_details'], true) : (array) $detail['product_details'];
                $detail['variation'] = gettype($detail['variation']) != 'array' ? (array) json_decode($detail['variation'], true) : (array) $detail['variation'];
                $detail['add_on_ids'] = gettype($detail['add_on_ids']) != 'array' ? (array) json_decode($detail['add_on_ids'], true) : (array) $detail['add_on_ids'];
                $detail['variant'] = gettype($detail['variant']) != 'array' ? (array) json_decode($detail['variant'], true) : (array) $detail['variant'];
                $detail['add_on_qtys'] = gettype($detail['add_on_qtys']) != 'array' ? (array) json_decode($detail['add_on_qtys'], true) : (array) $detail['add_on_qtys'];
                $detail['add_on_prices'] = gettype($detail['add_on_prices']) != 'array' ? (array) json_decode($detail['add_on_prices'], true) : (array) $detail['add_on_prices'];
                $detail['add_on_taxes'] = gettype($detail['add_on_taxes']) != 'array' ? (array) json_decode($detail['add_on_taxes'], true) : (array) $detail['add_on_taxes'];

//                if(count($detail->variation) > 0) {
//                    $detail['variation'] = $detail->variation[0] ?? null; //first element is given, since variation can't be multiple
//                } else {
//                    $detail['variation'] = null;
//                }

                if(!isset($detail['reviews_count'])) {
                    $detail['review_count'] = Review::where(['order_id' => $detail['order_id'], 'product_id' => $detail['product_id']])->count();
                }

                $detail['product_details'] = Helpers::product_formatter($detail['product_details']);

                $product_availability = Product::where('id', $detail['product_id'])->first();
                $detail['is_product_available'] = isset($product_availability) ? 1 : 0;
            }
        }

        return $details;
    }

    public static function product_formatter($product)
    {
        $product['variations'] = gettype($product['variations']) != 'array' ? (array)json_decode($product['variations'], true) : (array)$product['variations'];
        $product['add_ons'] = gettype($product['add_ons']) != 'array' ? (array)json_decode($product['add_ons'], true) : (array)$product['add_ons'];
        $product['attributes'] = gettype($product['attributes']) != 'array' ? (array)json_decode($product['attributes'], true) : (array)$product['attributes'];
        $product['category_ids'] = gettype($product['category_ids']) != 'array' ? (array)json_decode($product['category_ids'], true) : (array)$product['category_ids'];
        $product['choice_options'] = gettype($product['choice_options']) != 'array' ? (array)json_decode($product['choice_options'], true) : (array)$product['choice_options'];

        // try {
        //     $addons = [];
        //     foreach ($product['add_ons'] as $add_on_id) {
        //         $addon = AddOn::find($add_on_id)->whereStatus(true);
        //         if (isset($addon)) {
        //             $addons [] = $addon;
        //         }
        //     }
        //     $product['add_ons'] = $addons;

        // } catch (\Exception $exception) {
        //     //
        // }
        try {
            $addons = [];
            foreach ($product['add_ons'] as $add_on_id) {
                // Use `where()` to find the add-on with `status = true`
                $addon = AddOn::where('id', $add_on_id)
                              ->where('status', true) // Add status check here
                              ->first(); // Use `first()` to get a single result
        
                // If the add-on exists and is not null, add it to the addons array
                if ($addon) {
                    $addons[] = $addon;
                }
            }
            
            // Update the product's add-ons
            $product['add_ons'] = $addons;
        
        } catch (\Exception $exception) {
            // You can log the exception here if needed
            \Log::error($exception->getMessage());
        }

        return $product;
    }

    public static function generate_referer_code() {
        $ref_code = Str::random('20');
        if (User::where('refer_code', '=', $ref_code)->exists()) {
            return generate_referer_code();
        }
        return $ref_code;
    }

    public static function update_daily_product_stock() {
        $current_day = now()->day;
        $current_month = now()->month;
        $products = ProductByBranch::where(['stock_type' => 'daily'])->get();
        foreach ($products as $product){
            if ($current_day != $product['updated_at']->day || $current_month != $product['updated_at']->month){
                $product['sold_quantity'] = 0;
                $product->save();
            }
        }
        return true;
    }

    public static function text_variable_data_format($value,$user_name=null,$restaurant_name=null,$delivery_man_name=null,$transaction_id=null,$order_id=null)
    {
        $data = $value;
        if ($value) {
            if($user_name){
                $data =  str_replace("{userName}", $user_name, $data);
            }

            if($restaurant_name){
                $data =  str_replace("{restaurantName}", $restaurant_name, $data);
            }

            if($delivery_man_name){
                $data =  str_replace("{deliveryManName}", $delivery_man_name, $data);
            }

            if($order_id){
                $data =  str_replace("{orderId}", $order_id, $data);
            }
        }
        return $data;
    }

    public static function order_status_message_key($status)
    {
        if ($status == 'pending') {
            $data = 'order_pending_message';
        } elseif ($status == 'confirmed') {
            $data = 'order_confirmation_msg';
        } elseif ($status == 'processing') {
            $data = 'order_processing_message';
        } elseif ($status == 'out_for_delivery') {
            $data = 'out_for_delivery_message';
        } elseif ($status == 'delivered') {
            $data = 'order_delivered_message';
        } elseif ($status == 'delivery_boy_delivered') {
            $data = 'delivery_boy_delivered_message';
        } elseif ($status == 'del_assign') {
            $data = 'delivery_boy_assign_message';
        } elseif ($status == 'ord_start') {
            $data = 'delivery_boy_start_message';
        } elseif ($status == 'returned') {
            $data = 'returned_message';
        } elseif ($status == 'failed') {
            $data = 'failed_message';
        } elseif ($status == 'canceled') {
            $data = 'canceled_message';
        } elseif ($status == 'customer_notify_message') {
            $data = 'customer_notify_message';
        } elseif ($status == 'customer_notify_message_for_time_change') {
            $data = 'customer_notify_message_for_time_change';
        } else {
            $data = $status;
        }

        return $data;
    }

    
    public static function escape_string($value)
    {
        $search = array("\\",  "\x00", "\n",  "\r",  "'",  '"', "\x1a");
        $replace = array("\\\\", "\\0", "\\n", "\\r", "\'", '\"', "\\Z");
        return str_replace($search, $replace, $value);
    }
    
   
    public static function clean_input($input)
    {
        $search = [
            '@<script[^>]*?>.*?</script>@si',   // Strip out javascript
            '@<[\/\!]*?[^<>]*?>@si',            // Strip out HTML tags
            '@<style[^>]*?>.*?</style>@siU',    // Strip style tags properly
            '@<![\s\S]*?--[ \t\n\r]*>@'         // Strip multi-line comments
        ];
        $output = preg_replace($search, '', $input);
        return $output;
    }
    
    
    public static function sanitize_input($input)
    {
        if (is_array($input)) {
            $output = [];
            foreach ($input as $var => $val) {
                $output[$var] = self::sanitize_input($val);
            }
            return $output;
        } else {
            return self::escape_string(self::clean_input(stripslashes($input)));
        }
    }

    public static function getFileNameFromPath($path){
        $existing_image_path = explode('/', $path);
        return $existing_image_path[count($existing_image_path)-1];
    }

    public static function notifyKitchenFirstTimeCustomer($userId)
    {
        // Check if the user has placed any orders before
        $hasOrderedBefore = Order::where('user_id', $userId)->exists();

        if (!$hasOrderedBefore) {
            // The user is a first-time customer
            // Notify the kitchen
            self::sendFirstTimeCustomerNotification($userId);

            return [
                'status' => 'success',
                'message' => 'The user is a first-time customer.',
            ];
        }

        return [
            'status' => 'error',
            'message' => 'This user has ordered before. No need to notify the kitchen.',
        ];
    }

    protected static function sendFirstTimeCustomerNotification($userId)
    {
        // Logic to send the notification to the kitchen
        // This could be a message to a kitchen dashboard, an email, an SMS, etc.
        
        $user = User::find($userId);
        $message = "New first-time customer: " . $user->name . " (ID: " . $user->id . ")";
    }


}

function translate($key)
{
    $local = session()->has('local') ? session('local') : 'en';
    App::setLocale($local);
    $lang_array = include(base_path('resources/lang/' . $local . '/messages.php'));
    $processed_key = ucfirst(str_replace('_', ' ', Helpers::remove_invalid_charcaters($key)));
    if (!array_key_exists($key, $lang_array)) {
        $lang_array[$key] = $processed_key;
        $str = "<?php return " . var_export($lang_array, true) . ";";
        file_put_contents(base_path('resources/lang/' . $local . '/messages.php'), $str);
        $result = $processed_key;
    } else {
        $result = __('messages.' . $key);
    }
    return $result;
}

function generateOTP()
{   
    // Generates Random number between given pair
    return mt_rand(10000, 99999);
}