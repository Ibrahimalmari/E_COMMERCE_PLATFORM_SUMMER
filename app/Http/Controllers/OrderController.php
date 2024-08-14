<?php

namespace App\Http\Controllers;

use App\Events\MyEvent;
use App\Events\MyEventToCustomer;
use App\Events\OrderCreated;
use App\Events\OrderReadyForDelivery;
use App\Events\OrderReadyForDeliveryNew;
use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\DeliveryMan;
use App\Models\DeliveryMenOrder;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Store;
use App\Notifications\NotificationOrder;
use App\Notifications\OrderReadyNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'additional_info' => 'nullable|string',
                'invoice_amount' => 'nullable|numeric',
                'order_status' => 'nullable|string|in:' . implode(',', Order::getStatuses()),
                'pay_way' => 'nullable|string',
                'delivery_notes' => 'nullable|string',
                'tax' => 'nullable|numeric',
                'tip' => 'nullable|numeric',
                'delivery_fee' => 'nullable|numeric',
                'discount' => 'nullable|numeric',
                'cart_id' => 'required|exists:carts,id',
                'customer_id' => 'required|exists:customers,id',
                'store_id' => 'required|exists:stores,id',
                'address_id' => 'required|exists:addresses,id',
            ]);
    
         
     
            // Prepare the event data
            $eventData = [
                'order' => $validated,
                'message' => 'New order created successfully',
            ];
    
            // Dispatch the event
            try {
                event(new OrderCreated($eventData));
            } catch (Exception $e) {
                // Log the event dispatch error
                Log::error('Error dispatching OrderCreated event: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Error dispatching the event: ' . $e->getMessage(),
                ], 500);
            }
    
            // Return the success response including event data
            return response()->json([
                'success' => true,
                'data' => $validated,
                'event_data' => $eventData,
            ], 201);
    
        } catch (ValidationException $e) {
            // Handle validation exceptions
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . $e->getMessage(),
            ], 422);
        } catch (Exception $e) {
            // Handle any other exceptions
            Log::error('General error during order creation: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }
    

    public function acceptOrderCreation(Request $request)
{
    try {
        $validated = $request->validate([
            'additional_info' => 'nullable|string',
            'invoice_amount' => 'nullable|numeric',
            'order_status' => 'nullable|string|in:' . implode(',', Order::getStatuses()),
            'pay_way' => 'nullable|string',
            'delivery_notes' => 'nullable|string',
            'tax' => 'nullable|numeric',
            'tip' => 'nullable|numeric',
            'delivery_fee' => 'nullable|numeric',
            'discount' => 'nullable|numeric',
            'cart_id' => 'required|exists:carts,id',
            'customer_id' => 'required|exists:customers,id',
            'store_id' => 'required|exists:stores,id',
            'address_id' => 'required|exists:addresses,id',
        ]);


        // Create the order
        try {
            $order = Order::create($validated);
        } catch (QueryException $e) {
            // Log the database error
            Log::error('Database error during order creation: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Database error while creating the order: ' . $e->getMessage(),
            ], 500);
        }

        // Update the cart status to 'completed'
        try {
            $cart = Cart::find($validated['cart_id']);
            if ($cart) {
                $cart->update(['status' => 'completed']);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Cart not found.',
                ], 404);
            }
        } catch (QueryException $e) {
            // Log the database error
            Log::error('Database error during cart update: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Database error while updating the cart: ' . $e->getMessage(),
            ], 500);
        }

        $eventData = [
            'order_id' => $order->id, // Include the order_id
            'invoice_amount' => $validated['invoice_amount'],
            'delivery_fee' => $validated['delivery_fee'],
            'store_id' => $validated['store_id'],
            'tax' => $validated['tax'],
            'discount' => $validated['discount'],
            'message' => 'طلب قيد التجهيز سوف يتم تجهزو خلال الوقت المتوقع',
        ];
         // Dispatch the event
         try {
            event(new MyEventToCustomer($eventData));
        } catch (Exception $e) {
            // Log the event dispatch error
            Log::error('Error dispatching OrderCreated event: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error dispatching the event: ' . $e->getMessage(),
            ], 500);
        }

        // Return the success response including order data
        return response()->json([
            'success' => true,
            'data' => $order,
        ], 201);

    } catch (ValidationException $e) {
        // Handle validation exceptions
        return response()->json([
            'success' => false,
            'message' => 'Validation error: ' . $e->getMessage(),
        ], 422);
    } catch (Exception $e) {
        // Handle any other exceptions
        Log::error('General error during order acceptance: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'An error occurred: ' . $e->getMessage(),
        ], 500);
    }
}

