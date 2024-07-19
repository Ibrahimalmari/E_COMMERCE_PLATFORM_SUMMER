<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Category;
use App\Models\DeliveryMan;
use App\Models\Product;
use App\Models\SellerMan;
use App\Models\Store;
use App\Notifications\GeneralNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index_admin()
    {
        $product = Product::all();
        return response()->json($product);   
    }

    


    public function index_seller($id)
    {
        try {
            // البحث عن صاحب المتجر
            $seller = SellerMan::findOrFail($id);
    
            // جلب جميع المتاجر التابعة لصاحب المتجر
            $stores = $seller->store;
    
            // تأكد من وجود المتاجر
            if ($stores->isEmpty()) {
                return response()->json([
                    'status' => 404,
                    'message' => 'No stores found for this seller',
                ], 404);
            }
    
            // تحضير مصفوفة لتخزين جميع المنتجات
            $allProducts = [];
    
            foreach ($stores as $store) {
                // استخدام العلاقة hasMany لجلب المنتجات المرتبطة بالمتجر
                $products = $store->product;
                // التأكد من أن $products ليست null قبل استدعاء الدالة toArray()
                if ($products !== null) {
                    // دمج المنتجات في المصفوفة
                    $allProducts = array_merge($allProducts, $products->toArray());
                }
            }
            
    
            return response()->json([
                'status' => 200,
                'stores' => $stores,
                'products' => $allProducts,
                'message' => 'Stores and their products retrieved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function displaydetailsProduct($id)  /// من اجل عرض المنتج لعميل لاضافة السلة 
    {
        $product = Product::findOrFail($id); // استرجاع التفاصيل باستخدام معرف المنتج
        return response()->json(['product' => $product]);
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
    public function store(Request $request ,$id)
    {

    try{
        $validatedData = Validator::make($request->all(),[
            'name' => 'required|regex:/^[\p{Arabic}\s]+$/u',
            'description' => 'required|regex:/^[\p{Arabic}\s]+$/u',
            'price' => 'required',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048', // Validate each image        
            'store_id' => 'required',
    ]);

    if ($validatedData->fails()) {
        return response()->json([
            'status' => 401,
            'message' => $validatedData->errors()->first(),
        ]);
    }
 

    // Check if the image hash already exists in any of the specified tables and fields
    $imageHashes = [];
 foreach ($request->file('images') as $image) {
    $imageHash = md5_file($image->path());
    $tablesAndFieldsToCheck = [
        SellerMan::class => ['PhotoOfPersonalID'],
        Store::class => ['coverPhoto'],
        DeliveryMan::class => ['PhotoOfPersonalID', 'vehicle_image', 'license_image'],
        Product::class => ['images'],
    ];

    foreach ($tablesAndFieldsToCheck as $table => $fields) {
        foreach ($fields as $field) {
            if ($table::where($field, $imageHash)->exists()) {
                throw new \Exception("عذرًا، لا يمكنك القيام بهذا الامر يرجى التأكد من صحة البيانات المدخلة وسنحاول مرة أخرى لاحقاً");
            }
        }
    }

            $store = Store::findOrFail($request->store_id);
            $storeName = $store->name;

            $category_id = $request->category_id ?? null;
            $branch_id = $request->branch_id ?? null;

            $imageHashes[] = $imageHash;
            $image->move(public_path("products/"), $imageHash);

        }
          $category = Category::find($request->category_id);
        $categoryName = $category ? $category->name : 'N/A';

        $branch = Branch::find($request->branch_id);
        $branchName = $branch ? $branch->name : 'N/A';

        $seller = SellerMan::findOrFail($id);
        $sellerName = $seller->name;

          $NotificationProduct = Admin::all(); 

          $data = [
              'product_name' => $request->name,
              'product_description' => $request->description,
              'product_price' => $request->price,
              'product_quantity' => $request->quantity,
              'product_store_id' => $request->store_id,
              'product_category_id' =>$category_id,
              'product_branch_id' => $branch_id,
              'product_store_name' => $storeName,
              'product_category_name' => $categoryName,
              'product_branch_name' => $branchName,
              'product_seller_name' => $sellerName,
              'product_created_by' => $seller->id,
              'product_images' => implode(',', $imageHashes), 
          ];

           // التحقق من عدم تكرار نفس المحتوى في جدول الإشعارات
           $existingNotification = DB::table('notifications')
           ->where(function ($query) use ($data) {
               $query->where('data', json_encode(['type' => 'product', 'data' => $data]));
           })
           ->first();
   
       if ($existingNotification) {
           return response()->json([
               'status' => 401,
               'message' => 'لا يمكن القيام بذلك لأنه قد تم بالفعل. يرجى الانتظار حتى تتم الموافقة على طلبك',
           ]);
       }
  
          Notification::send($NotificationProduct, new GeneralNotification('product', $data));

          return response()->json([
            'status' => 200, 
            'user' =>$id,
            'images'=>$request->images,
            'message'=>'تمت العملية بنجاح. يرجى الانتظار حتى الموافق على طلبك.',
        ]);

    } catch (ValidationException $e) {
        return response()->json([
            'status' => 401 ,
            'message' => $e->getMessage(),
        ]);
    } catch (\Exception $ex) {
        return response()->json([
            'status' => 500 ,
            'message' => $ex->getMessage(),
        ]);
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
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        try {
            $product = Product::findOrFail($id);

            if (!$product) {
                return response()->json([
                    'status' => 404,
                    'message' => 'لم يتم العثور عليه',
                ]);
            }
    
            return response()->json([
                'status' => 200,
                'product' => $product,
            ]);
    
        } catch (\Exception $ex) {
            return response()->json([
                'status' => 500,
                'message' => $ex->getMessage(),
            ]);
        }
    }
    

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
{
    $product = Product::findOrFail($id);
    try{
    $validatedData = Validator::make($request->all(), [
        'name' => 'required|regex:/^[\p{Arabic}\s]+$/u',
        'description' => 'required|regex:/^[\p{Arabic}\s]+$/u',
        'price' => 'required',
        'store_id' => 'required',
        'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
    ]);

    if ($validatedData->fails()) {
        return response()->json([
            'status' => 401,
            'message' => $validatedData->errors()->first(),
        ]);
    }

    $oldImages = explode(',', $product->images);

    $newImageHashes = [];
    if ($request->hasFile('images')) {
        $tablesAndFieldsToCheck = [
            SellerMan::class => ['PhotoOfPersonalID'],
            Store::class => ['coverPhoto'],
            DeliveryMan::class => ['PhotoOfPersonalID', 'vehicle_image', 'license_image'],
            Product::class => ['images'],
        ];

        foreach ($request->file('images') as $image) {
            $imageHash = md5_file($image->path());
            foreach ($tablesAndFieldsToCheck as $table => $fields) {
                foreach ($fields as $field) {
                    if ($table::where($field, $imageHash)->exists()) {
                        throw new \Exception('عذرًا، لا يمكنك القيام بهذا الامر يرجى التأكد من صحة البيانات المدخلة وسنحاول مرة أخرى لاحقاً');
                    }
                }
            }

            if ($image->isValid()) {
                $imagePath = $image->path();
                if (file_exists($imagePath)) {
                    $imageHash = md5_file($imagePath);
                    $newImageHashes[] = $imageHash;
                    $image->move(public_path("products/"), $imageHash);

                } else {
                    throw new \Exception("Image file not found.");
                }
            } else {
                throw new \Exception("Invalid image file.");
            }
        }
    }

    $category_id = $request->category_id ?? null;
    $branch_id = $request->branch_id ?? null;

    $store = Store::find($product->store_id);
    $seller_id = $store ? $store->seller_id : null;

    $oldData = [
        'product_name' => $product->name,
        'product_description' => $product->description,
        'product_price' => (double)$product->price,
        'product_quantity' => (int)$product->quantity,
        'product_store_id' => (int)$product->store_id,
        'product_store_name' => $product->store ? $product->store->name : null,
        'product_seller_name' => $product->store && $product->store->seller ? $product->store->seller->name : null,
        'product_created_by' => $seller_id,
        'product_category_id' => (int)$product->category_id,
        'product_category_name' => $product->category ? $product->category->name : null,
        'product_branch_id' =>(int)$product->branch_id,
        'product_branch_name' => $product->branch ? $product->branch->name : null,
        'product_images' => implode(',', $oldImages),
    ];

    $newData = [
        'product_name' => $request->name,
        'product_description' => $request->description,
        'product_price' => (double)$request->price,
        'product_quantity' => (int)$request->quantity,
        'product_store_id' => (int)$request->store_id,
        'product_store_name' => Store::find($request->store_id) ? Store::find($request->store_id)->name : null,
        'product_created_by' => $seller_id,
        'product_seller_name' => Store::find($request->store_id) && Store::find($request->store_id)->seller ? Store::find($request->store_id)->seller->name : null,
        'product_category_id' => (int)$category_id,
        'product_category_name' => $request->has('category_id') && Category::find($request->category_id) ? Category::find($request->category_id)->name : ($product->category ? $product->category->name : null),
        'product_branch_id' =>  (int)$branch_id,
        'product_branch_name' => $request->has('branch_id') && Branch::find($request->branch_id) ? Branch::find($request->branch_id)->name : ($product->branch ? $product->branch->name : null),
        'product_images' => implode(',', $newImageHashes),
    ];

   if(!$newImageHashes){
    $newData['product_images'] = implode(',', $oldImages);
   }
    $isModified = false;
    foreach ($oldData as $key => $value) {
        if ($newData[$key] != $value) {
            $isModified = true;
            break;
        }
    }

    if (!$isModified) {
        return response()->json([
            'status' => 200,
            'isModified' => $isModified,
            'old_data' => $oldData,
            'new_data' => $newData,
            'message' => 'لم يتم إجراء أي تغييرات.',
        ]);
    }

    $data = [
        'old_data' => $oldData,
        'new_data' => $newData,
    ];

    $existingNotification = DB::table('notifications')
        ->where(function ($query) use ($data) {
            $query->where('data', json_encode(['type' => 'product', 'data' => $data]));
        })
        ->first();

    if ($existingNotification) {
        return response()->json([
            'status' => 401,
            'message' => 'لا يمكن القيام بذلك لأنه قد تم بالفعل. يرجى الانتظار حتى تتم الموافقة على طلبك',
        ]);
    }

    $admins = Admin::all();
    Notification::send($admins, new GeneralNotification('product', $data));

    return response()->json([
        'status' => 200,
        'isModified' => $isModified,
        'old_data' => $oldData,
        'new_data' => $newData,
        'message' => 'تمت العملية بنجاح. يرجى الانتظار حتى الموافقة على طلبك.',
    ]);

} catch (ValidationException $e) {
    return response()->json([
        'status' => 401 ,
        'message' => $e->getMessage(),
    ]);
} catch (\Exception $ex) {
    return response()->json([
        'status' => 500 ,
        'message' => $ex->getMessage(),
    ]);
}

}

        
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
   
public function destroy($id)
{
    try {
        $product = Product::findOrFail($id);
        

        
          // الحصول على معلومات المتجر المرتبط بالمنتج
        $store = Store::findOrFail($product->store_id);
        $storeName = $store->name;  // اسم المتجر

        // Delete product images
        if ($product->images) {
            $images = explode(',', $product->images);
            foreach ($images as $image) {
                $imagePath = public_path("products/".$image);
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
        }
        
    // Check if the folder is empty
    $folderPath = public_path("products/");
    if (count(glob("$folderPath/*")) === 0) {
        // Delete the folder if it's empty
        rmdir($folderPath);
         }

        // Delete product
        $product->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Product deleted successfully',
        ]);

    } catch (\Exception $ex) {
        return response()->json([
            'status' => 500,
            'message' => $ex->getMessage(),
        ]);
    }
  }
}
