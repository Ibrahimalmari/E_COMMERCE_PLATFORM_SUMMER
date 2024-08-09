<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Google\Auth\Cache\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function addToCart(Request $request)
{
    $customerId = Auth::guard('api_customer')->id();

    // البحث عن المنتج
    $product = Product::findOrFail($request->product_id);
    $storeId = $product->store_id;

    // البحث عن السلة الحالية للمستخدم التي ليست مكتملة ولها نفس store_id
    $cart = Cart::where('customer_id', $customerId)
                ->where('status', '!=', 'completed')
                ->where('store_id', $storeId)
                ->first();

    // إذا لم يتم العثور على سلة غير مكتملة بنفس store_id، إنشاء سلة جديدة
    if (!$cart) {
        $cart = Cart::create([
            'customer_id' => $customerId,
            'total_price' => 0,
            'store_id' => $storeId, // تعيين store_id
        ]);
    }

    // البحث عن العنصر في السلة
    $cartItem = CartItem::where('cart_id', $cart->id)
                        ->where('product_id', $product->id)
                        ->first();

    if ($cartItem) {
        // إذا كان العنصر موجودًا بالفعل، قم بزيادة الكمية
        $cartItem->quantity += $request->quantity;
        $cartItem->items_price += $product->price * $request->quantity;
        $cartItem->save();
    } else {
        // إذا لم يكن العنصر موجودًا، قم بإنشاء عنصر جديد
        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => $request->quantity,
            'items_price' => $product->price * $request->quantity,
            'notes' => $request->notes,
        ]);
    }

    // تحديث السعر الإجمالي للسلة
    $cart->total_price += $product->price * $request->quantity;
    $cart->save();

    return response()->json(['message' => 'Product added to cart successfully!', 'cart' => $cart, 'cartItem' => $cartItem], 201);
}

    


    public function checkCart($customerId, $storeId)
    {
        // جلب جميع السلات غير المكتملة الخاصة بالمستخدم
        $carts = Cart::where('customer_id', $customerId)
            ->where('status', '!=', 'completed') // تجاهل السلات المكتملة
            ->get();
    
        $totalQuantity = 0;
        $totalPrice = 0;
        $cartStatus = 'none'; // حالة السلة الافتراضية
    
        foreach ($carts as $cart) {
            // تحقق من وجود عناصر في السلة مرتبطة بالمتجر المحدد
            $cartItems = CartItem::where('cart_id', $cart->id)
                ->whereHas('product', function($query) use ($storeId) {
                    $query->where('store_id', $storeId);
                })
                ->with('product') // تأكد من جلب بيانات المنتج
                ->get();
    
            if ($cartItems->count() > 0) {
                $totalQuantity += $cartItems->sum('quantity');
                $totalPrice += $cartItems->sum(function($item) {
                    return $item->product->price * $item->quantity; // احصل على السعر من المنتج المرتبط
                });
    
                // افتراض أن حالة السلة الأخيرة هي ما نحتاجه
                $cartStatus = $cart->status;
            }
        }
    
        if ($totalQuantity > 0) {
            return response()->json([
                'exists' => true,
                'cartStatus' => $cartStatus,
                'totalQuantity' => $totalQuantity,
                'totalPrice' => $totalPrice,
            ]);
        }
    
        return response()->json(['exists' => false]);
    }
    
    
    public function removeCart($customerId, $storeId)
{
    try {
        // جلب جميع السلات غير المكتملة للمستخدم
        $carts = Cart::where('customer_id', $customerId)
            ->where('status', '!=', 'completed')
            ->get();

        if ($carts->count() > 0) {
            foreach ($carts as $cart) {
                // تحقق من وجود عناصر في السلة مرتبطة بالمتجر المحدد
                $cartItems = CartItem::where('cart_id', $cart->id)
                    ->whereHas('product', function($query) use ($storeId) {
                        $query->where('store_id', $storeId);
                    })
                    ->get();

                if ($cartItems->count() > 0) {
                    // احذف العناصر من السلة
                    CartItem::where('cart_id', $cart->id)
                        ->whereHas('product', function($query) use ($storeId) {
                            $query->where('store_id', $storeId);
                        })
                        ->delete();

                    // بعد حذف العناصر، تحقق مما إذا كانت السلة فارغة الآن
                    if (CartItem::where('cart_id', $cart->id)->count() == 0) {
                        // إذا كانت السلة فارغة، احذف السلة نفسها
                        $cart->delete();
                    }
                }
            }

            return response()->json(['message' => 'تم حذف السلات غير المكتملة الخاصة بالمتجر بنجاح'], 200);
        } else {
            return response()->json(['message' => 'لا توجد سلات غير مكتملة للمستخدم المحدد'], 404);
        }
    } catch (\Exception $e) {
        return response()->json(['message' => 'خطأ في حذف السلات غير المكتملة الخاصة بالمتجر', 'error' => $e->getMessage()], 500);
    }
}


