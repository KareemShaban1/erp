<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Client;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    //

    public function userRegister(Request $request)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create User
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone'=>$request->phone,
        ]);

        // Respond with User Data and Token
        $token = $user->createToken('Personal Access Token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }


    public function userLogin(Request $request)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Attempt Login
        if (Auth::guard('web')->attempt($request->only('email', 'password'))) {
            $user = Auth::guard('web')->user();
            $token = $user->createToken('Personal Access Token')->plainTextToken;

            return response()->json([
                'user' => $user,
                'token' => $token,
            ], 200);
        }

        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    public function clientRegister(Request $request)
    {
         // Validation
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'email_address' => [
            'required',
            'email',
            'max:255',
            'unique:clients'
        ],
        'password' => [
            'required',
            'string',
            'min:8',
        ],
        'mobile' => 'required|string|max:20',
        'location' => 'nullable|string|max:255',
        // 'business_id' => 'required|numeric|exists:business,id',
        'business_location_id' => 'required|numeric|exists:business_locations,id'
    ]);

    if ($validator->fails()) {
        // Get the first error message
        $firstError = $validator->errors()->first();
        return response()->json(['message' => $firstError], 422);
    }


        $business = Business::where('id',$request->business_location_id)->first();
    
        // business need to changed
        // Create contact information
        $contactInfo = Contact::create([
            'name' => $request->name,
            'mobile' => $request->mobile,
            'created_by'=>1,
            'business_id' => $business->id ?? 1,
            'type' => 'client',
            'contact_status'=>'not_active'
        ]);
    
        // Create Client
        $client = Client::create([
            'contact_id'=> $contactInfo->id,
            'email_address' => $request->email_address,
            'password' => Hash::make($request->password),
            'location' => $request->location,
            'business_location_id' => $request->business_location_id,
            'client_type' => 'application',
        ]);
    
        // Generate Sanctum Token
        $token = $client->createToken('Personal Access Token')->plainTextToken;
    
        // Respond with Client Data and Token
        return response()->json([
            'client' => $client,
            'token' => $token,
        ], 201);
    }
    



    public function clientLogin(Request $request)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            'email_address' => 'required|string|email',
            'password' => 'required|string',
        ]);
    
        if ($validator->fails()) {
            // Get the first error message
            $firstError = $validator->errors()->first();
            return response()->json(['message' => $firstError], 422);
        }
    
        // Find the client by email
        $client = Client::where('email_address', $request->email_address)->first();
    
        // Check if client exists and if the password is correct
        if (!$client || !Hash::check($request->password, $client->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
    
        // Generate Sanctum Token
        $token = $client->createToken('Personal Access Token')->plainTextToken;
    
        // Respond with Client Data and Token
        return response()->json([
            'client' => $client,
            'token' => $token,
        ], 200);
    }
    

}
