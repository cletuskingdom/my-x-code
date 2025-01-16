@extends('payment-gateway.layouts.master')

@push('script')

@endpush

@section('content')
    <center><h1>Please do not refresh this page...</h1></center>
    <script type="text/javascript" src="https://sdk.monnify.com/plugin/monnify.js"></script>
    <script>

        var isFlutterWebViewReady = false;
        function payWithMonnify() {
            MonnifySDK.initialize({
                amount: {{$data->payment_amount}},
                currency: "NGN",
                reference: "{{$reference}}",
                customerFullName: "{{$payer->name}}",
                customerEmail: "{{$payer->email}}",
                apiKey: "{{env('MONNIFY_PUBLIC_KEY')}}",
                contractCode: "{{env('MONIFY_CONTRACT_CODE')}}",
                paymentDescription: "Food Purchase",
                metadata: {
                    "name": "{{$payer->name}}",
                    "reference": "{{$reference}}",
                    "order_id": "{{$order_id}}",
                "payment_id":"{{$payment_id}}",
                },
                paymentMethods: [
                    "CARD",
                    "ACCOUNT_TRANSFER",
                    "USSD",
                    // "PHONE_NUMBER"
                ],
                onLoadStart: () => {
                    console.log("loading has started");
                },
                onLoadComplete: () => {
                    console.log("SDK is UP");
                },
                onComplete: function(response) {
                    console.log(response);
                    // make an api call using javascript to the url that I will provide. In the APi call send the response object
                    fetch("{{route('monnify.callback')}}?payment_id={{$data->id}}&payment_data="+JSON.stringify(response), {
                        method: "GET",
                        
                    }).then(function(feedback) {

                        // if(isFlutterWebViewReady){
                        //     var arg = {url: feedback.url}
                        //     window.flutter_inappwebview.callHandler('successful', ...arg);
                        // }
                        
                        // console.log(feedback.url)
                        // redirect to the url
                        location.href = feedback.url;
                        
                    })
                },
                onClose: function(data) {
                    if(isFlutterWebViewReady){
                        window.flutter_inappwebview.callHandler('close');
                    }
                    //Implement what should happen when the modal is closed here
                    console.log(data);
                }
            });
        }

        document.addEventListener("DOMContentLoaded", function () {
           return payWithMonnify();
        });


        window.addEventListener("flutterInAppWebViewPlatformReady", function(event) {
            isFlutterWebViewReady = true;
        });
    </script>

    
@endsection