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

    public function calculateRates($deliveryManId)
    {
        // Fetch the last 50 orders for the delivery man
        $orders = $this->where('delivery_men_id', $deliveryManId)
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get();

        $acceptanceRate = 100;
        $rejectionRate = 0;

        foreach ($orders as $order) {
            if ($order->status === 'مرفوض' || $order->status === 'ملغى') {
                $rejectionRate = min(100, $rejectionRate + 2);
                $acceptanceRate = max(0, $acceptanceRate - 2);
            } elseif ($order->status === 'مقبول' && $rejectionRate > 0) {
                $rejectionRate = max(0, $rejectionRate - 2);
            }
        }

        return [
            'acceptance_rate' => $acceptanceRate,
            'rejection_rate' => $rejectionRate,
        ];
    }



   
}

