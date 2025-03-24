<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Notification\NotificationCollection;
use App\Http\Resources\Notification\NotificationResource;
use App\Models\Client;
use App\Models\Notification;
use App\Models\User;
use App\Notifications\SendFcmNotification;
use Illuminate\Support\Facades\Auth;

class ClientNotificationController extends Controller
{

   // get client latest notifications
   public function getClientNotifications()
   {
      $notifications = Notification::
         where('notifiable_type', 'App\Models\Client')->
         where('notifiable_id', Auth::id())
         ->latest()
         ->get();
      $response = new NotificationCollection($notifications);
      return $this->returnJSON($response, __('message.Notifications has been retrieved  successfully'));

   }

   // get client unread latest notifications
   public function getUnreadNotificationsCount()
   {
      $notifications = Notification::where('notifiable_type', 'App\Models\Client')
         ->where('notifiable_id', Auth::id())
         ->whereNull('read_at') // Only unread notifications
         ->latest()
         ->get();

      $response = new NotificationCollection($notifications);

      return $this->returnJSON($response, __('message.Un Read Notifications has been retrieved  successfully'));

   }

   // mark client notifications as read
   public function markNotificationAsRead($id)
   {
      $notification = Notification::
         where('id', $id)
         ->where('notifiable_type', 'App\Models\Client')
         ->where('notifiable_id', Auth::id())
         ->whereNull('read_at')->first(); // Only unread notifications
      if ($notification) {
         $notification->read_at = now(); // Mark as read
         $notification->save();
      }
      $response = new NotificationResource($notification);

      return $this->returnJSON($response, __('message.Notification marked as read successfully'));

   }

   // mark all client notifications as read
   public function markAllNotificationsAsRead()
   {
      $notifications = Notification::where('notifiable_type', 'App\Models\Client')
         ->where('notifiable_id', Auth::id())
         ->whereNull('read_at') // Only unread notifications
         ->get();

      $notifications->each->update(['read_at' => now()]);

      $response = new NotificationCollection($notifications);

      return $this->returnJSON($response, __('message.All Notifications marked as read successfully'));

   }


}