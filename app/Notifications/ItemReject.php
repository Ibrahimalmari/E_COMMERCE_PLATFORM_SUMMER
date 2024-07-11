<?php


namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ItemReject extends Notification
{
    use Queueable;

    protected $rejectReason;
    protected $rejectedData;

    public function __construct($rejectReason, $rejectedData)
    {
        $this->rejectReason = $rejectReason;
        $this->rejectedData = $rejectedData;
    }

    public function via($notifiable)
    {
        return [ 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->line('لقد تم رفض العنصر الخاص بك.')
                    ->line('السبب: ' . $this->rejectReason)
                    ->line('البيانات المرفوضة: ' . json_encode($this->rejectedData))
                    ->line('شكراً لاستخدامك تطبيقنا!');
    }

    public function toArray($notifiable)
    {
        return [
            'rejectReason' => $this->rejectReason,
            'rejectedData' => $this->rejectedData,
        ];
    }
}
