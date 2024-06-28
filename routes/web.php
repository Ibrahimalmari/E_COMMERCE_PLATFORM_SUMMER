<?php

use App\Http\Controllers\SellerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

// Route::get('/verifyaccount/{id}', [SellerController::class, 'verifyAccount']);
// Route::get('/forgetpassword', [SellerController::class, 'sendPasswordResetEmail'])->name("email.password");
//  Route::post('/resetpassword/{token}', [SellerController::class, 'newPassword'])->name("password.update");




require __DIR__.'/auth.php';
