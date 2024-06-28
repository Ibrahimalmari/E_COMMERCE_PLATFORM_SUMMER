<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\DeliveryMan;
use App\Models\Product;
use App\Models\SellerMan;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\File;

class DeliveryController extends Controller
{
    public function index()
{
    // الحصول على جميع موظفي التوصيل
    $deliveryMen = DeliveryMan::all();

    // التحقق مما إذا كان هناك موظفين توصيل متاحين
    if ($deliveryMen->isEmpty()) {
        return response()->json([
            'status' => 404,
            'message' => 'No delivery men found',
        ]);
    }

    // إرجاع البيانات بنجاح
    return response()->json([
        'status' => 200,
        'deliveryMen' => $deliveryMen,
    ]);
}
    // الدالة لتسجيل الدخول
    public function login(Request $request)
    {
        // تحقق من صحة البيانات المدخلة
        $validatedData = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validatedData->fails()) {
            return response()->json([
                'validation_error' => $validatedData->messages(),
            ]);
        }

        // البحث عن موظف التوصيل باستخدام البريد الإلكتروني
        $deliveryMan = DeliveryMan::where('email', $request->email)->first();

        // التحقق مما إذا كانت بيانات الدخول صحيحة
        if (!$deliveryMan || !Hash::check($request->password, $deliveryMan->password)) {
            return response()->json([
                'status' => 401,
                'message' => 'Invalid Credentials',
            ]);
        }

        // إنشاء توكن لموظف التوصيل
        $token = $deliveryMan->createToken($deliveryMan->email.'_Token')->plainTextToken;

