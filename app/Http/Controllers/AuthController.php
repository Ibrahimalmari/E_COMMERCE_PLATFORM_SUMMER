<?php

// app/Http/Controllers/AuthController.php

namespace App\Http\Controllers;

use App\Models\SellerMan;
use App\Models\User;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // Validate request data
        $validatedData = $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:seller_men',
            'address' => 'required',
            'country' => 'required',
            'gender' => 'required',
            'personal_identity_photo' => 'required',
            'birthday' => 'required|date',
            'personal_number' => 'required',
            'password' => 'required|min:8',
        ]);

        // Create new user
        $user = SellerMan::create($validatedData);

        // Return success response
        return response()->json(['message' => 'Registration successful', 'user' => $user]);
    }
}
