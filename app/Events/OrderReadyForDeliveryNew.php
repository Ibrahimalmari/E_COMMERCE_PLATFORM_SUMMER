<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderReadyForDeliveryNew implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;
    public $customer;
    public $address;
    public $cartItems;
    public $excludedWorkerId;

    public function __construct($order, $customer, $address, $cartItems, $excludedWorkerId = null)
    {
        $this->order = $order;
        $this->customer = $customer;
        $this->address = $address;
        $this->cartItems = $cartItems;
        $this->excludedWorkerId = $excludedWorkerId;
    }

    public function broadcastOn()
    {
       return new Channel('my-channel-delivery');
    }

    public function broadcastAs()
    {
        return 'my-event-delivery';
    }

    public function broadcastWith()
    {
        // تحويل البيانات إلى JSON
        $data = [
            'order' => $this->order,
            'customer' => [
                'name' => $this->customer->name,
                'phone' => $this->customer->phone, // افتراض وجود حقل الهاتف
            ],
            'address' => $this->address,
            'cartItems' => $this->cartItems,
            'excluded_worker_id' => $this->excludedWorkerId,
        ];

        // ضغط البيانات
        $compressedData = gzcompress(json_encode($data));

        // تحويل البيانات المضغوطة إلى قاعدة 64 لتسهيل النقل
        return [
            'data' => base64_encode($compressedData),
        ];
    }
}
