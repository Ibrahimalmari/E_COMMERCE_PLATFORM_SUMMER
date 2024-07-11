<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ItemAccepted extends Notification
{
    use Queueable;

    protected $itemType;
    protected $itemName;

    public function __construct($itemType, $itemName)
    {
        $this->itemType = $itemType;
        $this->itemName = $itemName;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'message' => "Your {$this->itemType} '{$this->itemName}' has been accepted.",
        ];
    }
}