public function rejectOrderCreation()
    {
        $eventData = [
            'message' => 'ولكن للأسف تم رفض الطلب بسبب ضغط العمل. الرجاء معاودة المحاولة في وقت لاحق. شكرًا لك',
        ];
        event(new MyEventToCustomer($eventData));


        return response()->json([
            'success' => true,
            'message' => 'تم  بنجاح.',
        ]);
    }


    
    



    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'order_status' => 'required|string|in:' . implode(',', Order::getStatuses()),
        ]);
    
        try {
            // جلب الطلب مع كافة العلاقات المرتبطة
            $order = Order::with(['cart.items.product', 'address', 'customer', 'store'])->findOrFail($id);
            $oldStatus = $order->order_status;
            $order->order_status = $validated['order_status'];
            $order->save();
    
            // تحقق إذا كانت الحالة الجديدة هي "الطلب جاهز للتوصيل"
            if ($order->order_status === 'الطلب جاهز للتوصيل') {
                // جلب معلومات العميل، والعنوان، والمتجر من العلاقات المضمنة
                $customer = $order->customer;
                $address = $order->address;
                $store = $order->store;
    
                // الحصول على العناصر وتفاصيل المنتج
                $cartItems = $order->cart->items->map(function($item) {
                    return [
                        'id' => $item->id,
                        'quantity' => $item->quantity,
                        'product' => [
                            'name' => $item->product->name,
                            'price' => $item->product->price,
                            'description' => $item->product->description, // افتراض وجود وصف للمنتج
                        ],
                        'items_price' => $item->items_price, // سعر العنصر في السلة
                    ];
                });
    
                $notificationData = [
                    'order_id' => $order->id,
                    'customer_name' => $customer->name,
                    'address' => $address->address,
                    'store' => [
                        'name' => $store->name,
                        'latitude' => $store->latitude,
                        'longitude' => $store->longitude,
                    ],
                    'cart_items' => $cartItems->toArray(), // تحويل العناصر إلى مصفوفة لتخزينها
                ];
    
                
                $connectedWorkers = DeliveryMan::where('status', 'متصل')->get(); // جلب العمال المتصلين

                // إرسال إشعار لكل عامل متصل
                foreach ($connectedWorkers as $worker) {
                    $worker->notify(new OrderReadyNotification($order, $notificationData));
                }
    
                // إرسال الحدث مع جميع المعاملات
                event(new OrderReadyForDelivery($order, $customer, $address, $cartItems, $store, $connectedWorkers));
            }
    
            if ($order->order_status === 'تم تسليم الطلب' && $oldStatus !== 'تم تسليم الطلب') {
                // حذف إشعار الطلب من جدول الإشعارات
                Notification::where('type', OrderReadyNotification::class)
                            ->where('data', 'like', '%"order_id":' . $order->id . '%')
                            ->delete();
            }
    
            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully',
                'data' => $order,
            ]);
    
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage(),
            ], 500);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    

    
    


    public function acceptOrderForDelivery(Request $request)
{
    // التحقق من صحة البيانات
    $request->validate([
        'order_id' => 'required',
        'delivery_worker_id' => 'required',
    ]);

    $orderId = $request->input('order_id');
    $deliveryWorkerId = $request->input('delivery_worker_id');

    try {
        // تحديث السجل بناءً على order_id
        $order = Order::find($orderId);

        if ($order) {
            // تحديث الطلب بتعيين عامل التوصيل
            $order->delivery_worker_id = $deliveryWorkerId;
            $order->save();

            // إدخال سجل جديد في جدول delivery_men_orders باستخدام Eloquent
            $deliveryMenOrder = new DeliveryMenOrder();
            $deliveryMenOrder->delivery_men_id = $deliveryWorkerId;
            $deliveryMenOrder->order_id = $orderId;
            $deliveryMenOrder->status = 'مقبول'; // حالة الطلب يمكن تعديلها حسب الحاجة
            $deliveryMenOrder->save();



            return response()->json(['message' => 'تم تحديث الطلب وإضافة السجل بنجاح.'], 200);
        } else {
            return response()->json(['message' => 'طلب غير موجود.'], 404);
        }
    } catch (\Exception $e) {
        // إعادة الرسالة الأصلية للخطأ
        return response()->json([
            'message' => 'حدث خطأ أثناء تحديث الطلب.',
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
        ], 500);
    }
}

    


