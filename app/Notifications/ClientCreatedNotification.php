<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClientCreatedNotification extends Notification
{
    use Queueable;

   
    protected $client;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($client)
    {
        $this->client = $client;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
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
            'client_name' => $this->client->contact->name, 
            'client_email' => $this->client->email_address, 
            'contact_id' => $this->client->contact->id
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
            'title' => __('lang_1.client_added_successfully'),
            // 'body'=>'test client notifications',
            'body' => strip_tags( __('lang_1.new_client_notification', 
            [
                'client_name' => $this->client->contact->name, 
                'client_email' => $this->client->email_address, 
                'contact_id' => $this->client->contact->id]) ),
            'link' => action('ContactController@index', ['type' => 'client'])
        ]);
    }
}
