<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Delivery;
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
            'phone' => $request->phone,
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
         // Custom validation messages
         $messages = [
            'name.required' => 'الاسم مطلوب.',
            'email_address.required' => 'عنوان البريد الإلكتروني مطلوب.',
            'email_address.email' => 'يرجى إدخال عنوان بريد إلكتروني صالح.',
            'email_address.unique' => 'عنوان البريد الإلكتروني هذا مسجل بالفعل.',
            'password.required' => 'كلمة المرور مطلوبة.',
            'password.min' => 'يجب أن تتكون كلمة المرور من 8 أحرف على الأقل.',
            'mobile.required' => 'رقم الهاتف مطلوب.',
            'mobile.max' => 'يجب ألا يتجاوز رقم الهاتف 20 حرفًا.',
            'fcm_token.required' => 'رمز FCM مطلوب.',
            'business_location_id.required' => 'معرف موقع العمل مطلوب.',
            'business_location_id.exists' => 'معرف موقع العمل المحدد غير صالح.',
        ];
        

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
            'fcm_token' => 'required|string',
            'business_location_id' => 'required|numeric|exists:business_locations,id'
        ], $messages);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return response()->json(['message' => $firstError], 422);
        }


        $business_location = BusinessLocation::where('id', $request->business_location_id)->first();

        $business = Business::where('id', $business_location->business_id)->first();


        // business need to changed
        // Create contact information
        $contactInfo = Contact::create([
            'name' => $request->name,
            'mobile' => $request->mobile,
            'created_by' => 1,
            'business_id' => $business->id ?? 1,
            'type' => 'client',
            'contact_status' => 'inactive'
        ]);

        // Create Client
        $client = Client::create([
            'contact_id' => $contactInfo->id,
            'email_address' => $request->email_address,
            'password' => Hash::make($request->password),
            'location' => $request->location,
            'business_location_id' => $request->business_location_id,
            'fcm_token' => $request->fcm_token,
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
        // Custom validation messages
    $messages = [
        'email_address.required' => 'عنوان البريد الإلكتروني مطلوب.',
        'email_address.email' => 'يرجى إدخال عنوان بريد إلكتروني صالح.',
        'password.required' => 'كلمة المرور مطلوبة.',
        'fcm_token.required' => 'رمز FCM مطلوب.',
    ];

    // Validation
    $validator = Validator::make($request->all(), [
        'email_address' => 'required|string|email',
        'password' => 'required|string',
        'fcm_token' => 'required|string',
    ], $messages);

        if ($validator->fails()) {
            // Get the first error message
            $firstError = $validator->errors()->first();
            return response()->json(['message' => $firstError], 422);
        }

        // Find the client by email
        $client = Client::where('email_address', $request->email_address)->first();

        $client_status = $client->contact->contact_status;

        // Check if client exists
        if (!$client) {
            return response()->json(['message' => 'Client not found'], 404);
        }

        // Check if the delivery account is deleted
        if ($client->account_status == 'deleted') {
            return response()->json(['message' => 'client account is deleted'], 403); // Forbidden for inactive deliverys
        }


        // Check if the client is active
        if ($client_status == 'inactive') {
            return response()->json(['message' => 'Client is not active'], 403); // Forbidden for inactive clients
        }

        // Check if the password is correct
        if (!Hash::check($request->password, $client->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Generate Sanctum Token
        $token = $client->createToken('Personal Access Token')->plainTextToken;

        $client->fcm_token = $request->fcm_token;
        $client->save();

        // Respond with Client Data and Token
        return response()->json([
            'client' => $client,
            'token' => $token,
        ], 200);
    }


    public function deliveryLogin(Request $request)
    {
        $messages = [
            'email_address.required' => 'عنوان البريد الإلكتروني مطلوب.',
            'email_address.email' => 'يرجى إدخال عنوان بريد إلكتروني صالح.',
            'password.required' => 'كلمة المرور مطلوبة.',
            'fcm_token.required' => 'رمز FCM مطلوب.',
        ];
        // Validation
        $validator = Validator::make($request->all(), [
            'email_address' => 'required|string|email',
            'password' => 'required|string',
            'fcm_token' => 'required|string',
        ],$messages);

        if ($validator->fails()) {
            // Get the first error message
            $firstError = $validator->errors()->first();
            return response()->json(['message' => $firstError], 422);
        }

        // Find the delivery by email
        $delivery = Delivery::where('email_address', $request->email_address)->first();

        $delivery_status = $delivery->contact->contact_status;

        // Check if delivery exists
        if (!$delivery) {
            return response()->json(['message' => 'delivery not found'], 404);
        }

        // Check if the delivery account is deleted
        if ($delivery->account_status == 'deleted') {
            return response()->json(['message' => 'delivery account is deleted'], 403); // Forbidden for inactive deliverys
        }

        // Check if the delivery is active
        if ($delivery_status == 'inactive') {
            return response()->json(['message' => 'delivery is not active'], 403); // Forbidden for inactive deliverys
        }

        // Check if the password is correct
        if (!Hash::check($request->password, $delivery->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Generate Sanctum Token
        $token = $delivery->createToken('Personal Access Token')->plainTextToken;

        // Respond with delivery Data and Token
        return response()->json([
            'delivery' => $delivery,
            'token' => $token,
        ], 200);
    }

    public function deleteClientAccount()
    {
        $client = Auth::user();
        $client->account_status = 'deleted';
        $client->save();
        return response()->json(['message' => 'Account deleted successfully'], 200);
    }

    public function deleteDeliveryAccount()
    {
        $delivery = Auth::user();
        $delivery->account_status = 'deleted';
        $delivery->save();
        return response()->json(['message' => 'Account deleted successfully'], 200);
    }


    public function getClientAccount()
    {
        $client_id = Auth::id();
        $client = Client::with('contact')->find($client_id);
        if (!$client) {
            return response()->json(['message' => 'Account not found'], 404);
        }

        return $this->returnJSON($client, __('message.Client Account Information'));

    }

    public function getDeliveryAccount()
    {
        $delivery_id = Auth::id();
        $delivery = Delivery::with('contact')->find($delivery_id);
        if (!$delivery) {
            return response()->json(['message' => 'Account not found'], 404);
        }

        return $this->returnJSON($delivery, __('message.Delivery Account Information'));

    }


    public function updateClientPassword(Request $request)
    {
        // Validate the request
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8',
        ]);
    
        $client = Auth::user();
    
        // Verify the current password
        if (!Hash::check($request->current_password, $client->password)) {
            return response()->json([
                'success' => false,
                'message' => __('message.Current password is incorrect'),
            ], 400);
        }
    
        // Update the password with hashing
        $client->password = Hash::make($request->new_password);
        $client->save();
    
        return response()->json([
            'success' => true,
            'message' => __('message.Password updated successfully'),
        ]);
    }
    

    public function updateDeliveryPassword(Request $request)
    {
        // Validate the request
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8',
        ]);
    
        $delivery = Auth::user();
    
        // Verify the current password
        if (!Hash::check($request->current_password, $delivery->password)) {
            return response()->json([
                'success' => false,
                'message' => __('message.Current password is incorrect'),
            ], 400);
        }
    
        // Update the password with hashing
        $delivery->password = Hash::make($request->new_password);
        $delivery->save();
    
        return response()->json([
            'success' => true,
            'message' => __('message.Password updated successfully'),
        ]);
    }


    public function updatePassword(Request $request)
{
    // Validate the request
    $request->validate([
        'type' => 'required|in:client,delivery',
        'current_password' => 'required',
        'new_password' => 'required|min:8',
    ]);

    // Get the authenticated user
    $user = Auth::user();

    // Check the user type and apply specific logic
    if ($request->type === 'client' && $user->role !== 'client') {
        return response()->json([
            'success' => false,
            'message' => __('message.Unauthorized user for client type'),
        ], 403);
    }

    if ($request->type === 'delivery' && $user->role !== 'delivery') {
        return response()->json([
            'success' => false,
            'message' => __('message.Unauthorized user for delivery type'),
        ], 403);
    }

    // Verify the current password
    if (!Hash::check($request->current_password, $user->password)) {
        return response()->json([
            'success' => false,
            'message' => __('message.Current password is incorrect'),
        ], 400);
    }

    // Update the password with hashing
    $user->password = Hash::make($request->new_password);
    $user->save();

    return response()->json([
        'success' => true,
        'message' => __('message.Password updated successfully'),
    ]);
}

}
