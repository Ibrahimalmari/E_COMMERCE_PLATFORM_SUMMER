<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{

    public function getFavorites(Request $request)
  {
    $customerId = $request->input('customer_id');

    // جلب المتاجر التي تم إضافتها إلى المفضلة من جدول Favorite وربطها بجدول المتاجر
    $favorites = Favorite::where('customer_id', $customerId)
                          ->with('store') // Assuming you have a relationship defined in the Favorite model
                          ->get();

    // تحقق مما إذا كانت القائمة فارغة
    if ($favorites->isEmpty()) {
        return response()->json(['success' => false, 'message' => 'No favorites found.']);
    }

    // إرجاع المتاجر المفضلة مع تفاصيلها
    return response()->json(['success' => true, 'favorites' => $favorites]);
    }

    public function check($customerId, $storeId)
    {
        // تحقق مما إذا كان المتجر في قائمة المفضلات للمستخدم
        $isFavorite = Favorite::where('customer_id', $customerId)
            ->where('store_id', $storeId)
            ->exists();
        
        // إرجاع استجابة JSON
        return response()->json(['isFavorite' => $isFavorite]);
    }


    public function addFavorite(Request $request)
    {
        $customerId = $request->input('customer_id');
        $storeId = $request->input('store_id');
    
        // تحقق إذا كان المتجر موجودًا بالفعل في المفضلة
        $existingFavorite = Favorite::where('customer_id', $customerId)
                                    ->where('store_id', $storeId)
                                    ->first();
    
        if ($existingFavorite) {
            return response()->json(['success' => false, 'message' => 'The store is already in favorites.']);
        }
    
        // إذا لم يكن موجودًا، أضفه إلى المفضلة
        $favorite = Favorite::create([
            'customer_id' => $customerId,
            'store_id' => $storeId,
        ]);
    
        return response()->json(['success' => true, 'favorite' => $favorite]);
    }
    

    public function removeFavorite(Request $request)
    {
        $customerId = $request->input('customer_id');
        $storeId = $request->input('store_id');

        $favorite = Favorite::where('customer_id', $customerId)
                            ->where('store_id', $storeId)
                            ->first();

        if ($favorite) {
            $favorite->delete();
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Favorite not found']);
    }
}
