<?php

namespace App\Http\Controllers;

use App\Events\MyEvent;
use App\Events\MyEventToCustomer;
use App\Events\OrderCreated;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Store;
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
            'order' => $validated,
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
            $order = Order::findOrFail($id);
            $order->order_status = $validated['order_status'];
            $order->save();

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
        $customerId = Auth::guard('api')->id(); // احصل على معرف المستخدم المسجل
    
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
    

    

    
    
    

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
 

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
