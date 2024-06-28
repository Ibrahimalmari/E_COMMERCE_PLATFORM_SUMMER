<?php

namespace App\Http\Controllers;

use App\Models\Discount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class DiscountController extends Controller
{

  
       // عرض قائمة الخصومات
       public function index()
       {
           $discounts = Discount::all();

            if ($discounts->isEmpty()) {
                return response()->json([
                    'status' => 404,
                    'message' => 'لا يوجد اي كود خصم',
                ]);
            }

            // إرجاع البيانات بنجاح
            return response()->json([
                'status' => 200,
                'discounts' => $discounts,
            ]);       
}
    
    
public function store(Request $request)
{
    try {
        // التحقق من البيانات المرسلة
        $validatedData = Validator::make($request->all() ,[
            'name' => 'required|regex:/^[\p{Arabic}\s]+$/u',
            'code' => 'required|unique:discounts',
            'percentage' => 'nullable|numeric',
            'value' => 'nullable|numeric',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'conditions' => 'nullable|regex:/^[\p{Arabic}\s]+$/u',
            'store_id' => 'required',

        ]);
        
        if ($validatedData->fails()) {
            return response()->json([
                'validation_error' => $validatedData->messages(),
            ]);
        }
        $data = $request->all();

        // إنشاء خصم جديد
        $Discount = Discount::create($data);

        return response()->json([
            'status' => 200,
            'Discount' => $Discount,
            'message' => 'تم انشاء الخصم بنجاح',
        ]);
    } catch (\Exception $e) {
        // إرسال رسالة الخطأ
        return response()->json([
            'status' => 500,
            'message' => 'حدث مشكلة اثناء عملية التسجيل يرجى المحاولة لاحقا',
        ]);
    }
}

    
      // عرض النموذج لتعديل الخصم
        public function edit($id)
        {
            $discount = Discount::find($id);

            if (!$discount) {
                return response()->json([
                    'status' => 404,
                    'message' => 'لم يتم العثور عليه',
                ]);
            }

            return response()->json([
                'status' => 200,
                'discount' => $discount,
            ]);
        }
    
       public function update(Request $request, $id) 
       {
           // البحث عن الخصم
           $discount = Discount::find($id);
       
           if (!$discount) {
               return response()->json([
                   'status' => 404,
                   'message' => 'Discount not found',
               ]);
           }
       
           // قم بالتحقق من البيانات المرسلة
           $validatedData = $request->validate([
               'name' => 'required|regex:/^[\p{Arabic}\s]+$/u',
               'code' => 'required|unique:discounts,code,' .$id,
               'percentage' => 'nullable|numeric',
               'value' => 'nullable|numeric',
               'start_date' => 'required|date',
               'end_date' => 'required|date',
               'conditions' => 'nullable|regex:/^[\p{Arabic}\s]+$/u',
               'store_id' => 'required',
           ]);
       
           // التحقق مما إذا تم تعديل أي بيانات
           $isUpdated = false;
           foreach ($validatedData as $key => $value) {
               if ($discount->$key != $value) {
                   $isUpdated = true;
                   break;
               }
           }
       
           // إذا لم يتم تعديل أي بيانات
           if (!$isUpdated) {
               return response()->json([
                   'status' => 200,
                   'message' => 'لم يتم اجراء اي تعديل',
               ]);
           }
       
           // قم بتحديث بيانات الخصم
           $discount->update($validatedData);
       
           return response()->json([
               'status' => 200,
               'message' => 'تم تحديث البيانات بنجاح',
           ]);
       }
    
      // حذف الخصم من قاعدة البيانات
public function destroy($id)
{

    $discount = Discount::findOrFail($id);

    if ($discount->delete()) {
        return response()->json([
            'status' => 200,
            'message' => 'تم عملية حذف الخصم بنجاح',
        ]);
    }

    return response()->json([
        'status' => 500,
        'message' => 'فشل عملية حذف الخصم',
    ]);
}
    
    
    }
    

