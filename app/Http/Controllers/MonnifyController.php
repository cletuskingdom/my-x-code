<?php

namespace App\Http\Controllers;

use App\Models\PaymentRequest;
use App\Models\User;
use App\Model\Order;
use App\Models\WebHook;
use App\Traits\Processor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MonnifyController extends Controller
{
    use Processor;

    private PaymentRequest $payment;
    private $user;
    private $secretKey;

    public function __construct(PaymentRequest $payment, User $user)
    {
        $config = $this->payment_config('monnify', 'payment_config');
        $values = false;
        if (!is_null($config) && $config->mode == 'live') {
            $values = json_decode($config->live_values);
        } elseif (!is_null($config) && $config->mode == 'test') {
            $values = json_decode($config->test_values);
        }

        if ($values) {
            $config = array(
                'publicKey' => env('MONNIFY_PUBLIC_KEY', $values->public_key),
                'secretKey' => env('MONNIFY_SECRET_KEY', $values->secret_key),
                'paymentUrl' => env('MONNIFY_PAYMENT_URL', 'https://api.monnify.com'),
                'merchantEmail' => env('MERCHANT_EMAIL', $values->merchant_email),
            );
            Config::set('monnify', $config);
            $this->secretKey = env('MONNIFY_SECRET_KEY', $values->secret_key);
        }

        $this->payment = $payment;
        $this->user = $user;
    }


    public function index(Request $request)
    {
        $config = $this->payment_config('monnify', 'payment_config');
        $values = false;
        if (!is_null($config) && $config->mode == 'live') {
            $values = json_decode($config->live_values);
        } elseif (!is_null($config) && $config->mode == 'test') {
            $values = json_decode($config->test_values);
        }

        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|uuid'
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

        // Safely decode payer information with null check
        $payer = null;
        if (!empty($data['payer_information'])) {
            $payer = json_decode($data['payer_information']);
        }

        // If json_decode fails (invalid JSON), handle the error gracefully
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid payer information format.',
            ], 400);
        }

        // Proceed with generating the reference
        $reference = $data->reference;

        return view('payment-gateway.monnify', compact('data', 'payer', 'reference', 'order_id', 'payment_id'));
    }

    // public function index(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'payment_id' => 'required|uuid'
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json($this->response_formatter(GATEWAYS_DEFAULT_400, null, $this->error_processor($validator)), 400);
    //     }

    //     $payment_id = $request['payment_id'];
    //     $data = $this->payment::where(['id' => $request['payment_id']])->where(['is_paid' => 0])->first();
    //     $order_id = $request['order_id']; 
    //     if (!isset($data)) {
    //         return response()->json($this->response_formatter(GATEWAYS_DEFAULT_204), 200);
    //     }

    //     $payer = json_decode($data['payer_information']);

    //     // $reference = Paystack::genTranxRef();
    //     $reference =$data->reference;

    //     return view('payment-gateway.monnify', compact('data', 'payer', 'reference', 'order_id', 'payment_id'));
    // }

    public function handleGatewayCallback(Request $request)
    {
        // json decode request->payment
        Log::info('handleGatewayCallback method called');

        $paymentDetails = json_decode($request->payment_data);
        if ($paymentDetails->status == true) {

            $this->payment::where(['reference' => $paymentDetails->paymentReference])->update([
                'payment_method' => 'monnify',
                'is_paid' => 1,
                'transaction_id' => $paymentDetails->transactionReference,
            ]);
            $data = $this->payment::where(['reference' => $paymentDetails->paymentReference])->first();
            if (isset($data) && function_exists($data->success_hook)) {
                call_user_func($data->success_hook, $data);
            }
            return $this->payment_response($data, 'success');
        }

        $payment_data = $this->payment::where(['reference' => $paymentDetails->paymentReference])->first();
        if (isset($payment_data) && function_exists($payment_data->failure_hook)) {
            call_user_func($payment_data->failure_hook, $payment_data);
        }
        return $this->payment_response($payment_data, 'fail');
    }

    public static function computeSHA512TransactionHash($stringifiedData, $clientSecret)
    {
        $computedHash = hash_hmac('sha512', $stringifiedData, $clientSecret);
        return $computedHash;
    }

    function validateWebhook(Request $request)
    {
        try {
            // Log the incoming webhook request
            $requestBody = $request->getContent();
            Log::info('Monnify webhook received', ['request_body' => $requestBody]);

            // Calculate HMAC-SHA512 hash to validate the webhook
            $computedHash = $this->computeSHA512TransactionHash($requestBody, env("MONNIFY_SECRET_KEY"));
            $receivedSignature = $request->header('Monnify-Signature');

            if ($computedHash !== $receivedSignature) {
                Log::warning('Monnify webhook signature mismatch', ['computed_hash' => $computedHash, 'received_signature' => $receivedSignature]);
                return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 400);
            }


            // Parse the event data
            $event = json_decode($requestBody, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Monnify webhook JSON parse error', ['error' => json_last_error_msg()]);
                return response()->json(['status' => 'error', 'message' => 'Invalid JSON'], 400);
            }

            // Check the event type
            if ($event["eventType"] !== "SUCCESSFUL_TRANSACTION") {
                Log::info('Monnify webhook received an event other than SUCCESSFUL_TRANSACTION', ['event_type' => $event["eventType"]]);
                return response('Expecting SUCCESSFUL_TRANSACTION, but got ' . $event['eventType'], 200);
            }

            DB::beginTransaction();
            try {
                // Find the payment request using the payment ID from the event
                $payment = PaymentRequest::where('id', $event["eventData"]["metaData"]["payment_id"])->first();
                if (!$payment) {
                    Log::warning('Monnify webhook: Payment not found', ['payment_id' => $event["eventData"]["metaData"]["payment_id"]]);
                    return response()->json(['status' => 'error', 'message' => 'Payment not found'], 404);
                }

                // Calculate payment status
                $orderPaymentStatus = 'paid';
                $receivedAmount = (float) $event["eventData"]["amountPaid"];
                $initiatedAmount = (float) $payment->payment_amount;

                if ($receivedAmount !== $initiatedAmount) {
                    $orderPaymentStatus = 'suspicious';
                    Log::warning('Monnify webhook: Payment amount mismatch', ['received_amount' => $receivedAmount, 'initiated_amount' => $initiatedAmount]);
                }

                // Update the order with payment status
                $order = Order::where(['id' => $event["eventData"]["metaData"]["order_id"], 'payment_status' => 'unpaid', 'order_status' => 'pending'])->first();
                if ($order) {
                    // $order->payment_status = $orderPaymentStatus;
                    // $order->order_status = 'confirmed';
                    // $order->confirmed_at = now();
                    // $order->updated_at = now();
                    // $order->save();

                    $created_time = $order->created_at;
                    $estimated_delivery_time = $order->delivery_time;
                    $timeFromDatetime = Carbon::parse($created_time)->format('H:i:s');
                    $time1 = Carbon::parse($timeFromDatetime);
                    $time2 = Carbon::parse($estimated_delivery_time);
                    $timeDifferenceInMinutes = $time1->diffInMinutes($time2);


                    $order->payment_status = $orderPaymentStatus;
                    $order->order_status = 'confirmed';
                    $order->confirmed_at = now();
                    $order->updated_at = now();
                    $order->delivery_time = Carbon::now()
                        ->addMinutes($timeDifferenceInMinutes)
                        ->format('H:i:s');
                    $order->save();

                    Log::info('Monnify webhook: Order updated', ['order_id' => $order->id, 'payment_status' => $orderPaymentStatus]);
                } else {
                    Log::warning('Monnify webhook: Order not found', ['order_id' => $event["eventData"]["metaData"]["order_id"]]);
                    return response()->json(['status' => 'error', 'message' => 'Order not found'], 404);
                }

                // Log the webhook data in WebHook table
                $cc_data = new WebHook();
                $cc_data->log = $requestBody;
                $cc_data->name = "Monnify";
                $cc_data->status = $event["eventData"]["paymentStatus"];
                $cc_data->remark = $event["eventData"]["paymentStatus"];
                $cc_data->order_id = $event["eventData"]["metaData"]["order_id"];
                $cc_data->save();

                DB::commit();

                return response()->json(['status' => 'success', 'message' => 'Webhook processed successfully'], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Monnify webhook processing error', ['error' => $e->getMessage()]);
                return response()->json(['status' => 'error', 'message' => 'Internal Server Error'], 500);
            }
        } catch (\Exception $e) {
            Log::error('Monnify webhook validation error', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => 'Internal Server Error'], 500);
        }
        // Send 200 OK response
        // http_response_code(200);
    }

}