        return response()->json([
            'status' => 200,
            'user' => $deliveryMan->name,
            'role' => $deliveryMan->role_id,
            'id' => $deliveryMan->id,
            'token' => $token,
            'message' => 'Logged in Successfully',
        ]);
    }

  
    public function register(Request $request)
    {
        
            // التحقق من صحة البيانات المدخلة
            $validatedData = Validator::make($request->all(), [
                'name' => 'required|regex:/^[\p{Arabic}]+\s[\p{Arabic}]+$/u',
                'email' => 'required|email|unique:delivery_men|unique:seller_men|unique:admins',
                'password' => 'required|min:3',
                'phone' => 'required|regex:/^\d{10}$/|unique:delivery_men|unique:seller_men|unique:admins|unique:stores',
                'address' => 'required',
                'joining_date' => 'required|date',
                'PhotoOfPersonalID' => 'image|mimes:jpeg,png,jpg,gif|max:2048|unique:delivery_men|unique:seller_men',
                'vehicle_image' => 'image|mimes:jpeg,png,jpg,gif|max:2048|unique:delivery_men',
                'license_image' => 'image|mimes:jpeg,png,jpg,gif|max:2048|unique:delivery_men',
                'vehicle_number' => 'nullable|string',
                'vehicle_type' => 'nullable|string',
                'NationalNumber' => 'required|digits:11|unique:seller_men|unique:delivery_men',
                'role_id' => 'required|exists:roles,id',
            ]);
    
            if ($validatedData->fails()) {
                return response()->json([
                    'status' => 401,
                    'message' => $validatedData->errors()->first(),
                ]);
            }
    
            // إعداد البيانات
            $data = $request->all();
    
            // التعامل مع تحميل الصور
            if ($request->hasFile('PhotoOfPersonalID')) {
                
                        // Check if the image hash already exists in any of the specified tables and fields
                        $image = $request->file('PhotoOfPersonalID');
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
                $request->file('PhotoOfPersonalID')->move(public_path('delivery_worker'), $imageHash);
                $data['PhotoOfPersonalID'] =  $imageHash;
            }
            if ($request->hasFile('vehicle_image')) {
                  // Check if the image hash already exists in any of the specified tables and fields
                  $image = $request->file('vehicle_image');
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
                $request->file('vehicle_image')->move(public_path('delivery_worker'), $imageHash);
                $data['vehicle_image'] =  $imageHash;
            }
            if ($request->hasFile('license_image')) {
                      // Check if the image hash already exists in any of the specified tables and fields
                      $image = $request->file('license_image');
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
                    $request->file('license_image')->move(public_path('delivery_worker'), $imageHash);
                $data['license_image'] =  $imageHash;
            }
    
            // إنشاء موظف التوصيل
            $deliveryMan = DeliveryMan::create($data);
    
            // إنشاء توكن لموظف التوصيل
            $token = $deliveryMan->createToken($deliveryMan->email . '_Token')->plainTextToken;
    
            return response()->json([
                'status' => 200,
                'user' => $deliveryMan->name,
                'role' => $deliveryMan->role_id,
                'id' => $deliveryMan->id,
                'token' => $token,
                'message' => 'تم عملية التسجيل بنجاح',
            ]);
        } 
    
    

    public function update(Request $request, $id)
    {
        try {
            // البحث عن موظف التوصيل
            $deliveryMan = DeliveryMan::findOrFail($id);
    
            // التحقق من صحة البيانات المدخلة
            $validatedData = Validator::make($request->all(), [
                'name' => 'required|regex:/^[\p{Arabic}]+\s[\p{Arabic}]+$/u',
                'email' => 'required|email|unique:delivery_men,email,'.$id.'|unique:seller_men|unique:admins',
                'phone' => 'required|regex:/^\d{10}$/|unique:delivery_men,phone,'.$id.'|unique:seller_men|unique:admins|unique:stores',
                'address' => 'required',
                'joining_date' => 'required|date',
                'vehicle_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048|unique:delivery_men',
                'license_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048|unique:delivery_men',
                'vehicle_number' => 'nullable|string',
                'vehicle_type' => 'nullable|string',
                'NationalNumber' => 'required|digits:11|unique:seller_men|unique:delivery_men,NationalNumber,'.$id,
                'role_id' => 'required|exists:roles,id',
            ]);
    
            if ($validatedData->fails()) {
                return response()->json([
                    'status' => 401,
                    'message' => $validatedData->errors()->first(),
                ]);
            }
    
    
            // التعامل مع تحميل الصور
            if ($request->hasFile('PhotoOfPersonalID')) {
                 // Check if the image hash already exists in any of the specified tables and fields
                 $image = $request->file('PhotoOfPersonalID');
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
                $request->file('PhotoOfPersonalID')->move(public_path('delivery_worker'), $imageHash);
                $data['PhotoOfPersonalID'] =  $imageHash;
                
    
                if (!empty($deliveryMan->PhotoOfPersonalID)) {
                    unlink(public_path('delivery_worker/' . $deliveryMan->PhotoOfPersonalID));
                }
                $deliveryMan->update([
                    'PhotoOfPersonalID' => $imageHash,
                ]);;
            }
    
            if ($request->hasFile('vehicle_image')) {
                 // Check if the image hash already exists in any of the specified tables and fields
                 $image = $request->file('vehicle_image');
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
                $request->file('vehicle_image')->move(public_path('delivery_worker'), $imageHash);
                $data['vehicle_image'] =  $imageHash;
    
                if (!empty($deliveryMan->vehicle_image)) {
                    unlink(public_path('delivery_worker/' . $deliveryMan->vehicle_image));
                }
                $deliveryMan->update([
                    'vehicle_image' => $imageHash,
                ]);;

            }
    
            if ($request->hasFile('license_image')) {
               // Check if the image hash already exists in any of the specified tables and fields
               $image = $request->file('license_image');
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
              $request->file('license_image')->move(public_path('delivery_worker'), $imageHash);
                $data['license_image'] =  $imageHash;
                
                if (!empty($deliveryMan->license_image)) {
                    unlink(public_path('delivery_worker/' . $deliveryMan->license_image));
                }
                $deliveryMan->update([
                    'license_image' => $imageHash ,
                ]);;
            }
    
            $deliveryMan->update([
                'name' => $request->name,
                'email' => $request->email,
                'address' => $request->address,
                'joining_date' => $request->joining_date,
                'phone' => $request->phone,
                'NationalNumber' => $request->NationalNumber,
                'role_id' => $request->role_id,
                'vehicle_number' => $request->vehicle_number,
                'vehicle_type' => $request->vehicle_type,
            ]);
          
            
    
            $changes = $deliveryMan->getChanges();
    
            if (empty($changes)) {
                return response()->json(['message' => 'لم يتم حدوث اي تعديل '], 200);
            }
    
            return response()->json([
                'status' => 200,
                'message' => 'تم تحديث موظف التوصيل بنجاح',
            ]);
        }  catch (ValidationException $e) {
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
            $deliveryMan = DeliveryMan::findOrFail($id);
            
            return response()->json([
                'status' => 200,
                'deliveryMan' => $deliveryMan,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'An unexpected error occurred. Please try again later.',
            ]);
        }
    }
    

    // الدالة لعرض بيانات موظف التوصيل
    public function show($id)
    {
        // البحث عن موظف التوصيل
        $deliveryMan = DeliveryMan::find($id);

        if (!$deliveryMan) {
            return response()->json([
                'status' => 401,
                'message' => 'لا يوجد اي عامل توصيل',
            ]);
        }

        return response()->json([
            'status' => 200,
            'delivery_man' => $deliveryMan,
        ]);
    }
    public function destroy($id)
    {
        try {
            // البحث عن موظف التوصيل
            $deliveryMan = DeliveryMan::findOrFail($id);
    
            // حذف صور موظف التوصيل إذا كانت موجودة
            $photoPath = public_path('delivery_worker/' . $deliveryMan->PhotoOfPersonalID);
            if (file_exists($photoPath) && is_file($photoPath)) {
                unlink($photoPath);
            }
    
            $vehicleImagePath = public_path('delivery_worker/' . $deliveryMan->vehicle_image);
            if (file_exists($vehicleImagePath) && is_file($vehicleImagePath)) {
                unlink($vehicleImagePath);
            }
    
            $licenseImagePath = public_path('delivery_worker/' . $deliveryMan->license_image);
            if (file_exists($licenseImagePath) && is_file($licenseImagePath)) {
                unlink($licenseImagePath);
            }
    
            // حذف موظف التوصيل
            $deliveryMan->delete();
    
            return response()->json([
                'status' => 200,
                'message' => 'تم عملية الحذف بتجاح',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'حدث خطأ غير متوقع ارجو المحاولة لاحقا',
            ]);
        }
    }
    
    
    
}
