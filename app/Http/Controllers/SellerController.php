<?php

namespace App\Http\Controllers;

use App\Events\VerifyRegisterSeller;
use App\Mail\ResetPassword;
use App\Models\Category;
use App\Models\DeliveryMan;
use App\Models\Notification;
use App\Models\Product;
use App\Models\SellerMan;
use App\Models\Store;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SellerController extends Controller

{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index()
    {
         $seller = SellerMan::all();
         return response()->json([
            'status' => 200, 
             'seller' =>$seller,
            'message'=>'Registered Successfully',
        ]); 
    
    }

    public function getSellerNotifications($id)
    {
        // جلب الإشعارات الخاصة بالبائع بناءً على معرف المستخدم (ID)
        $notifications = Notification::where('notifiable_id', $id)
        ->where('notifiable_type', 'App\Models\SellerMan')
        ->get();

        return response()->json([
            'status' => 200,
            'notifications' => $notifications
        ]);
    }


    
    public function Login(Request $request)
    {
        // Validate the incoming request data
        $validatedData = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);
    
        // Return validation errors if validation fails
        if ($validatedData->fails()) {
            return response()->json([
                'status' => 400,
                'validation_error' => $validatedData->messages(),
            ]);
        }
    
        // Find the seller by email
        $SellerMan = SellerMan::where('email', $request->email)->first();
    
        // Check if the seller exists and the password is correct
        if (!$SellerMan || !Hash::check($request->password, $SellerMan->password)) {
            return response()->json([
                'status' => 401,
                'message' => 'Invalid Credentials',
            ]);
        }
    
        // Check if the seller's account is active
        // Assuming 'status' column is integer and 1 represents 'Active'
        if ($SellerMan->status != 1) {
            return response()->json([
                'status' => 403,
                'message' => 'Your account is not activated. Please check your email to activate your account.',
            ]);
        }
    
        // Generate an authentication token for the seller
        $token = $SellerMan->createToken($SellerMan->email . '_Token')->plainTextToken;
    
        // Return the authenticated seller's information and token
        return response()->json([
            'status' => 200,
            'user' => $SellerMan->name,
            'role' => $SellerMan->role_id,
            'id' => $SellerMan->id,
            'token' => $token,
            'message' => 'Login Successfully',
        ]);
    }
    
    
    
    public function Logout($id){
        $seller = SellerMan::find($id);
      
       $seller->tokens()->delete();
        
        return response()->json([
            'status' => 200,
            'message' => 'Logged Out Successfully',
        ]);
    }
      
   
    public function sendPasswordResetEmail(Request $request)
    {
        $seller = SellerMan::where('email', $request->email)->first();
    
        if (!$seller) {
            return response()->json([
                'status' => 'error',
                'message' => 'We could not find a user with that email address.',
            ], 404);
        }
        else{
            $token = Password::createToken($seller);
            $seller->remember_token = $token;
            $seller->save();        

        if( $seller)
        Mail::to($seller->email)->send(new ResetPassword($token));
        return response()->json([
            'status' => 'success',
            'token' =>  $token,
            'message' => 'We have emailed your password reset link!',
        ], 200);
       }
    }

    public function updatePassword(Request $request)
    {
        // التحقق من صحة الطلب
        $request->validate([
            'password' => 'required|min:8',
            'token' => 'required',
        ]);

        $seller = SellerMan::where('remember_token', $request->token)->first();

        if (!$seller) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid token.',
            ], 404);
        }

        // تحديث كلمة المرور الجديدة
        $seller->password = Hash::make($request->password);
        $seller->remember_token = null; 
        $seller->save();

        session()->flash('success', 'Password updated successfully.');

    }


    public function verifyAccountseller($sellerId)
    {
        // التحقق من وجود البائع
        $seller = SellerMan::find($sellerId);
    
        if (!$seller) {
            return view('emails/errorverifyaccount', ['message' => 'Seller not found.']);
        }
        
        // تحديث حالة البائع
        $seller->email_verified_at = now();
        $seller->Status	= true;
        $seller->save();
    
        return view('emails.verifyaccountseller');
    
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function Register(Request $request)
    {
    
    try {
        $validatedData = Validator::make($request->all(), [
            'name' => 'required|regex:/^[\p{Arabic}]+\s[\p{Arabic}]+$/u',
            'email' => 'unique:seller_men|unique:admins|unique:delivery_men',
            'address' => 'required',
            'gender' => 'required',
            'phone' => 'required|regex:/^\d{10}$/|unique:stores|unique:seller_men|unique:admins|unique:delivery_men',
            'DateOfBirth' => 'required|date',
            'NationalNumber' => 'required|digits:11|unique:seller_men|unique:delivery_men',
            'password' => 'required|min:3',
            'role_id' => 'required',
            'PhotoOfPersonalID' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
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

    
    
        // If the image doesn't exist in the database, proceed with registration
        $seller = SellerMan::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'address' => $request->address,
            'gender' => $request->gender,
            'phone' => $request->phone,
            'PhotoOfPersonalID' => $imageHash, // Save the hash of the image
            'DateOfBirth' => $request->DateOfBirth,
            'NationalNumber' => $request->NationalNumber,
            'role_id' => $request->role_id,
        ]);
    
        if ($seller) {
            // Send verification email
            event(new VerifyRegisterSeller($seller));
            $request->file('image')->move(public_path('seller_men'), $imageHash);      
          }
    
        $token = $seller->createToken($seller->email . '_Token')->plainTextToken;
    
        return response()->json([
            'status' => 200,
            'user' => $seller->name,
            'role' => $seller->role_id,
            'id' => $seller->id,
            'token' => $token,
            'message' => 'Registered Successfully',
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
       return 1;   
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        

    $seller = SellerMan::find($id);

    
    if($seller){
        return response()->json([
            'status' => 200, 
            'seller' =>$seller
        ]);
    }

    else{
        return response()->json([
            'status' => 404, 
            'message' =>'No Seller Id Found'
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
    $validatedData = Validator::make($request->all(), [
        'name' => 'required|regex:/^[\p{Arabic}]+\s[\p{Arabic}]+$/u',
        'email' => 'unique:seller_men,email,'.$id.'|unique:admins|unique:delivery_men',
        'address' => 'required',
        'gender' => 'required',
        'phone' => 'required|regex:/^\d{10}$/|unique:stores|unique:seller_men,phone,'.$id.'|unique:admins|unique:delivery_men',
        'DateOfBirth' => 'required|date',
        'NationalNumber' => 'required|digits:11|unique:seller_men,NationalNumber,'.$id.'|unique:delivery_men',
        'role_id' => 'required',
        'PhotoOfPersonalID' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
    ]);
    
    if ($validatedData->fails()) {
        return response()->json([
            'status' => 401,
            'message' => $validatedData->errors()->first(),
        ]);
    }
    
    $seller = SellerMan::find($id);
    
    if (!$seller) {
        return response()->json(['message' => 'Record not found'], 401);
    }
    
            // تحديث الصورة بناءً على الهاش
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
           if ($seller->PhotoOfPersonalID) {
            File::delete(public_path('seller_men/' . $seller->PhotoOfPersonalID));
         }   
        }


        // تحديث الهاش في قاعدة البيانات
        $seller->update([
            'PhotoOfPersonalID' => $imageHash,
        ]);
        $request->file('image')->move(public_path('seller_men'), $imageHash);

    }
    
    
    $seller->update([
        'name' => $request->name,
        'email' => $request->email,
        'address' => $request->address,
        'gender' => $request->gender,
        'phone' => $request->phone,
        'DateOfBirth' => $request->DateOfBirth,
        'NationalNumber' => $request->NationalNumber,
        'role_id' => $request->role_id,
    ]);

    $changes = $seller->getChanges();
    
    if (empty($changes)) {
        return response()->json(['message' => 'No modification has been made'], 200);
    }

    return response()->json([
        'message' => 'Record updated successfully.',
        'seller' => $seller
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

public function updatesellerprofile(Request $request, $id)
{

    try {
    $validatedData = Validator::make($request->all(), [
        'name' => 'required|regex:/^[\p{Arabic}]+\s[\p{Arabic}]+$/u',
        'email' => 'unique:seller_men,email,'.$id.'|unique:admins|unique:delivery_men',
        'address' => 'required',
        'gender' => 'required',
        'phone' => 'required|regex:/^\d{10}$/|unique:stores|unique:seller_men,phone,'.$id.'|unique:admins|unique:delivery_men',
        'DateOfBirth' => 'required|date',
        'NationalNumber' => 'required|digits:11|unique:seller_men,NationalNumber,'.$id.'|unique:delivery_men',
        'PhotoOfPersonalID' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
    ]);
    
    if ($validatedData->fails()) {
        return response()->json([
            'status' => 401,
            'message' => $validatedData->errors()->first(),
        ]);
    }
    
    $seller = SellerMan::find($id);
    
    if (!$seller) {
        return response()->json(['message' => 'Record not found'], 401);
    }
    
            // تحديث الصورة بناءً على الهاش
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
           if ($seller->PhotoOfPersonalID) {
            File::delete(public_path('seller_men/' . $seller->PhotoOfPersonalID));
         }   
        }


        // تحديث الهاش في قاعدة البيانات
        $seller->update([
            'PhotoOfPersonalID' => $imageHash,
        ]);
        $request->file('image')->move(public_path('seller_men'), $imageHash);

    }
    
    
    $seller->update([
        'name' => $request->name,
        'email' => $request->email,
        'address' => $request->address,
        'gender' => $request->gender,
        'phone' => $request->phone,
        'DateOfBirth' => $request->DateOfBirth,
        'NationalNumber' => $request->NationalNumber,
    ]);

    $changes = $seller->getChanges();
    
    if (empty($changes)) {
        return response()->json(['message' => 'No modification has been made'], 200);
    }

    return response()->json([
        'message' => 'Record updated successfully.',
        'seller' => $seller
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

public function sellerchangePassword(Request $request, $id)
{
    try {
        $seller = SellerMan::find($id);

        // Check if seller exists
        if (!$seller) {
            return response()->json(['message' => 'Seller not found','status' => 404 ,]);
        }

        // Check if current password matches
        if (!Hash::check($request->currentPassword, $seller->password)) {
            return response()->json(['message' => 'Current password is incorrect', 'status' => 401 , ]);
        }

        // Check if new password and confirm password match
        if ($request->newPassword !== $request->confirmNewPassword) {
            return response()->json(['message' => 'New password and confirm password do not match' ,'status' => 401 ,]);
        }

        // Check if newPassword and confirmNewPassword are not empty
        if (empty($request->newPassword) || empty($request->confirmNewPassword)) {
            return response()->json(['message' => 'Please fill in all fields' ,'status' => 401 ,]);
        }

        // Update seller's password
        $seller->password = Hash::make($request->newPassword);
        $seller->save();

        return response()->json(['message' => 'Password changed successfully'], 200);
    }catch (ValidationException $e) {
            return response()->json([
                'status' => 401 ,
                'message' => $e->getMessage(),
            ]);
    } catch (\Exception $e) {
        return response()->json(['message' => $e->getMessage()], 500);
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
        $seller = SellerMan::findOrFail($id);
         // التحقق من وجود ال hash للصورة في قاعدة البيانات
    if ($seller->PhotoOfPersonalID) {
        // البحث عن الصورة باستخدام ال hash في قاعدة البيانات
        $existingImage = SellerMan::where('PhotoOfPersonalID', $seller->PhotoOfPersonalID)->first();
        
        if ($existingImage) {
            // حذف الصورة إذا تم العثور عليها
            $filePath = public_path('seller_men/' . $existingImage->PhotoOfPersonalID);
            if (file_exists($filePath) && is_file($filePath)) {
                unlink($filePath);
            }
        }
            // حذف الصورة من قاعدة البيانات أيضًا
            $seller->tokens()->delete();
            $seller->delete();
            return response()->json([
                'status' => 200,
                'message' => 'Seller has been deleted.'
            ]);
        } else {
            return response()->json([
                'status' => 500,
                'message' => 'An unexpected error occurred. Please try again later.'
            ]);
        }
    }
    
    
    
    

}