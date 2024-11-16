<?php 
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use App\Notifications\SendFcmNotification;
use Illuminate\Support\Facades\Auth;

Class NotificationController extends Controller {
         public function getClientNotifications(){
            $client = Client::where('id', auth()->user()->id)->first();
         }

}