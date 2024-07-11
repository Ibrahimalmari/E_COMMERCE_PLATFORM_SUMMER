<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\SellerMan;
use App\Models\Store;
use App\Notifications\ItemAccepted;
use App\Notifications\ItemReject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function acceptNotification($notification_id, $notifiable_id)
{
    $notification = DatabaseNotification::where('id', $notification_id)
        ->where('notifiable_id', $notifiable_id)
        ->first();

    if ($notification) {
        // تحقق إذا كانت البيانات تحتاج إلى تحليل من JSON
        $data = is_string($notification->data) ? json_decode($notification->data, true) : $notification->data;

        $isAccepted = false;
        $message = '';
        $seller = null;

        switch ($data['type']) {
            case 'category':
                $store_id = $data['data']['old_data']['category_store_id'] ?? $data['data']['category_store_id'];
                $store = Store::find($store_id);
                if ($store) {
                    $seller = $store->seller;

                    // البحث عن الفئة بناءً على البيانات القديمة المخزنة في الإشعار إذا كانت موجودة
                    $category = null;
                    if (isset($data['data']['old_data'])) {
                        $category = Category::where('name', $data['data']['old_data']['category_name'])
                                            ->where('store_id', intval($data['data']['old_data']['category_store_id']))
                                            ->first();
                    }

                    // تسجيل نتيجة البحث
                    Log::info('Category Search Result:', [$category]);

                    if ($category) {
                        // تحديث الفئة إذا كانت موجودة بالفعل
                        $categoryData = array_filter([
                            'name' => $data['data']['new_data']['category_name'],
                            'slug' => $data['data']['new_data']['category_slug'] ,
                            'description' => $data['data']['new_data']['category_description'] ,
                            'store_id' => intval($data['data']['new_data']['category_store_id']),
                        ]);
                        $category->update($categoryData);
                        $message = 'Category accepted and updated';
                    } else {
                        // إنشاء فئة جديدة إذا لم تكن موجودة
                        $categoryData = array_filter([
                            'name' => $data['data']['category_name'],
                            'slug' => $data['data']['category_slug'],
                            'description' => $data['data']['category_description'],
                            'store_id' =>  intval($data['data']['category_store_id']),
                        ]);
                        $category = Category::create($categoryData);
                        $message = 'Category accepted and created';
                    }
                    $isAccepted = true;
                }
                break;

            case 'product':
                $store_id = $data['data']['old_data']['product_store_id'] ?? $data['data']['product_store_id'];
                $store = Store::find($store_id);
                if ($store) {
                    $seller = $store->seller;
                    $images = $data['data']['old_data']['product_images'] ?? $data['data']['product_images'];

                    // البحث عن المنتج بناءً على البيانات القديمة المخزنة في الإشعار إذا كانت موجودة
                    $product = null;
                    if (isset($data['data']['old_data'])) {
                        $product = Product::where('name', $data['data']['old_data']['product_name'])
                                          ->where('store_id', intval($data['data']['old_data']['product_store_id']))
                                          ->first();
                    }

                    // تسجيل نتيجة البحث
                    Log::info('Product Search Result:', [$product]);

                    if ($product) {
                        $category_id = null;
                        $branch_id = null;

                        $category_id = isset($data['data']['new_data']['product_category_id']) && $data['data']['new_data']['product_category_id'] !== 'undefined'
                        ? $data['data']['new_data']['product_category_id'] : null;
                    $branch_id = isset($data['data']['new_data']['product_branch_id']) && $data['data']['new_data']['product_branch_id'] !== 'undefined'
                        ? $data['data']['new_data']['product_branch_id'] : null;
                    
                        $oldImages = explode(',', $data['data']['old_data']['product_images']);
                        $newImages = explode(',', $data['data']['new_data']['product_images']);
                            $productData = array_filter([
                            'name' => $data['data']['new_data']['product_name'] ,
                            'description' => $data['data']['new_data']['product_description'] ,
                            'price' => $data['data']['new_data']['product_price'],
                            'store_id' => intval($data['data']['new_data']['product_store_id']),
                            'quantity' => $data['data']['new_data']['product_quantity'],
                            'category_id' => $category_id,
                            'branch_id' => $branch_id,
                            'images' => implode(',', $newImages),
                        ]);

                        if ($newImages != $oldImages) {
                            // حذف الصور القديمة فقط إذا تم إضافة صور جديدة
                            foreach ($oldImages as $oldImage) {
                                $oldImagePath = public_path("products/" . $oldImage);
                                if (file_exists($oldImagePath)) {
                                    unlink($oldImagePath);
                                }
                            }
                        }

                        foreach ($newImages as $imageHash) {
                            $sourcePath = public_path("tmp/" . $imageHash);
                            $destinationPath = public_path("products/" . $imageHash);
                            if (file_exists($sourcePath)) {
                                rename($sourcePath, $destinationPath);
                            }
                        }
                        $product->update($productData);
                        $message = 'Product accepted and updated';
                    } else {
                       
                          $category_id = isset($data['data']['product_category_id']) && $data['data']['product_category_id'] !== 'undefined'
                            ? $data['data']['product_category_id'] : null;
                        $branch_id = isset($data['data']['product_branch_id']) && $data['data']['product_branch_id'] !== 'undefined'
                            ? $data['data']['product_branch_id'] : null;
                       
                      
                        // إنشاء منتج جديد إذا لم يكن موجوداً
                        $productData = array_filter([
                            'name' => $data['data']['product_name'],
                            'description' =>  $data['data']['product_description'],
                            'price' =>  $data['data']['product_price'],
                            'store_id' => intval($data['data']['product_store_id']),
                            'quantity' => $data['data']['product_quantity'],
                            'category_id' => $category_id,
                            'branch_id' => $branch_id,
                            'images' => $images,
                        ]);
                        $product = Product::create($productData);
                        $message = 'Product accepted and created';
                    }
                    $isAccepted = true;
                }
                break;

            case 'branch':
                $category_id = $data['data']['old_data']['branch_category_id'] ?? $data['data']['branch_category_id'];
                $category = Category::find($category_id);
                if ($category) {
                    $store = $category->store;
                    $seller = $store->seller;

                    // البحث عن الفرع بناءً على البيانات القديمة المخزنة في الإشعار إذا كانت موجودة
                    $branch = null;
                    if (isset($data['data']['old_data'])) {
                        $branch = Branch::where('name', $data['data']['old_data']['branch_name'])
                                        ->where('category_id', $data['data']['old_data']['branch_category_id'])
                                        ->first();
                    }

                    // تسجيل نتيجة البحث
                    Log::info('Branch Search Result:', [$branch]);

                    if ($branch) {
                        // تحديث الفرع إذا كان موجوداً بالفعل
                        $branchData = array_filter([
                            'name' => $data['data']['new_data']['branch_name'],
                            'category_id' =>  $data['data']['new_data']['branch_category_id'],
                        ]);
                        $branch->update($branchData);
                        $message = 'Branch accepted and updated';
                    } else {
                        // إنشاء فرع جديد إذا لم يكن موجوداً
                        $branchData = array_filter([
                            'name' =>  $data['data']['branch_name'],
                            'category_id' => $data['data']['branch_category_id'],
                        ]);
                        $branch = Branch::create($branchData);
                        $message = 'Branch accepted and created';
                    }
                    $isAccepted = true;
                }
                break;

            default:
                $message = 'Unknown notification type';
                break;
        }

        if ($isAccepted) {
              // إرسال إشعار إلى صاحب المتجر
              if ($seller) {
                $itemType = $data['type'];
                $itemName = $data['data']['new_data'][$itemType . '_name'] ?? $data['data'][$itemType . '_name'];
                Notification::send($seller, new ItemAccepted($itemType, $itemName));
            }
            $notification->delete();
            return response()->json([
                'status' => 200,
                'message' => $message,
                'store_owner' => $seller // إضافة معلومات صاحب المتجر إلى الرد
            ]);
        } else {
            return response()->json(['status' => 400, 'message' => $message]);
        }
    }

    return response()->json(['status' => 404, 'message' => 'Notification not found']);
}


    

    

