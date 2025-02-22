<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PushNotification extends Notification
{
    use Queueable;

    protected $title;
    protected $body;
    protected $data;

    /**
     * Create a new notification instance.
     *
     * @param string $title
     * @param string $body
     * @param array $data
     */
    public function __construct(string $title, string $body, array $data = [])
    {
        $this->title = $title;
        $this->body = $body;
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'data' => $this->data,
            'android' => [
                'notification' => [
                    'sound' => 'custom_sound',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK', // Required for tapping to trigger response
                    'channel_id' => 'high_importance_channel'
                ],
            ],
            'apns' => [
                'payload' => [
                    'aps' => [
                        'sound' => 'custom_sound.caf',
                        'content-available' => 1,
                    ],
                ],
            ],
        ];
    }
}
