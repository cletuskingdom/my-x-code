<?php

namespace App\Http\Controllers;

use App\Model\Order;
use App\Models\PaymentRequest;
use App\Models\User;
use App\Models\WebHook;
use App\Traits\Processor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Unicodeveloper\Paystack\Facades\Paystack;
use Carbon\Carbon;

class PaystackController extends Controller
{
    use Processor;

    private PaymentRequest $payment;
    private $user;
    private $secretKey;

    public function __construct(PaymentRequest $payment, User $user)
    {
        $config = $this->payment_config('paystack', 'payment_config');
        $values = false;
        if (!is_null($config) && $config->mode == 'live') {
            $values = json_decode($config->live_values);
        } elseif (!is_null($config) && $config->mode == 'test') {
            $values = json_decode($config->test_values);
        }

        if ($values) {
            $config = array(
                'publicKey' => env('PAYSTACK_PUBLIC_KEY', $values->public_key),
                'secretKey' => env('PAYSTACK_SECRET_KEY', $values->secret_key),
                'paymentUrl' => env('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co'),
                'merchantEmail' => env('MERCHANT_EMAIL', $values->merchant_email),
            );
            Config::set('paystack', $config);
            $this->secretKey = env('PAYSTACK_SECRET_KEY', $values->secret_key);
        }

        $this->payment = $payment;
        $this->user = $user;

    }

    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|uuid',
        ]);


        if ($validator->fails()) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_400, null, $this->error_processor($validator)), 400);
        }

        $payment_id = $request['payment_id'];
        $data = $this->payment::where(['id' => $payment_id])->where(['is_paid' => 0])->first();
        $order_id = $request['order_id'];
        if (!isset($data)) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_204), 200);
        }

        $payer = json_decode($data['payer_information']);

        // $reference = Paystack::genTranxRef();
        $reference = $data->reference;

        return view('payment-gateway.paystack', compact('data', 'payer', 'reference', 'order_id', 'payment_id'));
    }

    public function redirectToGateway(Request $request)
    {
        return Paystack::getAuthorizationUrl()->redirectNow();
    }

    public function handleGatewayCallback(Request $request)
    {
        $paymentDetails = Paystack::getPaymentData();
        if ($paymentDetails['status'] == true) {
            // dd($paymentDetails['data']);
            // $this->payment::where(['attribute_id' => $paymentDetails['data']['order_id']])->update([
            //     'payment_method' => 'paystack',
            //     'is_paid' => 1,
            //     'transaction_id' => $request['trxref'],
            // ]);
            $this->payment::where(['reference' => $paymentDetails['data']['reference']])->update([
                'payment_method' => 'paystack',
                'is_paid' => 1,
                'transaction_id' => $request['trxref'],
            ]);
            $data = $this->payment::where(['reference' => $paymentDetails['data']['reference']])->first();
            if (isset($data) && function_exists($data->success_hook)) {
                call_user_func($data->success_hook, $data);
            }
            return $this->payment_response($data, 'success');
        }

        $payment_data = $this->payment::where(['reference' => $paymentDetails['data']['reference']])->first();
        if (isset($payment_data) && function_exists($payment_data->failure_hook)) {
            call_user_func($payment_data->failure_hook, $payment_data);
        }
        return $this->payment_response($payment_data, 'fail');
    }

    function validateWebhook(Request $request)
    {
        $requestBody = $request->getContent();
        $cc_data = new WebHook();
        $cc_data->log = $requestBody;
        $cc_data->name = "Paystack";
        $cc_data->save();

        // Calculate HMAC-SHA512 hash
        $hash = hash_hmac('sha512', $requestBody, $this->secretKey);


        // check for a header key called X-PAYSTACK-SIGNATURE using if statetment
        if (!array_key_exists('HTTP_X_PAYSTACK_SIGNATURE', $_SERVER)) {
            $cc_data->status = 'fail';
            $cc_data->remark = "Missing signature. Event not from Paystack.";
            $cc_data->save();
            http_response_code(400);
        }

        // Check if the calculated hash matches the header signature
        $signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'];
        if ($hash !== $signature) {
            $cc_data->status = 'fail';
            $cc_data->remark = "Invalid signature. Event not from Paystack.";
            $cc_data->save();
            http_response_code(400);
        }


        $event = json_decode($requestBody, true);
        // update status with the event status
        $cc_data->status = $event["data"]["status"];
        $cc_data->remark = $event['event'];
        $cc_data->order_id = $event["data"]["metadata"]["order_id"];
        $cc_data->save();

        $order_payment_status = 'unpaid';
        // Do something with the event data
        // if($event['event'] == "charge.success" && $event["data"]["status"] == "success"){
        $payment = PaymentRequest::where(['id' => $event["data"]["metadata"]["payment_id"]])->first();

        if ($payment != null) {

            $order_payment_status = 'paid';
            $received_amount = (float) $event["data"]["amount"] / 100;
            $initiated_amount = (float) $payment->payment_amount;
            if ($received_amount != $initiated_amount) {
                $order_payment_status = 'suspicious';
            }
        }
        $order = Order::where(['id' => $event["data"]["metadata"]["order_id"], 'payment_status' => 'unpaid', 'order_status' => 'pending'])->first();

        if ($order != null) {

            $created_time = $order->created_at;
            $estimated_delivery_time = $order->delivery_time;
            $timeFromDatetime = Carbon::parse($created_time)->format('H:i:s');
            $time1 = Carbon::parse($timeFromDatetime);
            $time2 = Carbon::parse($estimated_delivery_time);
            $timeDifferenceInMinutes = $time1->diffInMinutes($time2);

            $cc_data->log = $order;
            $order->payment_status = $order_payment_status;
            $order->order_status = 'confirmed';
            $order->confirmed_at = now();
            $order->updated_at = now();
            $order->delivery_time = Carbon::now()
                ->addMinutes($timeDifferenceInMinutes)
                ->format('H:i:s');
            $order->save();
        } else {
            $cc_data->remark = "Order not found";
        }
        $cc_data->save();
        // } 
        // http_response_code(200);

        http_response_code(200);
        return response('Webhook processed successfully.', 200);

        // Send 200 OK response
        // http_response_code(200);
    }

}
