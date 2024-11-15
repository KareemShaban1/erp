<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;

class PushNotificationController extends Controller
{
          protected $firebaseService;
          public function __construct(FirebaseService $firebaseService)
          {
                    $this->firebaseService = $firebaseService;
          }
          public function sendPushNotification(Request $request)
          {
                    $title = $request->title;
                    $body = $request->body;
                    $data = [
                              "type" => $request->type ?? '',
                              'url' => $request->url ?? '',
                    ];
                    $token = 'fIHEYYh8RQGytra8FLxeCR:APA91bE3YCYzNmHIL0FK1sEKpKyGLBDb2WCN4w57h4jwJTJWHf4zwhtY5HM9nAaZcy7fxrzL4aZe1RN-9VUaHdiUwUv3xOJIdSnEhkkNuOZaoksWLnM-WNM';

                    $this->firebaseService->sendAndStoreNotification(1,$token, $title, $body, $data);


                    return response()->json(['message' => 'Push notification sent successfully']);
          }
}