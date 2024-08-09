<?php

namespace App\Http\Controllers;

use App\Mail\VerificationCodeMailCustomer;
use App\Models\Customer;
use GuzzleHttp\Promise\Create;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
   public function getCustomer()
    {
        $customer = Auth::guard('api_customer')->user();
        if ($customer) {
            return response()->json([
                'status' => 200,
                'customer' => $customer,
                'message' => 'User data fetched successfully',
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => 'User not found',
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

    public function login(Request $request)
{
    try {
        // Validate the request
        $validatedData = Validator::make($request->all(), [
            'email' => 'required|email',
        ], [
            'email.required' => 'الرجاء إدخال عنوان بريد إلكتروني صالح',
        ]);

        if ($validatedData->fails()) {
            return response()->json([
                'status' => 401,
                'message' => $validatedData->errors()->first(),
            ]);
        }

        // Check if the customer exists
        $customer = Customer::where('email', $request->email)->first();

        if (!$customer) {
            // If customer does not exist, create a new one
            $customer = Customer::create(['email' => $request->email]);
        }

        // Generate token
        $token = $customer->createToken($customer->email . '_Token')->plainTextToken;

        // Check if customer email is verified
        if (!$customer->email_verified_at) {
            // Generate verification code
            $verificationCode = rand(100000, 999999);

            // Send verification email
            Mail::to($request->email)->send(new VerificationCodeMailCustomer($verificationCode));

            // Update or create customer record with verification code
            $customer->update([
                'verification_code' => $verificationCode,
            ]);

            return response()->json([
                'status' => 'verification_needed',
                'message' => 'Login successful, verification needed',
                'verification_code' => $verificationCode,
                'customerToken' => $token,
                'customer_id' =>$customer->id
            ]);
        }

        // If email is verified, return success
        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'customerToken' => $token,
            'customer_id' =>$customer->id,
        ]);

    } catch (\Exception $e) {
        // Handle internal server error (500)
        return response()->json(['message' => 'حدث خطأ ما. الرجاء المحاولة مرة أخرى.'], 500);
    }
}

public function logout(Request $request)
{
    // الحصول على العميل الحالي
    $customer = $request->user(); // تأكد من أنك تستخدم Sanctum لحماية هذا المسار

    if ($customer) {
        // حذف جميع التوكين الخاصة بالعميل
        $deleted = DB::table('personal_access_tokens')->where('tokenable_id', $customer->id)->where('tokenable_type', 'App\\Models\\Customer')->delete();

        if ($deleted) {
            return response()->json(['status' => 200, 'message' => 'تم تسجيل الخروج بنجاح.']);
        } else {
            return response()->json(['status' => 500, 'message' => 'فشل في حذف التوكين.']);
        }
    } else {
        return response()->json(['status' => 400, 'message' => 'لم يتم العثور على العميل.']);
    }
}


  
        
    
    
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function completeRegistration(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|regex:/^\d{10}$/|unique:stores|unique:seller_men|unique:admins|unique:delivery_men|unique:customers',
            'name' => 'required|string',
            'city' => 'required|string',
            'gender' => 'required|string',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        // Find the customer by email
        $customer = Customer::where('email', $request->email)->first();
    
        if (!$customer) {
            return response()->json(['error' => 'Customer not found' ,'email' => $request->email], 404);
        }
    
        // Update customer information
        $customer->update([
            'name' => $request->name,
            'city' => $request->city,
            'gender' => $request->gender,
            'phone' => $request->phone,
        ]);
    
        return response()->json([
            'message' => 'Registration complete',
            'name' => $request->name,
            'city' => $request->city,
            'gender' => $request->gender,
            'phone' => $request->phone,
        ]);
    }
    


    public function verifyEmail(Request $request)
{
    $customer = Customer::where('email', $request->email)->first();

    if (!$customer) {
        return response()->json(['message' => 'Customer not found'], 404);
    }
    // Check if the verification code matches the one stored in the database
    if ($customer->verification_code == $request->verification_code) {
        // Update the email_verified_at field
        $customer->email_verified_at = now();
        // Clear the verification code
        $customer->verification_code = null;
        // Save the changes
        $customer->save();

        // Return success response
        return response()->json(['message' => 'Email verified successfully', 'customer' => $customer]);
    } else {
        // Return error response if verification code doesn't match
        return response()->json(['message' => 'Invalid verification code'], 400);
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
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
