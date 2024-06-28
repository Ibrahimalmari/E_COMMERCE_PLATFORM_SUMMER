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
                throw new \Exception("Image already exists in the database.");
            }
        }
    }

            $store = Store::findOrFail($request->store_id);
            $storeName = $store->name;


            $imageHashes[] = $imageHash;
        }


      $seller = Product::create([
            'name' => $request->name,
            'description'=> $request->description,
            'price'=> $request->price,
            'quantity'=> $request->quantity,
            'phone'=> $request->phone,
            'images' => implode(',', $imageHashes), // Assuming you're storing multiple image hashes as a comma-separated string
            'category_id' => $request->category_id !== 'undefined' ? $request->category_id : null,
            'store_id'=> $request->store_id,
            'branch_id' => $request->branch_id !== 'undefined' ? $request->branch_id : null,
            'created_by'=>$id,
          ]);
          $image->move(public_path("products/"), $imageHash);


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
              'product_store_name' => $storeName,
              'product_category_name' => $categoryName,
              'product_branch_name' => $branchName,
              'product_created_by' => $sellerName,
              'product_images' => $imageHashes,
          ];
  
          Notification::send($NotificationProduct, new GeneralNotification('product', $data));

          return response()->json([
            'status' => 200, 
            'user' =>$id,
            'images'=>$request->images,
            'message'=>'Product added successfully',
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
        try {
            $product = Product::findOrFail($id);
            
            $validatedData = Validator::make($request->all(), [
                'name' => 'required|regex:/^[\p{Arabic}\s]+$/u',
                'description' => 'required|regex:/^[\p{Arabic}\s]+$/u',
                'price' => 'required',
                'store_id' => 'required',
                'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048', // قواعد الصور المتعددة
            ]);
    
            if ($validatedData->fails()) {
                return response()->json([
                    'status' => 401,
                    'message' => $validatedData->errors()->first(),
                ]);
            }

            
    
            if ($request->hasFile('images')) {

      
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
                                throw new \Exception("Image already exists in the database.");
                            }
                        }
                    }
                }   
                    $store = Store::findOrFail($request->store_id);
                    $storeName = $store->name;        
    
                // Delete old images if they exist
            if ($product->images) {
                $oldImages = explode(',', $product->images);
                foreach ($oldImages as $oldImage) {
                    $oldImagePath = public_path("products/".$oldImage);
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
            }

            // Upload new images
           
                foreach ($request->file('images') as $image) {
                    if ($image->isValid()) { // التحقق من صحة الملف
                        $imagePath = $image->path();
                        if (file_exists($imagePath)) {
                            $imageHash = md5_file($imagePath);
                            $image->move(public_path("products/"), $imageHash);

                            $imageHashes[] = $imageHash;
                        } else {
                            // التعامل مع حالة عدم وجود الملف
                            throw new \Exception("Image file not found.");
                        }
                    } else {
                        // التعامل مع الملفات غير الصالحة
                        throw new \Exception("Invalid image file.");
                    }
                }    
            

        
                $product->update([
                    'images' => implode(',', $imageHashes), // استخدام implode لتحويل المصفوفة إلى سلسلة نصية مفصولة بفواصل
                ]);
                      
        }
    
            $productData = [
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'store_id' => $request->store_id,
            ];
            

            // التحقق من أن القيم غير فارغة وهي أرقام صحيحة قبل إضافتها للتحديث
            if (is_numeric($request->category_id)) {
                $productData['category_id'] = $request->category_id;
            }
            
            if (is_numeric($request->branch_id)) {
                $productData['branch_id'] = $request->branch_id;
            }
            
            
            
            $product->update($productData);
            
            
            
            

            $changes = $product->getChanges();
    
            if (empty($changes)) {
                return response()->json(['message' => 'لم يتم حدوث اي تعديل ',
                'status'=> 200 ,
                'images'=>$request->images,

            ]);
            
           } 
    
            return response()->json([
                'status' => 200,
                'images'=>$request->images,
                'message' => 'Product updated successfully',
            ]);
    
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 401,
                'message' => $e->getMessage(),
            ]);
        } catch (\Exception $ex) {
            return response()->json([
                'status' => 500,
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
