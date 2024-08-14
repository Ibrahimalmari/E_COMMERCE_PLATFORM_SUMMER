<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\DeliveryMan;
use App\Models\Product;
use App\Models\SellerMan;
use App\Models\Store;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class StoreController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index_admin()
    {
        $store = Store::all();
        return response()->json([
           'status' => 200, 
            'store' =>$store,
           'message'=>' Successfully',
       ]);
    }

    public function index_seller($id)
    {
        $store = Store::where("seller_id", $id)->get()->toArray();

        return response()->json([
           'status' => 200, 
             'id'=>$id,
            'store' =>$store,
           'message'=>' Successfully',
       ]);
    }

    public function DisplayStoreToCustomer($storeId)
    {
        try {
            // استرداد بيانات المتجر من قاعدة البيانات أو من خلال أي خدمة
            $store = Store::find($storeId); // توقع أنه يمكنك استخدام مثل هذا النموذج
            
            if (!$store) {
                return response()->json(['error' => 'Store not found'], 404);
            }

            return response()->json(['store' => $store]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch store details', 'message' => $e->getMessage()], 500);
        }
    }

    
    public function getStoreDetails($store_id)
    {
        try {
            // جلب بيانات المتجر
            $store = Store::findOrFail($store_id);
    
            // جلب الفئات والأفراع والمنتجات المرتبطة بالمتجر
            $categories = Category::where('store_id', $store_id)
                ->with(['branches.products']) // تأكد من استخدام 'products' بدلاً من 'product'
                ->get();
    
            return response()->json([
                'store' => $store,
                'categories' => $categories,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching store details: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch store details.'], 500);
        }
    }
    

    public function getStoreAddress($store_id)
    {
        try {
            // جلب بيانات المتجر
            $store = Store::findOrFail($store_id);
    
            return response()->json([
                'store' => $store,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching store details: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch store details.'], 500);
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
    public function store(Request $request, $id)
    {
       try{
        $validatedData = Validator::make($request->all(), [
            'name' => 'required|unique:stores|regex:/^[\p{Arabic}\s]+$/u',
            'address' => 'required',
            'description' => 'required||regex:/^[\p{Arabic}\s]+$/u',
            'type' => 'required|regex:/^[\p{Arabic}\s]+$/u',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'phone' => 'required|regex:/^\d{10}$/|unique:stores|unique:seller_men|unique:admins|unique:delivery_men',
            'openTime' => 'required',
            'closeTime'=> 'required',
            'seller_id' => 'required',
            'coverPhoto' => 'image|mimes:jpeg,png,jpg,gif|max:2048|unique:stores|unique:seller_men|unique:delivery_men|unique:products',

        ]);
    
        if ($validatedData->fails()) {
            throw new ValidationException($validatedData);
        }
              // Check if the image hash already exists in any of the specified tables and fields
        $image = $request->file('image');
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
    
            
            // إنشاء سجل المتجر مع البيانات المرسلة
            $store = Store::create([
                'name' => $request->name,
                'address' => $request->address,
                'description' => $request->description,
                'type' => $request->type,
                'phone' => $request->phone,
                'coverPhoto' => $imageHash,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'openTime' => $request->openTime,
                'closeTime' => $request->closeTime,
                'seller_id' => $request->seller_id,
                'created_by' => $id,
            ]);
            $image->move(public_path('stores'), $imageHash);

            // إرسال رد ناجح إذا تمت العملية بنجاح
            return response()->json([
                'status' => 200,
                'created_by' => $id,
                'store' => $store,
                'message' => 'The store has been added successfully.',
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
        $store = Store::find($id);

    
    if($store){
        return response()->json([
            'status' => 200, 
            'store' =>$store
        ]);
    }

    else{
        return response()->json([
            'status' => 404, 
            'message' =>'No store Id Found'
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
        try{
            $validatedData = Validator::make($request->all(), [
                'name' => 'required|regex:/^[\p{Arabic}\s]+$/u|unique:stores,name,'.$id,
                'address' => 'required',
                'description' => 'required|regex:/^[\p{Arabic}\s]+$/u',
                'type' => 'required|regex:/^[\p{Arabic}\s]+$/u',
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
                'phone' => 'required|regex:/^\d{10}$/|unique:stores,phone,'.$id.'|unique:seller_men|unique:admins|unique:delivery_men',
                'openTime' => 'required',
                'closeTime'=> 'required',
                'seller_id' => 'required',
                'coverPhoto' => 'image|mimes:jpeg,png,jpg,gif|max:2048|unique:stores,coverPhoto,'.$id.'|unique:seller_men,PhotoOfPersonalID,'.$id.'|unique:delivery_men,profile_picture,'.$id.'|unique:delivery_men,vehicle_image,'.$id.'|unique:delivery_men,license_image,'.$id.'|unique:products,images,'.$id,

            ]);
        
            if ($validatedData->fails()) {
                return response()->json([
                    'status' => 401,
                    'message' => $validatedData->errors()->first(),
                ]);
            } 


                $store = Store::find($id);
        
                if (!$store) {
                    return response()->json(['message' => 'Record not found'], 404);
                }
        
        
       // التحقق من وجود الصورة
       if ($request->hasFile('image')) {
       
        
              // Check if the image hash already exists in any of the specified tables and fields
              $image = $request->file('image');
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
                   // حذف الصورة القديمة إذا كانت موجودة
                    if ($store->coverPhoto) {
                        File::delete(public_path('stores/' . $store->coverPhoto));
                    }
              }
        
            $store->update([
                'coverPhoto' => $imageHash ,
          ]);
          $image->move(public_path('stores'), $imageHash);
         }
        
        // تحديث السجل بما في ذلك حقل الصورة إذا كانت هناك صورة جديدة
        $store->update([
            'name' => $request->name,
            'address'=> $request->address,
            'description'=> $request->description,
            'type'=> $request->type,
            'phone'=> $request->phone,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'openTime'=> $request->openTime,
            'closeTime' => $request->closeTime,
            'seller_id'=> $request->seller_id,
        ]);
        
        $changes = $store->getChanges();

        if (empty($changes)) {
                return response()->json(['message' => 'No modification has been made' ], 200);
            }
        
        
        return response()->json([
            'message' => 'Record updated successfully.',
            'store' => $store
        ]);
     }   catch (ValidationException $e) {
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
      $store = Store::findOrFail($id);
    
     // التحقق من وجود ال hash للصورة في قاعدة البيانات
     if ($store->coverPhoto) {
        // البحث عن الصورة باستخدام ال hash في قاعدة البيانات
        $existingImage = Store::where('coverPhoto', $store->coverPhoto)->first();
        
        if ($existingImage) {
            // حذف الصورة إذا تم العثور عليها
            $filePath = public_path('stores/' . $existingImage->coverPhoto);
            if (file_exists($filePath) && is_file($filePath)) {
                unlink($filePath);
            }
            $store->delete();
            return response()->json([
                'status' => 200,
                'message' => 'store has been deleted.'
            ]);
        }
        } else {
        return response()->json([
            'status' => 500,
            'message' => 'An unexpected error occurred. Please try again later.'
        ]);
    }
    
}

}


    
