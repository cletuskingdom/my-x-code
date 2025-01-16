<?php

namespace App\Http\Controllers;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Mail\WelcomeMail;
use App\Models\User;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Google_Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialLoginController extends Controller
{
    public function handleLogin($driver, Request $request)
    {
        if ($driver == 'google') {
            $validator = Validator::make($request->all(), [
                'token' => ['required'],
            ]);
    
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 400);
            }
            $token = $request->token;

            try {
                $verify_token = Http::get('https://oauth2.googleapis.com/tokeninfo?access_token=' . $token);

                if ($verify_token->successful()) {
                    $get_user = Http::withToken($token)->get('https://people.googleapis.com/v1/people/me?personFields=names,emailAddresses,photos,phoneNumbers');

                    if ($get_user->successful()) {
                        $userData = $get_user->json();
                        $email = $userData['emailAddresses'][0]['value'] ?? null;
                        $check_if_email_exist = User::whereEmail($email)->first();

                        if ($check_if_email_exist) {
                            $token = $check_if_email_exist->createToken('SocialAuth')->accessToken;

                            // Send a welcome email containing their login details
                            return response()->json([
                                'token' => $token,
                                'message' => "Existing"
                            ], 200);
                        } else {
                            $user = User::create([
                                'f_name' => Helpers::sanitize_input($userData['names'][0]['givenName']),
                                'l_name' => Helpers::sanitize_input($userData['names'][0]['familyName']),
                                'email' => Helpers::sanitize_input($userData['emailAddresses'][0]['value']),
                                'phone' => Helpers::sanitize_input(Str::random(11, '0123456789')),
                                'password' => bcrypt(Helpers::sanitize_input($userData['names'][0]['metadata']['source']['id'])),
                                'temporary_token' => $userData['names'][0]['metadata']['source']['id'],
                                'refer_code' => Str::random('20'),
                                'refer_by' => null,
                                'language_code' => Helpers::sanitize_input($request->header('X-localization')) ?? 'en',
                                'dob' => null,
                            ]);
                            $token = $user->createToken('SocialAuth')->accessToken;

                            // Send a welcome email containing their login details
                            $details = [
                                'name' => Helpers::sanitize_input($userData['names'][0]['givenName']),
                                'app_name' => env('APP_NAME'),
                                'default_password' => Helpers::sanitize_input($userData['names'][0]['metadata']['source']['id'])
                            ];

                            $subject = 'Welcome to ' . env('APP_NAME');
                            Mail::to(Helpers::sanitize_input($userData['emailAddresses'][0]['value']))->send(new WelcomeMail($details, $subject));

                            return response()->json([
                                'token' => $token,
                                'message' => "New"
                            ], 200);
                        }
                    }
                }
                return response()->json(['errors' => 'Invalid Token'], 404);
            } catch (\Exception $e) {
                return response()->json(['errors' => 'An error occurred during Google sign-in. Please try again later (' . $e->getMessage() . ')'], 500);
            }
        }elseif ($driver == 'apple') {
            $identityToken = $request->input('identityToken');
            $firstname = $request->firstname ?? null;
            $lastname = $request->lastname ?? null;

            try {
                // Fetch Apple's public keys
                $appleKeysUrl = 'https://appleid.apple.com/auth/keys';
                $response = Http::get($appleKeysUrl);

                if ($response->failed() || $response->header('Content-Type') !== 'application/json;charset=UTF-8') {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Failed to retrieve Apple public keys',
                    ], 400);
                }

                // Parse the JSON response into an array
                $appleKeys = $response->json();

                // Decode JWT Header to get 'kid'
                $headerEncoded = explode('.', $identityToken)[0];
                $headerJson = JWT::urlsafeB64Decode($headerEncoded);
                $header = json_decode($headerJson, true);

                if (!$header || !isset($header['kid'])) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Malformed JWT header',
                    ], 400);
                }

                $kid = $header['kid'];

                // Parse the keys and find the one with the matching 'kid'
                $publicKeys = JWK::parseKeySet($appleKeys);
                $publicKey = $publicKeys[$kid] ?? null;

                if (!$publicKey) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Public key not found or invalid',
                    ], 400);
                }

                // Decode the JWT using the found public key
                $payload = JWT::decode($identityToken, $publicKey);

                $appleId = $payload->sub;
                $email = $payload->email ?? null;

                $check_if_email_exist = User::whereEmail($email)->first();
                if ($check_if_email_exist) {
                    $token = $check_if_email_exist->createToken('SocialAuth_apple')->accessToken;

                    // Send a welcome email containing their login details
                    return response()->json([
                        'token' => $token,
                        'message' => "existing"
                    ], 200);
                } else {
                    if(!$request->firstname){
                        $get_firstname = Str::of($payload->email)->before('@gmail.com');
                    }else{
                        $get_firstname = $request->firstname;
                    }

                    if(!$request->lastname){
                        $get_lastname = Str::of($payload->email)->before('@gmail.com');
                    }else{
                        $get_lastname = $request->lastname;
                    }
                    $characters = 'PQRTUVWXYZdefpqrs0123456789!@#$%^&*()';
                    $password = Str::random(5, $characters);
                    $get_password = time() . $password;

                    $user = User::create([
                        'f_name' => Helpers::sanitize_input($get_firstname),
                        'l_name' => Helpers::sanitize_input($get_lastname),
                        'email' => Helpers::sanitize_input($payload->email),
                        'phone' => Helpers::sanitize_input(Str::random(11, '0123456789')),
                        'password' => bcrypt(Helpers::sanitize_input($get_password)),
                        'temporary_token' => Str::random(40),
                        'refer_code' => Str::random('20'),
                        'refer_by' => null,
                        'language_code' => Helpers::sanitize_input($request->header('X-localization')) ?? 'en',
                        'dob' => null,
                    ]);
                    $token = $user->createToken('SocialAuth_apple')->accessToken;

                    // Send a welcome email containing their login details
                    $details = [
                        'name' => Helpers::sanitize_input($get_firstname),
                        'app_name' => env('APP_NAME'),
                        'default_password' => Helpers::sanitize_input($get_password)
                    ];

                    $subject = 'Welcome to ' . env('APP_NAME');
                    // Mail::to(Helpers::sanitize_input($payload->email))->send(new WelcomeMail($details, $subject));

                    if(!$request->firstname || !$request->lastname){
                        return response()->json([
                            'token' => $token,
                            'message' => "newwithnoname"
                        ], 200);
                    }else{
                        return response()->json([
                            'token' => $token,
                            'message' => "newwithname"
                        ], 200);
                    }
                }

            } catch (\Exception $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error processing request: ' . $e->getMessage(),
                ], 400);
            }
        }else {
            return response()->json(['error' => 'Accepted values are google and apple'], 400);
        }
    }
}
