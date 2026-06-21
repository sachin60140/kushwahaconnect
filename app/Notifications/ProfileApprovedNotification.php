<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProfileApprovedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->success()
            ->subject('Your KushwahaConnect profile is approved')
            ->greeting('Namaste '.$notifiable->name.',')
            ->line('Congratulations! Your community profile has been approved by the KushwahaConnect admin team.')
            ->line('You can now connect with community members and keep your public profile updated.')
            ->action('Open My Community Profile', route('user.member-profile.edit'))
            ->salutation('Regards, KushwahaConnect Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Community Profile Approved',
            'message' => 'Congratulations! Your KushwahaConnect community profile has been approved by the administrators.',
            'type' => 'success',
            'action_url' => '/my-community-profile'
        ];
    }
}
