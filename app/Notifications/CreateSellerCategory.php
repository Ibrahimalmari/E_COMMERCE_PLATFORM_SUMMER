<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CreateSellerCategory extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
     private $SellerName;
     private $Name;
     private $Slug;
     private $Description;
     private $StoreName;
     private $tableName;

    public function __construct($SellerName , $Name , $Slug , $Description , $StoreName ,$tableName)
    {
        $this->SellerName = $SellerName;
        $this->Name = $Name;
        $this->Slug = $Slug;
        $this->Description = $Description;
        $this->StoreName = $StoreName;
        $this->tableName =$tableName;
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
            'Creator name' => $this->SellerName,
            'Category name' => $this->Name,
            'Category slug' => $this->Slug,
            'Category description' => $this->Description,
            'Store name ' => $this->StoreName,
            'table Name' => $this->tableName

    
        ];
    }
}
