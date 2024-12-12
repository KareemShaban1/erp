<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class OrderRefundCreatedNotification extends Notification
{
    use Queueable;

    protected $refund_order;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($refund_order)
    {
        $this->refund_order = $refund_order;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        $channels = ['database'];
        if (isPusherEnabled()) {
            $channels[] = 'broadcast';
        }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
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
            "order_number" => $this->refund_order->number,
            "parent_order_id" => $this->refund_order->parent_order_id,
            "client" => $this->refund_order->client->contact->name,
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return BroadcastMessage
     */
    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'title' => __('lang_1.refund_order_added_successfully'),
            // 'body'=>'test order notifications',
            'body' => strip_tags( __('lang_1.new_refund_order_notification', 
            ['client' => $this->refund_order->client->contact->name,
                'order_number' => $this->refund_order->number,
             'parent_order_id' => $this->refund_order->parent_order_id]) ),
            'link' => action('ApplicationDashboard\RefundOrderController@index')
        ]);
    }
}
