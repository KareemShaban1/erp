<?php 
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use App\Notifications\SendFcmNotification;
use Illuminate\Support\Facades\Auth;

Class NotificationController extends Controller {
          public function sendNotification()
{
    $client = Client::find(Auth::user()->id); // Replace with the intended user's ID

    $title = 'New Message';
    $body = 'You have a new message!';
    $data = ['key' => 'value']; // Optional additional data

    $client->notify(new SendFcmNotification($title, $body, $data));
}

}