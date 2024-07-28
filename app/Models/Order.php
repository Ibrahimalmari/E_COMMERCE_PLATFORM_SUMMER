<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;


    const STATUS_RECEIVED = 'تم استلام الطلب';
    const STATUS_PREPARING = 'الطلب قيد التجهيز';
    const STATUS_READY = 'الطلب جاهز للتوصيل';
    const STATUS_IN_DELIVERY = 'الطلب في مرحلة التوصيل';
    const STATUS_DELIVERED = 'تم تسليم الطلب';




    protected $fillable = [
        'additional_info',
        'delivery_notes',
        'order_numbers',
        'invoice_amount',
        'order_status',
        'pay_way',
        'delivery_fee',
        'discount',     
        'tax',
        'tip',
        'cart_id',
        'customer_id',
        'store_id',
        'address_id', 

    ];


    public static function getStatuses()
    {
        return [
            self::STATUS_RECEIVED,
            self::STATUS_PREPARING,
            self::STATUS_READY,
            self::STATUS_IN_DELIVERY,
            self::STATUS_DELIVERED,
        ];
    }


    public function customer(){
        return $this->belongsTo(Customer::class);
     }
     public function store(){
        return $this->belongsTo(Store::class);
     }

     public function cart(){
        return $this->belongsTo(Cart::class);
     }

     public function orderItems()
     {
         return $this->hasMany(OrderItem::class);
     }

     public function address()
    {
        return $this->belongsTo(Address::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            $order->order_numbers = self::generateOrderNumber();
        });
    }

    private static function generateOrderNumber()
    {
        $prefix = 'ORD-';
        $randomPart2 = rand(1000, 9999); // أربعة أرقام عشوائية

        return $prefix . $randomPart2;
    }


}
