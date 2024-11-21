<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Notification\NotificationCollection;
use App\Http\Resources\Notification\NotificationResource;
use App\Models\Delivery;
use App\Models\Notification;
use App\Models\User;
use App\Notifications\SendFcmNotification;
use Illuminate\Support\Facades\Auth;

class DeliveryNotificationController extends Controller
{
   public function getDeliveryNotifications()
   {
      $notifications = Notification::
         where('notifiable_type', 'App\Models\Delivery')->
         where('notifiable_id', Auth::id())->get();
      // return $notifications;
      $response = new NotificationCollection($notifications);
      return $this->returnJSON($response, __('message.Notifications has been retrieved  successfully'));

   }

   public function getUnreadNotificationsCount()
   {
      $notifications =  Notification::where('notifiable_type', 'App\Models\Delivery')
         ->where('notifiable_id', Auth::id())
         ->whereNull('read_at') // Only unread notifications
         ->get();

         $response = new NotificationCollection($notifications);

         return $this->returnJSON($response, __('message.Un Read Notifications has been retrieved  successfully'));

   }

   public function markNotificationAsRead($id)
   {
      $notification =  Notification::
         where('id', $id)
         ->where('notifiable_type', 'App\Models\Delivery')
         ->where('notifiable_id', Auth::id())
         ->whereNull('read_at')->first(); // Only unread notifications
         if ($notification) {
         $notification->read_at = now(); // Mark as read
         $notification->save();
         }
         $response = new NotificationResource($notification);

      return $this->returnJSON($response, __('message.Notification marked as read successfully'));

   }
   public function markAllNotificationsAsRead()
{
    // Update unread notifications and fetch them
    $notifications = Notification::where('notifiable_type', 'App\Models\Delivery')
        ->where('notifiable_id', Auth::id())
        ->whereNull('read_at') // Only unread notifications
        ->get(); // Fetch the records

    // Update their `read_at` field
    $notifications->each->update(['read_at' => now()]);

    // Use the NotificationCollection to transform the notifications
    $response = new NotificationCollection($notifications);

    return $this->returnJSON($response, __('message.All Notifications marked as read successfully'));
}



}