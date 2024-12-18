<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class MakeRefundNotification extends Notification
{
    use Queueable;

    protected $order_refund;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($order_refund)
    {
        $this->order_refund = $order_refund;
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
            "order_number" => $this->order_refund->order->number,
            'refund_status'=>$this->order_refund->status,
            "parent_order_id" => $this->order_refund->order->parent_order_id,
            "client" => $this->order_refund->order->client->contact->name,
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
            'title' => __('lang_1.order_refund_added_successfully'),
            // 'body'=>'test order notifications',
            'body' => strip_tags(__(
                'lang_1.new_order_refund_notification',
                [
                    'client' => $this->order_refund->order->client->contact->name,
                    'refund_status'=>$this->order_refund->status,
                    'order_number' => $this->order_refund->order->number,
                    'parent_order_id' => $this->order_refund->order->parent_order_id
                ]
            )),
            'link' => action('ApplicationDashboard\OrderRefundController@index')
        ]);
    }
}
