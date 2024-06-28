<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Store;
use App\Notifications\GeneralNotification;
use Illuminate\Http\Request;
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
    $store = Store::where('seller_id', $id)->first();
    
    if ($store) {
        $store_id = $store->id;
        
        // احصل على الفئة (category) المرتبطة بنوع المتجر
        $category = Category::where('store_id', $store_id)->first();

        // التحقق من وجود الفئة قبل استخدامها
        if ($category) {
            // احصل على الفروع (branches) المرتبطة بالفئة
            $branches = $category->branch;

            return response()->json([
                'status' => 200, 
                'branches' => $category->branch,
                'message' => 'Successfully',
            ]);
        } else {
            return response()->json([
                'status' => 404, 
                'message' => 'No category found for this store',
            ]);
        }
    } else {
        return response()->json([
            'status' => 500, 
            'message' => 'هناك مشكل ما',
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
    
            $branch = Branch::create([
                'name' => $request->name,
                'category_id' => $request->category_id,
                'created_by' => $id,
            ]);


              // إعداد البيانات للإشعار
        $data = [
            'branch_name' => $request->name,
            'branch_category_id' => $request->category_id,
            'branch_created_by' => $id,
        ];

        $NotificationBranch = Admin::all(); 


         // إرسال الإشعار
         Notification::send($NotificationBranch, new GeneralNotification('branch', $data));
    
            return response()->json([
                'status' => 200,
                'user' => $id,
                'branch' => $branch,
                'message' => 'Branch added successfully',
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
    
            $branch->update([
                'name' => $request->name,
                'category_id' => $request->category_id,
            ]);

            $changes = $branch->getChanges();
    
            if (empty($changes)) {
                return response()->json(['message' => 'لم يتم حدوث اي تعديل ',
                'status'=> 200 
            ]);
            
           } 
    
            return response()->json([
                'status' => 200,
                'message' => 'Branch updated successfully',
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
