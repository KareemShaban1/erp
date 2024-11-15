<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Google\Client as GoogleClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Storage;

class FcmController extends Controller
{
    protected $googleClient;
    protected $projectId;
    protected $guzzleClient;

    public function __construct()
    {
        $this->projectId = config('services.fcm.project_id');
        $this->googleClient = new GoogleClient();

        // Paths to the credentials and CA certificate
        $credentialsFilePath = storage_path('app/json/private-key.json');
        $cert = storage_path('app/cacert.pem'); // Path to your cacert.pem file

        // Configure Google Client with credentials and CA certificate
        $this->googleClient->setAuthConfig($credentialsFilePath);
        $this->googleClient->setHttpClient(new \GuzzleHttp\Client([
            'verify' => false, // Use the cacert.pem file for SSL verification
        ]));
        $this->googleClient->addScope('https://www.googleapis.com/auth/firebase.messaging');
        
        // Initialize Guzzle Client
        $this->guzzleClient = new GuzzleClient([
            'verify' => false, // Use the cacert.pem file for SSL verification
        ]);
    }

    public function updateDeviceToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $client_id = Auth::user()->id;
        $client = Client::find($client_id);
        $client->update(['fcm_token' => $request->fcm_token]);

        return response()->json(['message' => 'Device token updated successfully']);
    }

    protected function getAccessToken()
    {
        return Cache::remember('fcm_access_token', 3000, function () {
            $this->googleClient->refreshTokenWithAssertion();
            $token = $this->googleClient->getAccessToken();
            return $token['access_token'] ?? null;
        });
    }

    // public function sendFcmNotification(Request $request)
    // {
    //     $request->validate([
    //         'title' => 'required|string',
    //         'body' => 'required|string',
    //     ]);

    //     // Get client associated with authenticated user
    //     $client_id = Auth::user()->id;
    //     $client = Client::find($client_id);
    //     if (!$client || !$client->fcm_token) {
    //         return response()->json(['message' => 'Client does not have a device token'], 400);
    //     }

    //     $accessToken = $this->getAccessToken();
    //     if (!$accessToken) {
    //         return response()->json(['message' => 'Failed to obtain access token'], 500);
    //     }

    //     $headers = [
    //         "Authorization" => "Bearer $accessToken",
    //         'Content-Type' => 'application/json',
    //     ];

    //     $data = [
    //         "message" => [
    //             "token" => $client->fcm_token,
    //             "notification" => [
    //                 "title" => $request->title,
    //                 "body" => $request->body,
    //             ],
    //         ],
    //     ];

    //     // Using Guzzle to make the POST request
    //     try {
    //         $response = $this->guzzleClient->post("https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send", [
    //             'headers' => $headers,
    //             'json' => $data,
    //         ]);

    //         $responseBody = json_decode($response->getBody()->getContents(), true);

    //         return response()->json([
    //             'message' => 'Notification has been sent',
    //             'response' => $responseBody,
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => 'Error sending notification',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function sendFcmNotification(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'body' => 'required|string',
        ]);

        $client_id = Auth::user()->id;
        $client_data = Client::find($client_id);
        if (!$client_data || !$client_data->fcm_token) {
            return response()->json(['message' => 'Client does not have a device token'], 400);
        }

        // if (!$fcm) {
        //     return response()->json(['message' => 'User does not have a device token'], 400);
        // }

        $title = $request->title;
        $description = $request->body;
        $projectId = config('services.fcm.project_id'); # INSERT COPIED PROJECT ID

        $credentialsFilePath = storage_path('app/json/private-key.json');
        $client = new GoogleClient();
        $client->setAuthConfig($credentialsFilePath);
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
        $client->refreshTokenWithAssertion();
        $token = $client->getAccessToken();

        $access_token = $token['access_token'];

        $headers = [
            "Authorization: Bearer $access_token",
            'Content-Type: application/json'
        ];

        $data = [
            "message" => [
                "token" => $client_data->fcm_token,
                "notification" => [
                    "title" => $title,
                    "body" => $description,
                ],
            ]
        ];
        $payload = json_encode($data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_VERBOSE, true); // Enable verbose output for debugging
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            return response()->json([
                'message' => 'Curl Error: ' . $err
            ], 500);
        } else {
            return response()->json([
                'message' => 'Notification has been sent',
                'response' => json_decode($response, true)
            ]);
        }
    }

    
}
