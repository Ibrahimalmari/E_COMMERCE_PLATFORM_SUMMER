<?php

namespace App\Http\Controllers;

use App\Events\VerifyRegisterAdmin;
use App\Mail\ResetPasswordAdmin;
use App\Models\Address;
use App\Models\Admin;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Notification ;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Notifications\DatabaseNotification;

class AdminController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */


    public function Login(Request $request)
    {
        
        $validatedData = Validator::make($request->all(),[
            'email' => 'required',
            'password' => 'required',
        ]);
           
        if($validatedData->fails()){
            return response()->json([
                'validation_error'=>$validatedData->messages(),
            ]);
        }
        else{
        $admin =Admin::where('email',$request->email)->first();
     
        if (!$admin || !Hash::check($request->password, $admin->password)) {
            return response()->json([
                'status' => 401, 
                'message'=>'Invalid Credentials',
            ]);
        }
          
       else{
     
        $token= $admin->createToken($admin->email.'_Token')->plainTextToken;
    
        return response()->json([
            'status' => 200, 
            'user' =>$admin->name,
            'role' =>$admin->role_id,
            'id' =>$admin->id,
            'token'=>$token,
            'message'=>'Registered Successfully',
        ]);
       }
      }
    }

   
    public function showNotifications()
    {
        $notifications = DatabaseNotification::all();    

        return response()->json([
            'status' => 200,
            'data' => $notifications,
            'message' => 'Notifications retrieved successfully',
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('welcome');
    }

    public function sendPasswordResetEmail(Request $request)
    {
        $admin = Admin::where('email', $request->email)->first();
    
        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'We could not find a user with that email address.',
            ], 404);
        }
        else{
            $token = Password::createToken($admin);
            $admin->remember_token = $token;
            $admin->save();        

        if( $admin)
        Mail::to($admin->email)->send(new ResetPasswordAdmin($token));
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

        $admin = Admin::where('remember_token', $request->token)->first();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid token.',
            ], 404);
        }

        // تحديث كلمة المرور الجديدة
        $admin->password = Hash::make($request->password);
        $admin->remember_token = null; 
        $admin->save();

        session()->flash('success', 'Password updated successfully.');

    }

    public function verifyAccountadmin($adminId)
    {
        // التحقق من وجود البائع
        $admin = Admin::find($adminId);
    
        if (!$admin) {
            return view('emails.errorverifyaccount', ['message' => 'Admin not found.']);
        }
        
        // تحديث حالة البائع
        $admin->email_verified_at = now();
        $admin->save();
    
        return view('emails.verifyaccountadmin');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function Register(Request $request)   //Register
    {
        $validatedData = Validator::make($request->all(),[
            'name' => 'required|regex:/^[\p{Arabic}]+\s[\p{Arabic}]+$/u',
            'email' => 'required|email|unique:seller_men|unique:admins|unique:delivery_men',
            'phone' => 'required|regex:/^\d{10}$/|unique:stores|unique:seller_men|unique:admins|unique:delivery_men',
            'password' => 'required|min:8',
            
        ]);

         if($validatedData->fails()){
            return response()->json([
                'validation_error'=>$validatedData->messages(),
            ]);
        }
        else{

             // Check if the admin exists with the provided email or phone
            $existingAdmin = Admin::where('email', $request->email)
                             ->orWhere('phone', $request->phone)
                             ->first();

            if ($existingAdmin) {
                return response()->json([
                    'status' => 401,
                    'message' => 'Admin already exists.',
                ]);
            }
        
          else{  
        // Create new admin
        $admin = Admin::create([
            'name' => $request->name,
            'email'=> $request->email,
            'password'=>Hash::make($request->password),
            'phone'=> $request->phone,   
            'role_id'=>$request->role_id,    
         ]);

         if ($admin) {
            event(new VerifyRegisterAdmin($admin));
        }

         $token = $admin->createToken($admin->email.'_Token')->plainTextToken;
        // Return success response
        return response()->json([
            'status' => 200, 
            'user' => $admin->name,
            'role'=>$admin->role_id,
            'id' =>$admin->id,
            'token'=>$token,
            'message'=>'Registered Successfully',
        ]);
    }
  }
} 

 public function edit($id){
  
    
    $admin = Admin::find($id);

    
    if($admin){
        return response()->json([
            'status' => 200, 
            'admin' =>$admin
        ]);
    }

    else{
        return response()->json([
            'status' => 404, 
            'message' =>'No Admin Id Found'
        ]);
    }
     
    }

public function updateadminprofile(Request $request, $id)
{
    try{
    $validatedData = Validator::make($request->all(), [
        'name' => 'required|regex:/^[\p{Arabic}]+\s[\p{Arabic}]+$/u',
        'email' => 'required|email|unique:admins,email,'.$id.'|unique:seller_men|unique:delivery_men',
        'phone' => 'required|regex:/^\d{10}$/|unique:stores|unique:seller_men|unique:admins,phone,'.$id.',|unique:delivery_men',
    ]);

    if ($validatedData->fails()) {
        return response()->json([
            'status' => 401,
            'message' => $validatedData->errors()->first(),
        ]);
    }
     
        $admin = Admin::find($id);

        if (!$admin) {
            return response()->json([
                'status' => 404,
                'message' => 'Admin not found.',
            ]);
        }

        $admin->name = $request->name;
        $admin->email = $request->email;
        $admin->phone = $request->phone;
        $admin->save();


        $changes = $admin->getChanges();
    
        if (empty($changes)) {
            return response()->json(['message' => 'No modification has been made'], 200);
        }
    

        return response()->json([
            'status' => 200,
            'message' => 'Admin updated successfully.',
        ]);
  
     
    }catch (ValidationException $e) {
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

public function adminchangePassword(Request $request, $id)
{
    try {
        $admin = Admin::find($id);

        // Check if seller exists
        if (!$admin) {
            return response()->json(['message' => 'Admin not found','status' => 404 ,]);
        }

        // Check if current password matches
        if (!Hash::check($request->currentPassword, $admin->password)) {
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
        $admin->password = Hash::make($request->newPassword);
        $admin->save();

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





   public function Logout($id){
    $admin = Admin::find($id);
  
   $admin->tokens()->delete();
    
    return response()->json([
        'status' => 200,
        'message' => 'Logged Out Successfully',
    ]);
}
  


public function acceptNotification(Request $request)
    {
        $notification = Notification::find($request->notification_id);
        
        if ($notification) {
            // تحقق من نوع الإشعار واتبع المنطق المناسب
            $data = $notification->data;
            $isAccepted = false;
            $message = '';

            switch ($notification->type) {
                case 'category':
                    // تحقق من معايير الفئة ورفعها
                    if ($this->validateCategory($data)) {
                        $category = Category::create([
                            'name' => $data['category_name'],
                            'slug' => $data['category_slug'],
                            'description' => $data['category_description'],
                            'store_id' => $data['store_id'],
                        ]);
                        $isAccepted = true;
                        $message = 'Category accepted and created';
                    } else {
                        $message = 'Category does not meet the required criteria';
                    }
                    break;
                case 'product':
                    // تحقق من معايير المنتج ورفعها
                    if ($this->validateProduct($data)) {
                        $product = Product::create([
                            'name' => $data['product_name'],
                            'description' => $data['product_description'],
                            'price' => $data['product_price'],
                            'store_id' => $data['store_id'],
                        ]);
                        // رفع الصور إذا كانت موجودة
                        if (isset($data['product_images'])) {
                            $images = is_array($data['product_images']) ? $data['product_images'] : explode(',', $data['product_images']);
                            foreach ($images as $image) {
                                $product->images()->create(['path' => $image]);
                            }
                        }
                        $isAccepted = true;
                        $message = 'Product accepted and created';
                    } else {
                        $message = 'Product does not meet the required criteria';
                    }
                    break;
                case 'branches':
                    // تحقق من معايير الفروع ورفعها
                    if ($this->validateBranch($data)) {
                        $branch = Branch::create([
                            'name' => $data['branch_name'],
                            'store_id' => $data['store_id'],
                        ]);
                        $isAccepted = true;
                        $message = 'Branch accepted and created';
                    } else {
                        $message = 'Branch does not meet the required criteria';
                    }
                    break;
                default:
                    $message = 'Unknown notification type';
            }

            if ($isAccepted) {
                $notification->status = 'accepted';
                $notification->save();
                return response()->json(['status' => 200, 'message' => $message]);
            } else {
                return response()->json(['status' => 400, 'message' => $message]);
            }
        }

        return response()->json(['status' => 404, 'message' => 'Notification not found']);
    }

    public function rejectNotification(Request $request)
    {
        $notification = Notification::find($request->notification_id);
        if ($notification) {
            $notification->status = 'rejected';
            $notification->save();
            return response()->json(['status' => 200, 'message' => 'Notification rejected']);
        }
        return response()->json(['status' => 404, 'message' => 'Notification not found']);
    }



    
}
