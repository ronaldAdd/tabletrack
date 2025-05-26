<?php

namespace App\Notifications;

use App\Models\Restaurant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeRestaurantEmail extends BaseNotification
{

    public $restaurant;

    /**
     * Create a new notification instance.
     */
    public function __construct(Restaurant $restaurant)
    {
        // $this->restaurant = $restaurant;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $build = parent::build($notifiable);

        $siteName = global_setting()->name;
        return $build
            ->subject(__('email.welcomeRestaurant.subject', ['site_name' => $siteName]))
            ->greeting(__('email.welcomeRestaurant.greeting', ['name' => $notifiable->name]))
            ->line(__('email.welcomeRestaurant.line1', ['site_name' => $siteName]))
            ->line(__('email.welcomeRestaurant.line2'))
            ->line(__('email.welcomeRestaurant.line3'))
            ->line(__('email.welcomeRestaurant.line4'))
            ->line(__('email.welcomeRestaurant.line5'))
            ->line(__('email.welcomeRestaurant.line6'))
            ->line(__('email.welcomeRestaurant.line7', ['site_name' => $siteName]));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
