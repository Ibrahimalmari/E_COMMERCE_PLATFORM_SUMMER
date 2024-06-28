<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Category;
use App\Models\DeliveryMan;
use App\Models\Product;
use App\Models\SellerMan;
use App\Models\Store;
use App\Notifications\CreateSellerCategory;
use App\Notifications\GeneralNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Notification;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index_admin()
    {
        $category = Category::all();
         return response()->json([
            'status' => 200, 
             'category' =>$category,
            'message'=>' Successfully',
        ]); 
        
    }

    public function index_seller($id)
    {
        $stores = Store::where('seller_id', $id)->get();
    
        if ($stores->isEmpty()) {
            return response()->json([
                'status' => 500,
                'message' => 'هناك مشكل ما',
            ]);
        }
    
        $categories = [];
        foreach ($stores as $store) {
            $store_id = $store->id;
            $store_categories = Category::where('store_id', $store_id)->get()->toArray();
            $categories = array_merge($categories, $store_categories);
        }
    
        return response()->json([
            'status' => 200,
            'categories' => $categories,
            'message' => 'Successfully',
        ]);
    }
    
    

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        

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
        'name' => 'required',
        'slug' => 'required',
        'description' => 'required',
        'store_id' => 'required',
    ]);

    if ($validatedData->fails()) {
        return response()->json([
            'status' => 401,
            'message' => $validatedData->errors()->first(),
        ]);
    }
   
    
         // Check if category with same name or slug or both and store_id exists
         $existingCategory = Category::where(function($query) use ($request) {
            $query->where('name', $request->name)
                  ->orWhere('slug', $request->slug);
        })
        ->where('store_id', $request->store_id)
        ->first();



        if ($existingCategory) {
            return response()->json([
                'status' => 401,
                'message' => 'Category with the same name already exists in this store.',
            ]);
        }

        // إنشاء الفئة مع الصورة إذا كانت متاحة، وإلا استخدام الصورة التمبلت
        $category = Category::create([
            'name' => $request->name,
            'slug' => $request->slug,
            'description' => $request->description,
            'store_id' => $request->store_id,
        ]);
        
        $seller = SellerMan::findOrFail($id);
                $sellerName = $seller->name;

        $store = Store::findOrFail($request->store_id);
                $storeName = $store->name;


         $tableName = (new Category)->getTable();
                        


        // Prepare data for notification
        $data = [
            'category_name' => $request->name,
            'category_slug' => $request->slug,
            'category_description' => $request->description,
            'category_created_by' => $sellerName,
            'category_store' => $storeName,
        ];

        // Send notification to admins
        $admins = Admin::all();
        Notification::send($admins, new GeneralNotification('category', $data));



        return response()->json([
            'status' => 200,
            'user' => $id,
            'sellerName'=> $sellerName,
            'storeName' =>$storeName,
            'message' => 'Category added successfully',
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
public function edit($id)
{
    try {
        $category = Category::findOrFail($id);
        
        return response()->json([
            'status' => 200,
            'category' => $category,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 500,
            'message' => 'An unexpected error occurred. Please try again later.',
        ]);
    }
}

public function update(Request $request, $id)
{
    $category = Category::findOrFail($id);


    try{

        $validatedData = Validator::make($request->all(), [
            'name' => 'required|regex:/^[\p{Arabic}\s]+$/u',
            'slug' => 'required|regex:/^[\p{Arabic}\s]+$/u',
            'description' => 'required|regex:/^[\p{Arabic}\s()-.,]+$/u',
            'store_id' => 'required',

        ]);
    
        if ($validatedData->fails()) {
            return response()->json([
                'status' => 401,
                'message' => $validatedData->errors()->first(),
            ]);
        }

   

    $category->update([
        'name' => $request->name,
        'slug' => $request->slug,
        'description' => $request->description,
        'store_id' => $request->store_id,
    ]);
    

    $changes = $category->getChanges();
    
    if (empty($changes)) {
        return response()->json(['message' => 'لم يتم حدوث اي تعديل ','status'=> 200 
    ]);
    
   } 

    return response()->json([
        'status' => 200,
        'message' => 'تم تحديث الفئة بنجاح',
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
        try {

            $category = Category::findOrFail($id);
         
            $category->delete();
    
            return response()->json([
                'status' => 200,
                'message' => 'Category deleted successfully',
            ]);
        } 
        catch (\Exception $ex) {
            return response()->json([
                'status' => 500,
                'message' => $ex->getMessage(),
            ]);
        }
    }
    
}
