<?php

namespace App\Http\Controllers;

use App\Models\DeliveryMan;
use App\Models\DeliveryMenOrder;
use Illuminate\Http\Request;

class DeliveryMenOrdersController extends Controller
{
    public function getOrdersByDeliveryMan($deliveryMenId, Request $request)
    {
        // استرجاع تاريخ البداية والنهاية من الاستعلام
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
    
        // استرجاع الطلبات بناءً على delivery_men_id وفترة التواريخ
        $ordersQuery = DeliveryMenOrder::where('delivery_men_id', $deliveryMenId)
            ->with(['order:id,order_numbers']); // استرداد رقم الطلب من علاقة order
    
        // تطبيق الفلترة بناءً على التواريخ
        if ($startDate && $endDate) {
            $ordersQuery->whereBetween('created_at', [$startDate, $endDate]);
        }
    
        $orders = $ordersQuery->get()->map(function ($deliveryMenOrder) {
            return [
                'order_id' => $deliveryMenOrder->order_id,
                'order_numbers' => $deliveryMenOrder->order->order_numbers,
                'status' => $deliveryMenOrder->status,
            ];
        });
    
        // إرجاع النتائج كـ JSON
        return response()->json($orders);
    }

    public function showRates($deliveryManId)
    {
        // استخدام الميثود الموجودة في الـModel لحساب النسب
        $rates = (new DeliveryMenOrder())->calculateRates($deliveryManId);

        return response()->json($rates);
    }
   
    
    

}
