<?php

use App\Http\Controllers\Admin\BusinessSettingsController;
use App\Http\Controllers\Api\V1\Auth\KitchenLoginController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\DeliverymanController;
use App\Http\Controllers\Api\V1\FCMNotificationController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\GiftCardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SocialLoginController;
use App\Mail\{EmailVerification, PasswordResetMail};
use App\Model\BusinessSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;




Route::get('checkout', 'PaymentController@checkout');

Route::group(['namespace' => 'Api\V1', 'middleware' => 'localization'], function () {
    ## authentication routes
    Route::group(['prefix' => 'auth', 'namespace' => 'Auth'], function () {
        Route::post('registration', 'CustomerAuthController@registration');
        Route::post('login', 'CustomerAuthController@login');
        Route::post('send-otp', 'CustomerAuthController@sendOTP');
        
        Route::post('social-customer-login', 'CustomerAuthController@social_customer_login');

        Route::post('check-phone', 'CustomerAuthController@check_phone');
        Route::post('verify-phone', 'CustomerAuthController@verify_phone');

        Route::post('check-email', 'CustomerAuthController@check_email');
        Route::post('verify-email', 'CustomerAuthController@verify_email');

        Route::post('firebase-auth-verify', 'CustomerAuthController@firebase_auth_verify');

        Route::post('forgot-password', 'PasswordResetController@reset_password_request');
        Route::post('verify-token', 'PasswordResetController@verify_token');
        Route::put('reset-password', 'PasswordResetController@reset_password_submit');

        Route::group(['prefix' => 'delivery-man'], function () {
            Route::post('register', 'DeliveryManLoginController@registration');
            Route::post('login', 'DeliveryManLoginController@login');
        });

        Route::group(['prefix' => 'kitchen', 'middleware' => 'app_activate:' . APPS['kitchen_app']['software_id']], function () {
            Route::post('login', 'KitchenLoginController@login');
            Route::post('logout', 'KitchenLoginController@logout')->middleware('auth:kitchen_api');
        });

        Route::post('{driver}', [SocialLoginController::class, 'handleLogin'])->where('driver', 'apple|google')->name('social_login');
    });

    Route::group(['prefix' => 'delivery-man', 'middleware' => 'deliveryman_is_active'], function () {
        Route::get('profile', 'DeliverymanController@get_profile');
        Route::get('current-orders', 'DeliverymanController@get_current_orders');
        Route::get('all-orders', 'DeliverymanController@get_all_orders');
        Route::post('record-location-data', 'DeliverymanController@record_location_data');
        Route::get('order-delivery-history', 'DeliverymanController@get_order_history'); // not used
        Route::put('update-order-status', 'DeliverymanController@update_order_status');
        Route::put('update-payment-status', 'DeliverymanController@order_payment_status_update');
        Route::get('order-details', 'DeliverymanController@get_order_details');
        //Route::get('last-location', 'DeliverymanController@get_last_location');
        Route::put('update-fcm-token', 'DeliverymanController@update_fcm_token');
        Route::get('order-model', 'DeliverymanController@order_model');

        //delivery-man message
        Route::group(['prefix' => 'message'], function () {
            Route::post('get-message', 'ConversationController@get_order_message_for_dm');
            Route::post('send/{sender_type}', 'ConversationController@store_message_by_order');
        });

        Route::group(['prefix' => 'reviews', 'middleware' => ['auth:api']], function () {
            Route::get('/{delivery_man_id}', 'DeliveryManReviewController@get_reviews'); //not used
            Route::get('rating/{delivery_man_id}', 'DeliveryManReviewController@get_rating'); //not used
            // Route::post('/submit', 'DeliveryManReviewController@submit_review');
        });
    });

    Route::middleware('auth:api')->post('delivery-man/reviews/submit', 'DeliveryManReviewController@submit_review');
    Route::middleware('auth:api')->get('delivery-man/last-location', 'DeliverymanController@get_last_location');

    Route::group(['prefix' => 'config'], function () {
        Route::get('/', 'ConfigController@configuration');
        Route::get('table', 'TableConfigController@configuration');
        Route::get('get-direction-api', 'ConfigController@direction_api');

    });

    Route::group(['prefix' => 'products', 'middleware' => 'branch_adder'], function () {
        Route::get('latest', 'ProductController@get_latest_products');
        Route::get('popular', 'ProductController@get_popular_products');
        Route::get('set-menu', 'ProductController@get_set_menus');
        Route::get('search', 'ProductController@get_searched_products');
        Route::get('details/{id}', 'ProductController@get_product');
        Route::get('related-products/{product_id}', 'ProductController@get_related_products');
        Route::get('reviews/{product_id}', 'ProductController@get_product_reviews');
        Route::get('rating/{product_id}', 'ProductController@get_product_rating');
        Route::post('reviews/submit', 'ProductController@submit_product_review')->middleware('auth:api');

        Route::get('discounted', [ProductController::class, 'getGroupedProducts']);
        Route::get('most-ordered', [ProductController::class, 'getMostOrderedProducts']);

        Route::get('search-new', 'ProductController@get_searched_products_new');
    });

    Route::group(['prefix' => 'banners'], function () {
        Route::get('/', 'BannerController@get_banners');
    });

    Route::group(['prefix' => 'notifications'], function () {
        Route::get('/', 'NotificationController@get_notifications');
    });

    Route::group(['prefix' => 'categories'], function () {
        Route::get('/', 'CategoryController@get_categories');
        Route::get('menus', 'CategoryController@get_menus');
        Route::get('product-groups', 'CategoryController@get_product_groups');
        Route::get('addon_categories', 'CategoryController@get_addon_categories');
        Route::get('childes/{category_id}', 'CategoryController@get_childes');
        Route::get('products/{category_id}', 'CategoryController@get_products')->middleware('branch_adder');
        Route::get('products/{category_id}/all', 'CategoryController@get_all_products')->middleware('branch_adder');
    });

    Route::group(['prefix' => 'customer', 'middleware' => ['auth:api', 'is_active']], function () {
        Route::get('info', 'CustomerController@info');
        Route::put('update-profile', 'CustomerController@update_profile');
        Route::put('cm-firebase-token', 'CustomerController@update_cm_firebase_token')->withoutMiddleware(['auth:api', 'is_active']);
        Route::get('transaction-history', 'CustomerController@get_transaction_history');

        Route::namespace('Auth')->group(function () {
            Route::delete('remove-account', 'CustomerAuthController@remove_account');
        });

        Route::group(['prefix' => 'address'], function () {
            Route::get('list', 'CustomerController@address_list')->withoutMiddleware(['auth:api', 'is_active']);
            Route::post('add', 'CustomerController@add_new_address')->withoutMiddleware(['auth:api', 'is_active']);
            Route::put('update/{id}', 'CustomerController@update_address')->withoutMiddleware(['auth:api', 'is_active']);
            Route::delete('delete', 'CustomerController@delete_address')->withoutMiddleware(['auth:api', 'is_active']);
        });

        Route::group(['prefix' => 'order'], function () {
            Route::get('list', 'OrderController@get_order_list')->withoutMiddleware(['auth:api', 'is_active']);
            Route::get('details', 'OrderController@get_order_details')->withoutMiddleware(['auth:api', 'is_active']);
            Route::post('place', 'OrderController@place_order')->withoutMiddleware(['auth:api', 'is_active']);
            Route::put('cancel', 'OrderController@cancel_order')->withoutMiddleware(['auth:api', 'is_active']);
            Route::get('track', 'OrderController@track_order')->withoutMiddleware(['auth:api', 'is_active']);
            Route::get('status', 'OrderController@order_status')->withoutMiddleware(['auth:api', 'is_active']);
            Route::put('payment-method', 'OrderController@update_payment_method')->withoutMiddleware(['auth:api', 'is_active']);
            Route::post('guest-track', 'OrderController@guset_track_order')->withoutMiddleware(['auth:api', 'is_active']);
            Route::post('details-guest', 'OrderController@get_guest_order_details')->withoutMiddleware(['auth:api', 'is_active']);
        });
        // Chatting
        Route::group(['prefix' => 'message'], function () {
            //customer-admin
            Route::get('get-admin-message', 'ConversationController@get_admin_message');
            Route::post('send-admin-message', 'ConversationController@store_admin_message');
            //customer-deliveryman
            Route::get('get-order-message', 'ConversationController@get_message_by_order');
            Route::post('send/{sender_type}', 'ConversationController@store_message_by_order');
        });

        Route::group(['prefix' => 'wish-list'], function () {
            Route::get('/', 'WishlistController@wish_list')->middleware('branch_adder');
            Route::post('add', 'WishlistController@add_to_wishlist');
            Route::delete('remove', 'WishlistController@remove_from_wishlist');
        });

        Route::post('transfer-point-to-wallet', 'CustomerWalletController@transfer_loyalty_point_to_wallet');
        Route::get('wallet-transactions', 'CustomerWalletController@wallet_transactions');
        Route::get('loyalty-point-transactions', 'LoyaltyPointController@point_transactions');
        Route::get('bonus/list', 'CustomerWalletController@wallet_bonus_list');

        Route::post('/update_phone', [CustomerController::class, 'update_phone']);
        Route::post('/update_phone_otp', [CustomerController::class, 'update_phone_otp']);
        Route::post('logout', [KitchenLoginController::class, 'logout']);
    });

    Route::group(['prefix' => 'banners'], function () {
        Route::get('/', 'BannerController@get_banners');
    });

    Route::group(['prefix' => 'coupon'], function () {
        Route::get('list', 'CouponController@list');
        Route::get('apply', 'CouponController@apply');
    });

    //map api
    Route::group(['prefix' => 'mapapi'], function () {
        Route::get('place-api-autocomplete', 'MapApiController@place_api_autocomplete');
        Route::get('distance-api', 'MapApiController@distance_api');
        Route::get('place-api-details', 'MapApiController@place_api_details');
        Route::get('geocode-api', 'MapApiController@geocode_api');
    });

    Route::post('subscribe-newsletter', 'CustomerController@subscribe_newsletter');

    Route::get('pages', 'PageController@index');

    Route::group(['prefix' => 'table', 'middleware' => 'app_activate:' . APPS['table_app']['software_id']], function () {
        Route::get('list', 'TableController@list');
        Route::get('product/type', 'TableController@filter_by_product_type');
        Route::get('promotional/page', 'TableController@get_promotional_page');
        Route::post('order/place', 'TableController@place_order');
        Route::get('order/details', 'TableController@get_order_details');
        Route::get('order/list', 'TableController@table_order_list');
    });

    Route::group(['prefix' => 'kitchen', 'middleware' => 'auth:kitchen_api', 'app_activate:' . APPS['kitchen_app']['software_id']], function () {
        Route::get('profile', 'KitchenController@get_profile');
        Route::get('order/list', 'KitchenController@get_order_list');
        Route::get('order/search', 'KitchenController@search');
        Route::get('order/filter', 'KitchenController@filter_by_status');
        Route::get('order/details', 'KitchenController@get_order_details');
        Route::put('order/status', 'KitchenController@change_status');
        Route::put('update-fcm-token', 'KitchenController@update_fcm_token');
    });

    Route::group(['prefix' => 'guest'], function () {
        Route::post('/add', 'GuestUserController@guest_store');
    });

    Route::group(['prefix' => 'offline-payment-method'], function () {
        Route::get('/list', 'OfflinePaymentMethodController@list');
    });

    Route::post('add_update_service_fee', function(Request $request) {
        $validator = Validator::make($request->all(), [
            'service_fee' => ['required'],
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }
    
        // Check if the BusinessSetting exists
        $business_setting = BusinessSetting::where('key', 'service_fee')->first();
    
        if ($business_setting === null) {
            // Create the BusinessSetting if it doesn't exist
            BusinessSetting::create([
                'key' => 'service_fee',
                'value' => $request->service_fee
            ]);
    
            return response()->json([
                'status' => true,  
                'message' => "Service fee created successfully",
            ], 200);
        } else {
            // Update the existing BusinessSetting
            $business_setting->update([
                'value' => $request->service_fee
            ]);
    
            return response()->json([
                'status' => true,  
                'message' => "Service fee updated successfully",
            ], 200);
        }
    });
    Route::post('riders_note', [HomeController::class, 'save_rider_note']);
    Route::get('get_riders_note', [HomeController::class, 'get_riders_note']);
    

    Route::post('/test-route', [FCMNotificationController::class, 'test_route']);
    Route::get('/test-paga', [BusinessSettingsController::class, 'test_paga']);

    Route::get('/notify-kitchen/first-time/{user_id}', [KitchenLoginController::class, 'notify_kitchen_first_time']);
    Route::post('/catering-service', [CustomerController::class, 'catering_service_store']);
    Route::get('/get-adsbanners', [BusinessSettingsController::class, 'get_ads_banner']);

    Route::get('/get-rider/{id}', [DeliverymanController::class, 'get_rider_phone']);

    // I want to displa suggestion based on the category of the supplied IDs
    Route::get('/cart/suggestions/{id}', [CustomerController::class, 'getSuggestions']);

    Route::get('/last_order', [OrderController::class, 'last_order']);
    Route::post('/set_general_surge', [BusinessSettingsController::class, 'set_general_surge']);

    // i want to display suggestion based on the supplied category IDs supplied
    Route::get('/cart/special-suggestion/{type}', [CustomerController::class, 'getSpecialSuggestions']);

    // Route::post('/gift-cards/purchase', [GiftCardController::class, 'purchase']);
    // Route::get('/gift-cards/{id}/unused', [GiftCardController::class, 'unused']);

    Route::post('/create-gift-card', [GiftCardController::class, 'create_index']);
    Route::get('/get-gift-cards', [GiftCardController::class, 'index']);
    Route::post('/gift-card/purchase/', [GiftCardController::class, 'purchase']);
});