public function rejectNotification(Request $request)
{
    // استخدام النموذج الصحيح للعثور على الإشعار
    $notification = DatabaseNotification::find($request->notification_id);


    if (!$notification) {
        return response()->json(['status' => 404, 'message' => 'الإشعار غير موجود']);
    }

    // فك تشفير بيانات الإشعار إذا كانت مخزنة بتنسيق JSON
    $jsonData = is_array($notification->data) ? $notification->data : json_decode($notification->data, true);

    $rejectReason = $request->input('reject_reason');
    $recipient = null;
    $rejectedData = null; // متغير لحفظ البيانات المرفوضة

    if (isset($jsonData['type'])) {
        $data = null;

        // التحقق من وجود 'old_data' أو 'data' وتعيينها إلى $data
        if (isset($jsonData['data']['new_data'])) {
            $data = $jsonData['data']['new_data'];
        } elseif (isset($jsonData['data'])) {
            $data = $jsonData['data'];
        }

        // المتابعة إذا تم تعيين $data بشكل صحيح
        if ($data !== null) {
            switch ($jsonData['type']) {
                case 'product':
                    $recipient = SellerMan::find($data['product_created_by']);
                    $rejectedData = $data; // تعيين البيانات المرفوضة
                    break;
                case 'category':
                    $recipient = SellerMan::find($data['category_created_by']);
                    $rejectedData = $data; // تعيين البيانات المرفوضة
                    break;
                case 'branch':
                    $recipient = SellerMan::find($data['branch_seller_id']);
                    $rejectedData = $data; // تعيين البيانات المرفوضة
                    break;
            }
        } else {
            // التعامل مع الخطأ: لا توجد مفتاح 'old_data' ولا 'data'
            $recipient = null;
        }
    }

    if ($recipient) {
        // إرسال الإشعار مع السبب والبيانات المرفوضة
        Notification::send($recipient, new ItemReject($rejectReason, $rejectedData));
    }

    // التحقق مما إذا كان الإشعار متعلقًا بنوع المنتج
    if (isset($jsonData['type']) && $jsonData['type'] === 'product') {
           
        // تحقق من وجود صور المنتج وحذفها إذا كانت موجودة
        if (isset($jsonData['data']['product_images']) && is_string($jsonData['data']['product_images'] )) {
            $productImages = explode(',', $jsonData['data']['product_images']);

            foreach ($productImages as $image) {
                $imagePath = public_path('products/' . $image);
                if (File::exists($imagePath)) {
                    File::delete($imagePath);
                }
            }
        }
        if($jsonData['data']['new_data']['product_images'] != $jsonData['data']['old_data']['product_images']){

        // تحقق من وجود صور البيانات الجديدة وحذفها إذا كانت موجودة
        if (isset($jsonData['data']['new_data']) && is_array($jsonData['data']['new_data'])) {
            if (isset($jsonData['data']['new_data']['product_images']) && is_string($jsonData['data']['new_data']['product_images'])) {
                $newImages = explode(',', $jsonData['data']['new_data']['product_images']);
                               
                foreach ($newImages as $image) {
                    $imagePath = public_path('products/' . $image);
                    if (File::exists($imagePath)) {
                        File::delete($imagePath);
                    }
                }
            
        }
             else {
                return response()->json(['status' => 500, 'message' => 'صور جديدة غير موجودة أو غير صحيحة.']);
            }
        }
      }
    }  

    // تعيين 'read_at' وحذف الإشعار
    $notification->read_at = now();
    $notification->save();
    $notification->delete();

    return response()->json(['status' => 200, 'message' => 'تم رفض الإشعار بنجاح' ,'new' => $jsonData['data']['new_data'] ,'old' => $jsonData['data']['old_data']
    ,'rejectedData' => $rejectedData , 'recipient' => $recipient]);
}







    
public function rejectAllNotifications(Request $request)
{
    // ابحث عن جميع الإشعارات غير المقروءة
    $notifications = DatabaseNotification::whereNull('read_at')->get();

    $rejectReason = $request->input('reject_reason');


    // حلق على كل إشعار وقم برفضه وحذفه
    foreach ($notifications as $notification) {
        // إذا كانت البيانات مصفوفة بالفعل، لا داعي لاستخدام json_decode()
        $jsonData = is_array($notification->data) ? $notification->data : json_decode($notification->data, true);

        // التحقق مما إذا كان الإشعار يتعلق بفئة المنتجات
        if (isset($jsonData['type']) && $jsonData['type'] === 'product') {
            // التحقق مما إذا كانت product_images موجودة وهي سلسلة نصية
            if (isset($jsonData['data']['product_images']) && is_string($jsonData['data']['product_images'])) {
                // تحويل السلسلة النصية إلى مصفوفة
                $productImages = explode(',', $jsonData['data']['product_images']);

                // حذف صور المنتج المخزنة
                foreach ($productImages as $image) {
                    // بناء المسار الكامل للصورة
                    $imagePath = public_path('products/' . $image);

                    // التحقق مما إذا كانت الصورة موجودة ومن ثم حذفها
                    if (File::exists($imagePath)) {
                        File::delete($imagePath);
                    }
                }
            }


            if($jsonData['data']['new_data']['product_images'] != $jsonData['data']['old_data']['product_images']){

            // التحقق مما إذا كانت new_data موجودة وهي مصفوفة
            if (isset($jsonData['data']['new_data']) && is_array($jsonData['data']['new_data'])) {
                if (isset($jsonData['data']['new_data']['product_images']) && is_string($jsonData['data']['new_data']['product_images'])) {
                    // تحويل السلسلة النصية إلى مصفوفة
                    $newImages = explode(',', $jsonData['data']['new_data']['product_images']);

                    // حذف صور المنتج المخزنة
                    foreach ($newImages as $image) {
                        // بناء المسار الكامل للصورة
                        $imagePath = public_path('products/' . $image);

                        // التحقق مما إذا كانت الصورة موجودة ومن ثم حذفها
                        if (File::exists($imagePath)) {
                            File::delete($imagePath);
                        }
                    }
                }
            }
        }
          
        }


        // تعيين 'read_at' وحذف الإشعار
        $notification->read_at = now();
        $notification->save();
        $notification->delete();

        // إرسال إشعار بالرفض إلى المستخدم
        $recipient = null;
        if (isset($jsonData['data']['product_created_by'])) {
            $recipient = SellerMan::find($jsonData['data']['product_created_by']);
        } elseif (isset($jsonData['data']['category_created_by'])) {
            $recipient = SellerMan::find($jsonData['data']['category_created_by']);
        } elseif (isset($jsonData['data']['branch_seller_id'])) {
            $recipient = SellerMan::find($jsonData['data']['branch_seller_id']);
        } elseif (isset($jsonData['data']['new_data']) && isset($jsonData['data']['new_data']['product_created_by'])) {
            $recipient = SellerMan::find($jsonData['data']['new_data']['product_created_by']);
        } elseif (isset($jsonData['data']['new_data']) && isset($jsonData['data']['new_data']['category_created_by'])) {
            $recipient = SellerMan::find($jsonData['data']['new_data']['category_created_by']);
        } elseif (isset($jsonData['data']['new_data']) && isset($jsonData['data']['new_data']['branch_seller_id'])) {
            $recipient = SellerMan::find($jsonData['data']['new_data']['branch_seller_id']);
        }
        
        

        if ($recipient) {
            Notification::send($recipient, new ItemReject($rejectReason, $jsonData['data']));
        }
    }

    


    return response()->json(['status' => 200, 'message' => 'All notifications rejected and related product images deleted' ,'recipient' => $recipient]);
}




public function markAllAsRead(Request $request)   ///in dashboard seller 
{
    $sellerId = $request->input('sellerId');
    $notifications = DatabaseNotification::where('notifiable_id', $sellerId)
    ->where('notifiable_type', 'App\Models\SellerMan')
    ->get();

    foreach ($notifications as $notification) {
        $notification->read_at = now();
        $notification->save();
        $notification->delete();
    }

    return response()->json([
        'status' => 200,
        'message' => 'تم تعليم جميع الإشعارات كمقروءة وحذفها بنجاح'
    ]);
}
  

    
}
