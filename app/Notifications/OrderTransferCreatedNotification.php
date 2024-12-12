<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class OrderTransferCreatedNotification extends Notification
{
    use Queueable;

    protected $transfer_order;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($transfer_order)
    {
        $this->transfer_order = $transfer_order;
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
            "order_number" => $this->transfer_order->number,
            "parent_order_id" => $this->transfer_order->parent_order_id,
            "client" => $this->transfer_order->client->contact->name,
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
            'title' => __('lang_1.transfer_order_added_successfully'),
            // 'body'=>'test order notifications',
            'body' => strip_tags( __('lang_1.new_transfer_order_notification', 
            ['client' => $this->transfer_order->client->contact->name,
            'parent_order_id' => $this->transfer_order->parent_order_id,
            'order_number' => $this->transfer_order->number]) ),
            'link' => action('ApplicationDashboard\TransferOrderController@index')
        ]);
    }
}
