<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProductSuggestionCreatedNotification extends Notification
{
    use Queueable;

    protected $productSuggestion;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($productSuggestion)
    {
        $this->productSuggestion = $productSuggestion;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
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
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */

    public function toArray($notifiable)
    {
        return [
            "product_name" => $this->productSuggestion->name,
            "client" => $this->productSuggestion->client->contact->name,
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
            'title' => __('lang_1.product_suggestion_added_successfully'),
            // 'body'=>'test order notifications',
            'body' => strip_tags( __('lang_1.new_product_suggestion_notification', 
            ['client' => $this->productSuggestion->client->contact->name, 'product_name' => $this->productSuggestion->name]) ),
            'link' => action('ApplicationDashboard\SuggestionProductController@index')
        ]);
    }
}
