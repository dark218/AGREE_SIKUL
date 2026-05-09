<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Kutia\Larafirebase\Messages\FirebaseMessage;

class SendPushNotification extends Notification
{
    use Queueable;

    protected $title;
    protected $message;
    protected $fcmTokens;
    protected $data;

    /**
     * Create a new notification instance.
     */
    public function __construct($title, $message, $fcmTokens, $data = null)
    {
        $this->title = $title;
        $this->message = $message;
        $this->fcmTokens = $fcmTokens;
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['firebase'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toFirebase($notifiable)
    {
//        $send = (new FirebaseMessage)
//            ->withTitle($this->title)
//            ->withBody($this->message)
////            ->withAdditionalData($this->data)
//            ->withPriority('high')
//            ->withSound('default')
//            ->asNotification($this->fcmTokens);
        return (new FirebaseMessage)
            ->withTitle($this->title)
            ->withBody($this->message)
            ->withAdditionalData($this->data)
            ->withPriority('high')
//            ->withSound('default')
            ->asNotification($this->fcmTokens);
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
