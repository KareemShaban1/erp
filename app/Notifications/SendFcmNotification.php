<?php

// app/Notifications/SendFcmNotification.php
namespace App\Notifications;

use App\Services\FcmService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SendFcmNotification extends Notification
{
    use Queueable;

    private $title;
    private $body;
    private $data;

    public function __construct($title, $body, $data = [])
    {
        $this->title = $title;
        $this->body = $body;
        $this->data = $data;
    }

    public function via($notifiable)
    {
        return ['fcm'];
    }

    public function toFcm($notifiable)
    {
        $fcmService = new FcmService();
        return $fcmService->sendNotification($notifiable->fcm_token, $this->title, $this->body, $this->data);
    }
}
