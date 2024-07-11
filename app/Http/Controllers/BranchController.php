<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Category;
use App\Models\SellerMan;
use App\Models\Store;
use App\Notifications\GeneralNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;


class BranchController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index_admin()
    {
        $branch = Branch::all();
        return response()->json([
           'status' => 200, 
            'branch' =>$branch,
           'message'=>'Registered Successfully',
       ]);
    }
    public function index_seller($id)
    {
        // جلب بيانات البائع باستخدام معرف البائع
        $seller = SellerMan::find($id);
    
        if ($seller) {
            // جلب جميع المتاجر الخاصة بالبائع
            $stores = $seller->store; // تأكد أن هناك علاقة بين SellerMan و Store
    
            $branchesData = [];
            foreach ($stores as $store) {
                // جلب الفئات الخاصة بكل متجر
                $categories = Category::where('store_id', $store->id)->get();
    
                foreach ($categories as $category) {
                    // جلب الفروع المرتبطة بكل فئة
                    $branches = Branch::where('category_id', $category->id)->get();
    
                    foreach ($branches as $branch) {
                        $branchesData[] = $branch;
                    }
                }
            }
    
            return response()->json([
                'status' => 200,
                'branches' => $branchesData,
                'message' => 'Successfully retrieved seller, stores, categories, and branches',
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => 'Seller not found',
            ]);
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
        try {
            $validatedData = Validator::make($request->all(), [
                'name' => 'required|regex:/^[\p{Arabic}\s]+$/u|unique:branches,name',
                'category_id' => 'required',
            ]);
    
            if ($validatedData->fails()) {
                return response()->json([
                    'status' => 401,
                    'message' => $validatedData->errors()->first(),
                ]);
            }

        $category = Category::findOrFail($request->category_id);
        $categoryName = $category->name;
        
        $category = Category::findOrFail($request->category_id);
        $store = Store::with('seller')->findOrFail($category->store_id);
        $storeName = $store->name;
        $store_id = $store->id;
        $sellerName = $store->seller->name;

             // إعداد البيانات للإشعار
        $data = [
            'branch_name'=> $request->name,
            'branch_category_id' => $request->category_id,
            'branch_store_name' => $storeName,
            'branch_store_id' => $store_id,
            'branch_seller_name' => $sellerName, 
            'branch_category_name' => $categoryName, 
            'branch_seller_id' => $store->seller->id,

        ];


        $existingNotification = DB::table('notifications')
        ->where(function ($query) use ($data) {
            $query->where('data', json_encode(['type' => 'branch', 'data' => $data]));
        })
        ->first();

    if ($existingNotification) {
        return response()->json([
            'status' => 401,
            'message' => 'لا يمكن القيام بذلك لأنه قد تم بالفعل. يرجى الانتظار حتى تتم الموافقة على طلبك',
        ]);
    }

        $NotificationBranch = Admin::all(); 


         // إرسال الإشعار
         Notification::send($NotificationBranch, new GeneralNotification('branch', $data));
    
            return response()->json([
                'status' => 200,
                'user' => $id,
                'branch' => $data,
                'message' => 'تمت العملية بنجاح. يرجى الانتظار حتى الموافق على طلبك.',
            ]);
    
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 401 ,
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
            $branch = Branch::findOrFail($id);
    
            return response()->json([
                'status' => 200,
                'branch' => $branch,
            ]);
    
        }catch (ValidationException $e) {
            return response()->json([
                'status' => 401 ,
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
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $branch = Branch::findOrFail($id);
    
            // Validate incoming request data
            $validatedData = Validator::make($request->all(), [
                'name' => 'required|regex:/^[\p{Arabic}\s]+$/u|unique:branches,name,' . $id,
                'category_id' => 'required',
            ]);
    
            if ($validatedData->fails()) {
                return response()->json([
                    'status' => 401,
                    'message' => $validatedData->errors()->first(),
                ]);
            }
    
              // Load the branch with its relationships
        $branch->load('category'); // Load category relationship

        // Get store and seller information based on category
        $category = Category::findOrFail($request->category_id);
        $store = Store::with('seller')->findOrFail($category->store_id);
        $storeName = $store->name;
        $sellerName = $store->seller->name;
        $store_id = $store->id;


        // Prepare old data before update
        $oldData = [
            'branch_name' => $branch->name,
            'branch_category_id' => $branch->category_id,
            'branch_store_name' => optional($branch->category->store)->name,
            'branch_seller_name' => optional(optional($branch->category->store)->seller)->name,
            'branch_category_name' => optional($branch->category)->name,
            'branch_seller_id' => $store->seller->id,
            'branch_store_id' => $store_id,


        ];

        // Prepare new data after update
        $newData = [
            'branch_name' => $request->name,
            'branch_category_id' => $request->category_id,
            'branch_store_name' => $storeName,
            'branch_seller_name' => $sellerName,
            'branch_category_name' => optional($branch->category)->name,
            'branch_seller_id' => $store->seller->id,
            'branch_store_id' => $store_id,


        ];

    
           // Check if there are any changes
           $changes = array_diff_assoc($newData, $oldData);

           // If no changes, return message
           if (empty($changes)) {
               return response()->json([
                   'message' => 'لم يتم حدوث أي تعديل',
                   'status' => 200,
               ]);
           }
    
            // Prepare data for notification
            $data = [
                'old_data' => $oldData,
                'new_data' => $newData,
            ];
    
            $existingNotification = DB::table('notifications')
            ->where(function ($query) use ($data) {
                $query->where('data', json_encode(['type' => 'branch', 'data' => $data]));
            })
            ->first();
    
        if ($existingNotification) {
            return response()->json([
                'status' => 401,
                'message' => 'لا يمكن القيام بذلك لأنه قد تم بالفعل. يرجى الانتظار حتى تتم الموافقة على طلبك',
            ]);
        }
            // Send notification to admins
            $admins = Admin::all();
            Notification::send($admins, new GeneralNotification('branch', $data));
    
            return response()->json([
                'status' => 200,
                'message' => 'تمت العملية بنجاح. يرجى الانتظار حتى الموافقة على طلبك.',
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
            $branch = Branch::findOrFail($id);

            $branch->delete();
    
            return response()->json([
                'status' => 200,
                'message' => 'Branch deleted successfully',
            ]);
    
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 401 ,
                'message' => $e->getMessage(),
            ]);
        } catch (\Exception $ex) {
            return response()->json([
                'status' => 500,
                'message' => $ex->getMessage(),
            ]);
        }
    }
    
}
