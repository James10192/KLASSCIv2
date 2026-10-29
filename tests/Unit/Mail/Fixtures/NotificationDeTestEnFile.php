<?php

namespace Tests\Unit\Mail\Fixtures;

class NotificationDeTestEnFile extends \Illuminate\Notifications\Notification implements \Illuminate\Contracts\Queue\ShouldQueue
{
    use \Illuminate\Bus\Queueable;

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): \Illuminate\Notifications\Messages\MailMessage
    {
        return (new \Illuminate\Notifications\Messages\MailMessage())->subject('Avis')->line('Bonjour');
    }
}
