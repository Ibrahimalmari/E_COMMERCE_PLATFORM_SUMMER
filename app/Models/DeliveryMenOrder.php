<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryMenOrder extends Model
{
    use HasFactory;

    // تحديد اسم الجدول إذا كان يختلف عن الاسم الافتراضي
    protected $table = 'delivery_men_orders';

    // تحديد الأعمدة القابلة للتعبئة (mass assignable)
    protected $fillable = [
        'delivery_men_id',
        'order_id',
        'status'
    ];

    // تحديد العلاقة مع نموذج DeliveryMen
    public function deliveryMan()
    {
        return $this->belongsTo(DeliveryMan::class, 'delivery_men_id');
    }

    // تحديد العلاقة مع نموذج Order
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