public function rejectOrderForDelivery(Request $request)
{
    // التحقق من صحة البيانات
    $request->validate([
        'order_id' => 'required',
        'delivery_worker_id' => 'required',
    ]);

    $orderId = $request->input('order_id');
    $deliveryWorkerId = $request->input('delivery_worker_id');

    try {
        // تحديث السجل بناءً على order_id
        $order = Order::find($orderId);

        if ($order) {
            // تعيين حالة الطلب إلى "جاهز للتوصيل" وإلغاء تعيين delivery_worker_id
            $order->order_status = 'الطلب جاهز للتوصيل';
            $order->delivery_worker_id = null; // تعيينها إلى null
            $order->save();

            $deliveryMenOrder = new DeliveryMenOrder();
            $deliveryMenOrder->delivery_men_id = $deliveryWorkerId;
            $deliveryMenOrder->order_id = $orderId;
            $deliveryMenOrder->status = 'مرفوض'; // حالة الطلب يمكن تعديلها حسب الحاجة
            $deliveryMenOrder->save();
            
            return response()->json(['message' => 'تم تحديث حالة الطلب بنجاح.'], 200);
        } else {
            return response()->json(['message' => 'طلب غير موجود.'], 404);
        }
    } catch (\Exception $e) {
        return response()->json(['message' => 'حدث خطأ أثناء تحديث الطلب.'], 500);
    }
}


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // احصل على جميع المتاجر الخاصة بالبائع
        $stores = Store::where('seller_id', $id)->pluck('id');
    
        if ($stores->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No stores found for this seller',
            ], 404);
        }
    
        // احصل على جميع الطلبات المرتبطة بالمتاجر مع معلومات العميل والعنوان
        $orders = Order::with(['cart.items.product', 'address', 'customer']) // تضمين عنوان الزبون ومعلومات العميل
            ->whereIn('store_id', $stores)
            ->get();
    
        if ($orders->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No orders found for these stores',
            ], 404);
        }
    
        return response()->json([
            'success' => true,
            'orders' => $orders,
        ]);
    }
    

    public function ForShowMyOrderToCustomer()
    {
        $customerId = Auth::guard('api_customer')->id(); // احصل على معرف المستخدم المسجل
    
        // احصل على جميع الطلبات الخاصة بالمستخدم المسجل مع معلومات المنتج والعنوان
        $orders = Order::with(['cart.items.product.store', 'address', 'customer']) // تضمين معلومات المنتج والعنوان والعميل والمتجر
            ->where('customer_id', $customerId)
            ->get();
    
        if ($orders->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No orders found for this customer',
            ], 404);
        }
    
        return response()->json([
            'success' => true,
            'orders' => $orders,
        ]);
    }
    

    

    
    public function getOrderDetails($orderId)
{
    $customerId = Auth::guard('api_customer')->id(); // احصل على معرف المستخدم المسجل

    // احصل على تفاصيل الطلب بناءً على معرف الطلب ومعرف العميل
    $order = Order::with(['cart.items.product.store', 'address', 'customer']) // تضمين معلومات المنتج والعنوان والعميل والمتجر
        ->where('id', $orderId)
        ->where('customer_id', $customerId)
        ->first();

    if (!$order) {
        return response()->json([
            'success' => false,
            'message' => 'Order not found or does not belong to this customer',
        ], 404);
    }

    return response()->json([
        'success' => true,
        'order' => $order,
    ]);
}



    

    public function cancelOrder(Request $request)
{
    // التحقق من صحة البيانات
    $request->validate([
        'order_id' => 'required',
        'delivery_worker_id' => 'required',
    ]);

    $orderId = $request->input('order_id');
    $deliveryWorkerId = $request->input('delivery_worker_id');

    try {
        // العثور على السجل في جدول delivery_men_orders
        $deliveryMenOrder = DeliveryMenOrder::where('order_id', $orderId)
                                             ->where('delivery_men_id', $deliveryWorkerId)
                                             ->first();

        if ($deliveryMenOrder) {
            // تحديث حالة الطلب في جدول delivery_men_orders إلى "ملغى"
            $deliveryMenOrder->status = 'ملغى';
            $deliveryMenOrder->save();

            // العثور على الطلب في جدول orders
            $order = Order::find($orderId);

            if ($order) {
                // تحديث حالة الطلب إلى "جاهز للتوصيل" وتعيين delivery_worker_id إلى null
                $order->order_status = 'الطلب جاهز للتوصيل';
                $order->delivery_worker_id = null;
                $order->save();

                // جلب معلومات العميل، والعنوان، والمتجر
                $customer = $order->customer;
                $address = $order->address;
                $store = $order->store;

                // الحصول على العناصر وتفاصيل المنتج
                $cartItems = $order->cart->items->map(function($item) {
                    return [
                        'id' => $item->id,
                        'quantity' => $item->quantity,
                        'product' => [
                            'name' => $item->product->name,
                            'price' => $item->product->price,
                            'description' => $item->product->description, // افتراض وجود وصف للمنتج
                        ],
                        'items_price' => $item->items_price, // سعر العنصر في السلة
                    ];
                });

                // تحديد العاملين في التوصيل ما عدا العامل الذي تم رفض الطلب منه
                $excludedWorkerId = $deliveryWorkerId;
                $workers = DeliveryMan::where('id', '!=', $excludedWorkerId)->get();

                // إرسال الحدث إلى جميع العاملين في التوصيل ما عدا المستبعد
                foreach ($workers as $worker) {
                    event(new OrderReadyForDeliveryNew($order, $customer, $address, $cartItems, $excludedWorkerId));
                }

                return response()->json(['message' => 'تم إلغاء الطلب وتحديث حالة الطلب بنجاح.'], 200);
            } else {
                return response()->json(['message' => 'الطلب غير موجود.'], 404);
            }
        } else {
            return response()->json(['message' => 'السجل المطلوب غير موجود في جدول delivery_men_orders.'], 404);
        }
    } catch (\Exception $e) {
        return response()->json(['message' => 'حدث خطأ أثناء إلغاء الطلب.', 'error' => $e->getMessage()], 500);
    }
}



        public function getOrderStatus($id)
        {
            $order = Order::find($id);

            if (!$order) {
                return response()->json(['error' => 'Order not found'], 404);
            }

            return response()->json(['status' => $order->order_status]);
        }


        public function rateOrder(Request $request, $id)
        {
            $request->validate([
                'rating' => 'required|integer|between:1,5',
                'feedback' => 'nullable|string',
            ]);

            $order = Order::find($id);

            if (!$order) {
                return response()->json(['error' => 'Order not found'], 404);
            }

            $order->rating = $request->input('rating');
            $order->feedback = $request->input('feedback');
            $order->save();

            return response()->json(['success' => 'Rating submitted successfully']);
        }
 

}
