<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\GiftCard;
use App\Models\GiftCardOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Brian2694\Toastr\Facades\Toastr;

class GiftCardController extends Controller
{
    public function create_index(Request $request)
    {
        $giftCards = GiftCard::create([
            'amount'  => $request->amount

        ]);
        return response()->json([
            'status' => true,  
            'message' => "Gift card created successfully",
        ], 200);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'amount' => 'required|numeric',
        ]);
        
        // Store the uploaded image and get the file path
        $path = $request->file('image')->store('gift_cards', 'public');
        
        // Create a new GiftCard record
        GiftCard::create([
            'amount' => $request->amount,
            'image' => $path,
        ]);

        // Redirect
        return redirect()->back();
    }

    public function update_status($id, Request $request)
    {
        $adbanners = GiftCard::find($id);
        if($adbanners){
            $adbanners->is_available = !$adbanners->is_available;
            $adbanners->save();
            return response()->json(1);
        }else{
            return response()->json(0);
        }
    }

    public function index()
    {
        $giftCards = GiftCardOrder::with(['giftCard', 'fromUser'])
            ->where('to_user', auth()->id())->where('is_redeemed', false)
        ->get();
        
        return response()->json([
            'gift_cards' => $giftCards,
        ]);
    }

    public function show_dashboard()
    {
        $giftCards = GiftCard::latest()->get();
        return view('admin-views.gift_card.index', compact('giftCards'));
    }

    public function orders()
    {
        $giftCardOrders = GiftCardOrder::with(['giftCard', 'fromUser', 'toUser'])->latest()->get();
        return view('admin-views.gift_card.orders', compact('giftCardOrders'));
    }

    public function purchase(Request $request)
    {
        // Validate the request inputs first
        $validator = Validator::make($request->all(), [
            'user_email' => ['required', 'email'],
            'amount' => ['required', 'numeric'],
            'gift_card_id' => ['required', 'exists:gift_cards,id'],
        ]);

        // Return validation errors if any
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $giftCard = GiftCard::find($request->gift_card_id);
        $to_user_id = User::where('email', $request->user_email)->value('id');
        
        if(!$giftCard){
            return response()->json([
                'message' => 'Gift card not found!',
            ], 400);
        }
        
        if(!$to_user_id){
            return response()->json([
                'message' => 'User not found.',
            ], 400);
        }

        if (!$giftCard->is_available) {
            return response()->json([
                'message' => 'This gift card is no longer available for purchase.',
            ], 400);
        }

        

        if (auth()->id() === $to_user_id) {
            return response()->json([
                'message' => 'You cannot send a gift card to yourself.'
            ], 400);
        }

        GiftCardOrder::create([
            'from_user' => 1,
            'to_user' => $to_user_id,
            'gift_card_id' => $giftCard->id,
            'amount_paid' => $giftCard->amount,
        ]);

        return response()->json([
            'status' => true,  
            'message' => "Gift card purchase successfully",
        ], 200);
    }
}
