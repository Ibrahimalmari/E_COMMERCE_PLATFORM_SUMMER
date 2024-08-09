<?php
// app/Notifications/OrderReadyNotification.php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage; // استخدم هذا للرسائل المخزنة في قاعدة البيانات

class OrderReadyNotification extends Notification
{
    use Queueable;

    protected $order;
    protected $notificationData;

    public function __construct($order, $notificationData)
    {
        $this->order = $order;
        $this->notificationData = $notificationData;
    }

    public function via($notifiable)
    {
        // افتراضياً نستخدم قناة 'database' لتخزين الإشعار في قاعدة البيانات
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'order_id' => $this->notificationData['order_id'],
            'customer_name' => $this->notificationData['customer_name'],
            'address' => $this->notificationData['address'],
            'cart_items' => $this->notificationData['cart_items'],
        ];
    }
}
