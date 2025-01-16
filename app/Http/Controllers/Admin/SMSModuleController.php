<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\BusinessSetting;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\DB;


class SMSModuleController extends Controller
{
    public function __construct(
        private BusinessSetting $business_setting
    )
    {
    }

    /**
     * @return Renderable
     */
    public function sms_index(): Renderable
    {
        $published_status = 0; // Set a default value
        $payment_published_status = config('get_payment_publish_status');
        if (isset($payment_published_status[0]['is_published'])) {
            $published_status = $payment_published_status[0]['is_published'];
        }

        $routes = config('addon_admin_routes');
        $desiredName = 'sms_setup';
        $payment_url = '';

        foreach ($routes as $routeArray) {
            foreach ($routeArray as $route) {
                if ($route['name'] === $desiredName) {
                    $payment_url = $route['url'];
                    break 2;
                }
            }
        }
        $data_values= Setting::where('settings_type','sms_config')->whereIn('key_name', ['twilio','nexmo','2factor','msg91', 'signal_wire'])->get() ?? [];

        return view('admin-views.business-settings.sms-index',  compact('published_status', 'payment_url', 'data_values'));
    }

    public function sms_update(Request $request, $module)
    {
        $validation = [
            'gateway' => 'required|in:twilio,nexmo,2factor,msg91,signal_wire,',
        ];
        //dd($request->all());

        $validation_data = [];
        if ($module == 'twilio') {
            $validation_data = [
                'status' => 'required|in:1,0',
                'sid' => 'required_if:status,1',
                'messaging_service_sid' => 'required_if:status,1',
                'token' => 'required_if:status,1',
                'from' => 'required_if:status,1',
                'otp_template' => 'required_if:status,1'
            ];
        } elseif ($module == 'nexmo') {
            $validation_data = [
                'status' => 'required|in:1,0',
                'api_key' => 'required_if:status,1',
                'api_secret' => 'required_if:status,1',
                'token' => 'required_if:status,1',
                'from' => 'required_if:status,1',
                'otp_template' => 'required_if:status,1'
            ];
        } elseif ($module == '2factor') {
            $validation_data = [
                'status' => 'required|in:1,0',
                'api_key' => 'required_if:status,1'
            ];
        } elseif ($module == 'msg91') {
            $validation_data = [
                'status' => 'required|in:1,0',
                'template_id' => 'required_if:status,1',
                'auth_key' => 'required_if:status,1',
            ];
        } elseif ($module == 'signal_wire') {
            $validation_data = [
                'status' => 'required|in:1,0',
                'project_id' => 'required_if:status,1',
                'token' => 'required_if:status,1',
                'space_url' => 'required_if:status,1',
                'from' => 'required_if:status,1',
                'otp_template' => 'required_if:status,1',
            ];
        }

        $validation = $request->validate(array_merge($validation, $validation_data));


        if ($module == 'twilio') {
            $additional_data = [
                'status' => $request['status'],
                'sid' => $request['sid'],
                'messaging_service_sid' => $request['messaging_service_sid'],
                'token' => $request['token'],
                'from' => $request['from'],
                'otp_template' => $request['otp_template'],
            ];

        } elseif ($module == 'nexmo') {
            $additional_data = [
                'status' =>$request['status'],
                'api_key' => $request['api_key'],
                'api_secret' => $request['api_secret'],
                'token' => $request['token'],
                'from' => $request['from'],
                'otp_template' => $request['otp_template'],
            ];

        } elseif ($module == '2factor') {
            $additional_data = [
                'status' => $request['status'],
                'api_key' => $request['api_key'],
            ];
        } elseif ($module == 'msg91') {
            $additional_data = [
                'status' => $request['status'],
                'template_id' => $request['template_id'],
                'auth_key' => $request['auth_key'],
            ];
        } elseif ($module == 'signal_wire') {
            $additional_data = [
                'status' => $request['status'],
                'project_id' => $request['project_id'],
                'token' => $request['token'],
                'space_url' => $request['space_url'],
                'from' => $request['from'],
                'otp_template' => $request['otp_template'],
            ];
        }

        $data= [
            'gateway' => $module ,
            'mode' =>  isset($request['status']) == 1  ?  'live': 'test'
        ];

        $credentials= json_encode(array_merge($data, $additional_data));
        DB::table('addon_settings')->updateOrInsert(['key_name' => $module, 'settings_type' => 'sms_config'], [
            'key_name' => $module,
            'live_values' => $credentials,
            'test_values' => $credentials,
            'settings_type' => 'sms_config',
            'mode' => isset($request['status']) == 1  ?  'live': 'test',
            'is_active' => isset($request['status']) == 1  ?  1: 0 ,
        ]);

        $sms_gateway_array = [
            'twilio','nexmo','2factor','msg91', 'signal_wire'
        ];

        if ($request['status'] == 1) {
            foreach ($sms_gateway_array as $gateway) {
                if ($module != $gateway) {
                    $keep = Setting::where(['key_name' => $gateway, 'settings_type' => 'sms_config'])->first();
                    if (isset($keep)) {
                        $hold = $keep->live_values;
                        $hold['status'] = 0;
                        Setting::where(['key_name' => $gateway, 'settings_type' => 'sms_config'])->update([
                            'live_values' => $hold,
                            'test_values' => $hold,
                            'is_active' => 0,
                        ]);
                    }
                }
            }

            $firebase_otp = Helpers::get_business_settings('firebase_otp_verification');

            DB::table('business_settings')->updateOrInsert(['key' => 'firebase_otp_verification'], [
                'value' => json_encode([
                    'status'  => 0,
                    'web_api_key' => $firebase_otp['web_api_key'],
                ]),
            ]);
        }
        return back();
    }

//    /**
//     * @param Request $request
//     * @param $module
//     * @return RedirectResponse
//     */
//    public function sms_update(Request $request, $module): RedirectResponse
//    {
//        if ($module == 'twilio_sms') {
//            $this->business_setting->updateOrInsert(['key' => 'twilio_sms'], [
//                'key' => 'twilio_sms',
//                'value' => json_encode([
//                    'status' => $request['status'],
//                    'sid' => $request['sid'],
//                    'messaging_service_sid' => $request['messaging_service_sid'],
//                    'token' => $request['token'],
//                    'from' => $request['from'],
//                    'otp_template' => $request['otp_template'],
//                ]),
//                'created_at' => now(),
//                'updated_at' => now(),
//            ]);
//
//        } elseif ($module == 'nexmo_sms') {
//            $this->business_setting->updateOrInsert(['key' => 'nexmo_sms'], [
//                'key' => 'nexmo_sms',
//                'value' => json_encode([
//                    'status' => $request['status'],
//                    'api_key' => $request['api_key'],
//                    'api_secret' => $request['api_secret'],
//                    'signature_secret' => '',
//                    'private_key' => '',
//                    'application_id' => '',
//                    'from' => $request['from'],
//                    'otp_template' => $request['otp_template']
//                ]),
//                'created_at' => now(),
//                'updated_at' => now(),
//            ]);
//
//        } elseif ($module == '2factor_sms') {
//            $this->business_setting->updateOrInsert(['key' => '2factor_sms'], [
//                'key' => '2factor_sms',
//                'value' => json_encode([
//                    'status' => $request['status'],
//                    'api_key' => $request['api_key'],
//                ]),
//                'created_at' => now(),
//                'updated_at' => now(),
//            ]);
//
//        } elseif ($module == 'msg91_sms') {
//            $this->business_setting->updateOrInsert(['key' => 'msg91_sms'], [
//                'key' => 'msg91_sms',
//                'value' => json_encode([
//                    'status' => $request['status'],
//                    'template_id' => $request['template_id'],
//                    'authkey' => $request['authkey'],
//                ]),
//                'created_at' => now(),
//                'updated_at' => now(),
//            ]);
//
//        } elseif ($module == 'signalwire_sms') {
//            $this->business_setting->updateOrInsert(['key' => 'signalwire_sms'], [
//                'key' => 'signalwire_sms',
//                'value' => json_encode([
//                    'status' => $request['status'],
//                    'project_id' => $request['project_id'],
//                    'token' => $request['token'],
//                    'space_url' => $request['space_url'],
//                    'from' => $request['from'],
//                    'otp_template' => $request['otp_template'],
//                ]),
//                'created_at' => now(),
//                'updated_at' => now(),
//            ]);
//        }
//
//        if ($request['status'] == 1) {
//            $config = Helpers::get_business_settings('twilio_sms');
//            if (isset($config) && $module != 'twilio_sms') {
//                $this->business_setting->updateOrInsert(['key' => 'twilio_sms'], [
//                    'key' => 'twilio_sms',
//                    'value' => json_encode([
//                        'status' => 0,
//                        'sid' => $config['sid'],
//                        'token' => $config['token'],
//                        'from' => $config['from'],
//                        'otp_template' => $config['otp_template'],
//                    ]),
//                    'created_at' => now(),
//                    'updated_at' => now(),
//                ]);
//            }
//
//            $config = Helpers::get_business_settings('nexmo_sms');
//            if (isset($config) && $module != 'nexmo_sms') {
//                $this->business_setting->updateOrInsert(['key' => 'nexmo_sms'], [
//                    'key' => 'nexmo_sms',
//                    'value' => json_encode([
//                        'status' => 0,
//                        'api_key' => $config['api_key'],
//                        'api_secret' => $config['api_secret'],
//                        'signature_secret' => '',
//                        'private_key' => '',
//                        'application_id' => '',
//                        'from' => $config['from'],
//                        'otp_template' => $config['otp_template']
//                    ]),
//                    'created_at' => now(),
//                    'updated_at' => now(),
//                ]);
//            }
//
//            $config = Helpers::get_business_settings('2factor_sms');
//            if (isset($config) && $module != '2factor_sms') {
//                $this->business_setting->updateOrInsert(['key' => '2factor_sms'], [
//                    'key' => '2factor_sms',
//                    'value' => json_encode([
//                        'status' => 0,
//                        'api_key' => $config['api_key'],
//                    ]),
//                    'created_at' => now(),
//                    'updated_at' => now(),
//                ]);
//            }
//
//            $config = Helpers::get_business_settings('msg91_sms');
//            if (isset($config) && $module != 'msg91_sms') {
//                $this->business_setting->updateOrInsert(['key' => 'msg91_sms'], [
//                    'key' => 'msg91_sms',
//                    'value' => json_encode([
//                        'status' => 0,
//                        'template_id' => $config['template_id'],
//                        'authkey' => $config['authkey'],
//                    ]),
//                    'created_at' => now(),
//                    'updated_at' => now(),
//                ]);
//            }
//
//            $config = Helpers::get_business_settings('signalwire_sms');
//            if (isset($config) && $module != 'signalwire_sms') {
//                $this->business_setting->updateOrInsert(['key' => 'signalwire_sms'], [
//                    'key' => 'signalwire_sms',
//                    'value' => json_encode([
//                        'status' => 0,
//                        'project_id' => $config['project_id'],
//                        'token' => $config['token'],
//                        'space_url' => $config['space_url'],
//                        'from' => $config['from'],
//                        'otp_template' => $config['otp_template'],
//                    ]),
//                    'created_at' => now(),
//                    'updated_at' => now(),
//                ]);
//            }
//        }
//
//        return back();
//    }
}
