<?php

namespace App\Services;

use App\Models\Delivery;
use App\Notifications\PushNotification;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Exception\Messaging\MessagingException;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Exception\MessagingException as ExceptionMessagingException;

class FirebaseDeliveryService
{
    protected $messaging;

    public function __construct()
    {
        $factory = (new Factory)->withServiceAccount(storage_path('app/json/private-key.json'));
        $this->messaging = $factory->createMessaging();
    }

    /**
     * Send a push notification and store it in the database.
     *
     * @param int $deliveryId
     * @param string $token
     * @param string $title
     * @param string $body
     * @param array $data
     * @return void
     */
    public function sendAndStoreNotification(
        int $deliveryId,
        string $token,
        string $title,
        string $body,
        array $data = []
    ): void {
        // Prepare notification payload for Firebase
        $notification = [
            'title' => $title,
            'body' => $body,
        ];

        $cloudMessage = CloudMessage::new()
            ->withNotification($notification)
            ->withData($data)
            ->withChangedTarget('token', $token);

        try {
            // Send notification via Firebase
            $response = $this->messaging->send($cloudMessage);
            
            // Log the success response (can be used to track the response from Firebase)
            Log::info("Notification sent successfully", [
                'delivery_id' => $deliveryId,
                'response' => $response
            ]);

            // Store notification in the database (after successful send)
            $delivery = Delivery::findOrFail($deliveryId); // Ensure the delivery exists
            $delivery->notify(new PushNotification($title, $body, array_merge($data, ['delivery_id' => $deliveryId])));

        } catch (ExceptionMessagingException $e) {
            // Log the error if something goes wrong while sending the notification
            Log::error("Error sending Firebase notification", [
                'delivery_id' => $deliveryId,
                'error' => $e->getMessage()
            ]);

            // You can also throw the exception if you want to propagate it
            // throw $e;
        } catch (\Exception $e) {
            // Catch any other general exception and log it
            Log::error("General error while sending notification", [
                'delivery_id' => $deliveryId,
                'error' => $e->getMessage()
            ]);

            // Optionally, you can throw this exception or handle it accordingly
            // throw $e;
        }
    }
}
