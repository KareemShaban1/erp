<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Google\Client as GoogleClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class FcmController extends Controller
{
    protected $googleClient;
    protected $projectId;

    public function __construct()
    {
        $this->projectId = config('services.fcm.project_id');
        $this->googleClient = new GoogleClient();
        $credentialsFilePath = storage_path('app/json/private-key.json');
        $this->googleClient->setAuthConfig($credentialsFilePath);
        $this->googleClient->addScope('https://www.googleapis.com/auth/firebase.messaging');
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
            return $token['Personal Access Token'];
        });
    }

    public function sendFcmNotification(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'body' => 'required|string',
        ]);

        // Get client associated with authenticated user
        $client_id = Auth::user()->id;
        $client = Client::find($client_id);
        if (!$client || !$client->fcm_token) {
            return response()->json(['message' => 'Client does not have a device token'], 400);
        }

        $accessToken = $this->getAccessToken();

        $headers = [
            "Authorization: Bearer $accessToken",
            'Content-Type: application/json',
        ];

        $data = [
            "message" => [
                "token" => $client->fcm_token,
                "notification" => [
                    "title" => $request->title,
                    "body" => $request->body,
                ],
            ],
        ];

        $response = Http::withHeaders($headers)
            ->post("https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send", $data);

        if ($response->failed()) {
            return response()->json([
                'message' => 'Error sending notification',
                'error' => $response->json(),
            ], 500);
        }

        return response()->json([
            'message' => 'Notification has been sent',
            'response' => $response->json(),
        ]);
    }
}