public function displayProductInCartForCustomer($customerId, $storeId)
{
    // ابحث عن جميع السلال غير المكتملة للمستخدم
    $carts = Cart::where('customer_id', $customerId)
                ->where('status', '!=', 'completed')
                ->get();

    if ($carts->isEmpty()) {
        return response()->json(['cart' => [], 'message' => 'No incomplete carts found.'], 200); // لا توجد سلة للمستخدم
    }

    // اجمع كل عناصر السلال غير المكتملة
    $cartItems = CartItem::whereIn('cart_id', $carts->pluck('id'))
                          ->whereHas('product', function ($query) use ($storeId) {
                              $query->where('store_id', $storeId);
                          })
                          ->with(['product', 'product.store'])
                          ->get();

    if ($cartItems->isEmpty()) {
        return response()->json(['cart' => [], 'message' => 'No items found for this store.'], 200); // لا توجد عناصر في السلة
    }

    // تضمين اسم المتجر في الاستجابة
    $cartItems = $cartItems->map(function ($cartItem) {
        $cartItem->product->store_name = $cartItem->product->store->name;
        return $cartItem;
    });

    return response()->json([
        'cart' => $cartItems,
        'store_name' => $cartItems->first()->product->store_name
    ], 200);
}

    


  // في تحديث الكمية بالنموذج Cart
  public function updateQuantity($id, Request $request)
  {
      $request->validate([
          'quantity' => 'required|integer|min:1',
      ]);
  
      // العثور على عنصر السلة المطلوب
      $cartItem = CartItem::findOrFail($id);
      $oldQuantity = $cartItem->quantity;
      $cartItem->quantity = $request->input('quantity');
      $cartItem->save();
  
      // إعادة حساب السعر الإجمالي للعنصر بناءً على الكمية الجديدة
      $itemsPriceDifference = ($cartItem->quantity - $oldQuantity) * $cartItem->product->price;
      $cartItem->items_price += $itemsPriceDifference;
      $cartItem->save();
  
      // إعادة حساب السعر الإجمالي للسلة
      $cart = Cart::findOrFail($cartItem->cart_id);
      $totalPrice = $cart->items()->sum('items_price'); // استخدام العلاقة الصحيحة هنا
  
      // تحديث السعر الإجمالي في جدول carts
      $cart->total_price = $totalPrice;
      $cart->save();
  
      return response()->json(['message' => 'تم تحديث الكمية والسعر الإجمالي بنجاح']);
  }
  

    
    

public function removeItem($id)
{
    $cartItem = CartItem::findOrFail($id);
    $cart_id = $cartItem->cart_id; // افتراض أن هناك علاقة cart_id

    $cartItem->delete();

    // Check remaining items in the cart for the same cart_id
    $remainingItems = CartItem::where('cart_id', $cart_id)->count();

    if ($remainingItems === 0) {
        // Delete the entire cart if it was the last item
        Cart::where('id', $cart_id)->delete();
        return response()->json(['message' => 'تم حذف المنتج من السلة بنجاح. تم حذف السلة بأكملها.']);
    }

    return response()->json(['message' => 'تم حذف المنتج من السلة بنجاح']);
}


 // في App\Http\Controllers\CartController.php

 public function getSavedCarts()
 {
     $customerId = Auth::guard('api')->id();
 
     // استعلام لجلب السلات غير المكتملة مع تفاصيل المتجر والعناصر والمنتجات
     $carts = Cart::with([
         'store', 
         'items.product' // يتطلب علاقة في CartItem لجلب المنتج
     ])
     ->where('customer_id', $customerId)
     ->where('status', 'uncompleted') // تأكد من استخدام العمود الصحيح هنا
     ->get()
     ->map(function ($cart) {
         return [
             'id' => $cart->id,
             'storeName' => $cart->store ? $cart->store->name : 'غير متوفر',
             'storeImage' => $cart->store ? $cart->store->coverPhoto : null,
             'store_id' =>  $cart->store_id,
             'items' => $cart->items->map(function ($item) {
                 return [
                     'productName' => $item->product->name,
                     'productImage' => $item->product->images, // تأكد من اسم العمود الصحيح
                     'quantity' => $item->quantity
                 ];
             }),
             'totalPrice' => $cart->items->sum(function ($item) {
                 return $item->product->price * $item->quantity;
             })
         ];
     });
 
     return response()->json([
         'success' => true,
         'carts' => $carts
     ]);
 }
 
 


 // دالة لجلب تفاصيل سلة معينة
 public function getCartDetails($cartId)
 {
    $customerId = Auth::guard('api')->id();

     $cart = Cart::where('id', $cartId)->where('customer_id', $customerId)->with('items.product')->firstOrFail();

     return response()->json([
         'success' => true,
         'cart' => $cart
     ]);
 }
    
    
}