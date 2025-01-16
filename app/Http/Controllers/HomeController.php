<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\Foundation\Application;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        /*$this->middleware('auth');*/
    }

    /**
     * Show the application dashboard.
     *
     * @return Renderable
     */
    public function index(): Renderable
    {
        return view('home');
    }

    /**
     * @return Factory|View|Application
     */
    public function about_us(): Factory|View|Application
    {
        return view('about-us');
    }

    /**
     * @return Factory|View|Application
     */
    public function terms_and_conditions(): Factory|View|Application
    {
        return view('terms-and-conditions');
    }

    /**
     * @return Factory|View|Application
     */
    public function privacy_policy(): Factory|View|Application
    {
        return view('privacy-policy');
    }

    public function save_rider_note(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => ['required', 'integer'],
                'message' => ['required'],
            ]);
            
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 400);
            }

            $find_user = User::find($request->user_id);
            if($find_user){
                // update the message
                $find_user->rider_note = $request->message;
                $find_user->save();

                return response()->json([
                    'status' => true,  
                    'message' => "Note updated successfully",
                ], 200);
            }else{
                return response()->json([
                    'status' => false,  
                    'message' => "User not found",
                ], 404);
            }

        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }

    public function get_riders_note(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => ['required', 'integer']
            ]);
            
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 400);
            }

            $find_user = User::find($request->user_id);
            if($find_user){
                // update the message
                return response()->json([
                    'status' => true,
                    'data' => $find_user->rider_note,
                    'message' => "Note fetched successfully",
                ], 200);
            }else{
                return response()->json([
                    'status' => false,  
                    'message' => "User not found",
                ], 404);
            }

        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }
}

