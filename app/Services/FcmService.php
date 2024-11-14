<?php 
// app/Services/FcmService.php
namespace App\Services;

use Illuminate\Support\Facades\Http;

class FcmService
{
    protected $fcmUrl;
    protected $fcmKey;

    public function __construct()
    {
        $this->fcmUrl = config('services.fcm.url');
        $this->fcmKey = config('services.fcm.key');
    }

    public function sendNotification($deviceToken, $title, $body, $data = [])
    {
        $payload = [
            'to' => $deviceToken,
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default', // Optional
            ],
            'data' => $data, // Optional additional data
        ];

        $response = Http::withHeaders([
            'Authorization' => 'key=' . $this->fcmKey,
            'Content-Type' => 'application/json',
        ])->post($this->fcmUrl, $payload);

        return $response->json();
    }
}
