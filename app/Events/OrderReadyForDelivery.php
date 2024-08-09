<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderReadyForDelivery implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;
    public $customer;
    public $address;
    public $cartItems;
    public $store; // تأكد من إضافة هذا المتغير
    public $connectedWorkers; // إضافة المتغير للعاملين المتصلين

    /**
     * Create a new event instance.
     *
     * @param  $order
     * @param  $customer
     * @param  $address
     * @param  $cartItems
     * @param  $store
     * @param  $connectedWorkers
     * @return void
     */
    public function __construct($order, $customer, $address, $cartItems, $store, $connectedWorkers)
    {
        $this->order = $order;
        $this->customer = $customer;
        $this->address = $address;
        $this->cartItems = $cartItems;
        $this->store = $store; // تعيين المتجر
        $this->connectedWorkers = $connectedWorkers; // تعيين العاملين المتصلين
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
            'connectedWorkers' => $this->connectedWorkers->map(function($worker) {
                return [
                    'id' => $worker->id,
                ];
            })->toArray(),
            'customer' => [
                'name' => $this->customer->name,
                'phone' => $this->customer->phone, // افتراض وجود حقل الهاتف
            ],
            'address' => $this->address,
            'cartItems' => $this->cartItems,
        ];

        // ضغط البيانات
        $compressedData = gzcompress(json_encode($data));

        // تحويل البيانات المضغوطة إلى قاعدة 64 لتسهيل النقل
        return [
            'data' => base64_encode($compressedData),
        ];
    }
}
