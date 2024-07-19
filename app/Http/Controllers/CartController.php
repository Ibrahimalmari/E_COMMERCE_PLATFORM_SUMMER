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
        // الحصول على أو إنشاء عربة التسوق للمستخدم الحالي
        $cart = Cart::firstOrCreate(
            ['customer_id' => Auth::guard('api')->id()],
            ['total_price' => 0]
        );

        // البحث عن المنتج
        $product = Product::findOrFail($request->product_id);

        // حساب سعر العناصر
        $items_price = $product->price * $request->quantity;

        // إضافة عنصر إلى عربة التسوق
        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => $request->quantity,
            'items_price' => $items_price,
            'notes' => $request->notes,
        ]);

        $cart->total_price += $items_price;
        $cart->save();

        return response()->json(['message' => 'Product added to cart successfully!', 'cart' => $cart, 'cartItem' => $cartItem], 201);
    }


    public function checkCart($customerId, $storeId)
    {
        // ابحث عن السلة للمستخدم
        $cart = Cart::where('customer_id', $customerId)->first();

        if ($cart) {
            // تحقق من وجود عناصر في السلة مرتبطة بالمتجر المحدد
            $cartItems = CartItem::where('cart_id', $cart->id)
                ->whereHas('product', function($query) use ($storeId) {
                    $query->where('store_id', $storeId);
                })
                ->with('product') // تأكد من جلب بيانات المنتج
                ->get();

            if ($cartItems->count() > 0) {
                $totalQuantity = $cartItems->sum('quantity');
                $totalPrice = $cartItems->sum(function($item) {
                    return $item->product->price * $item->quantity; // احصل على السعر من المنتج المرتبط
                });

                return response()->json([
                    'exists' => true,
                    'totalQuantity' => $totalQuantity,
                    'totalPrice' => $totalPrice,
                ]);
            }
        }

        return response()->json(['exists' => false]);
    }

    public function removeCart($customerId, $storeId)
    {
        try {
            // ابحث عن السلة للمستخدم
            $cart = Cart::where('customer_id', $customerId)->first();

            if ($cart) {
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

                    // بعد حذف العناصر، قم بحذف السلة نفسها
                    $cart->delete();

                    return response()->json(['message' => 'تم حذف السلة والسجل بنجاح'], 200);
                } else {
                    return response()->json(['message' => 'لا توجد عناصر في السلة للمتجر المحدد'], 404);
                }
            } else {
                return response()->json(['message' => 'لم يتم العثور على سلة للمستخدم المحدد'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'خطأ في حذف السلة والسجل', 'error' => $e->getMessage()], 500);
        }
    }

    public function displayProductInCartForCustomer($customerId)
    {
        // استرجاع المنتجات التي تنتمي إلى العميل المسجل الدخول، مع جلب بيانات المتجر لكل منتج
        $cartItems = CartItem::whereHas('cart', function ($query) use ($customerId) {
            $query->where('customer_id', $customerId);
        })->with(['product', 'product.store'])->get();
    
        // تضمين اسم المتجر في الاستجابة
        $cartItems = $cartItems->map(function ($cartItem) {
            $cartItem->product->store_name = $cartItem->product->store->name;
            return $cartItem;
        });
    
        return response()->json(['cart' => $cartItems], 200);
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
    $totalPrice = $cart->cartItem()->sum('items_price'); // استخدام العلاقة هنا

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

    
    
}