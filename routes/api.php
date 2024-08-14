<?php

use App\Http\Controllers\AddressController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\DeliveryMenOrdersController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\Store_Section_Controller;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\TransactionController;
use App\Models\DeliveryMenOrder;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// for chat
Route::get('/user-stats', [UserController::class, 'getUserStats']);


// Route::middleware(['auth:sanctum'])->group(function () {
//        Route::get("/checkingAuthenticated" , function () {
//         return response()->json(["message" => "You are in " , "status" => 200] , 200);
//        });
//     });       


    Route::controller(RoleController::class)->group(function () {    
        Route::get('/role','index');
    });         

    Route::controller(CategoryController::class)->group(function () {        
        Route::get('/allcategory','index_admin');
        Route::get('/displaycategory/{id}','index_seller');
        Route::post('/CategoryAdd/{id}','store');
        Route::get('/category/{id}','edit');
        Route::put('/updatecategory/{id}','update');
        Route::delete('/deletecategory/{id}','destroy');

    });         


Route::controller(StoreController::class)->group(function () {     
    Route::get('/allstore','index_admin');
    Route::get('/displaystore/{id}','index_seller');
    Route::get('/DisplayStoreToCustomer/{id}','DisplayStoreToCustomer');
    Route::get('/store/getStoreDetails/{store_id}','getStoreDetails');
    Route::get('/store/getStoreAddress/{store_id}','getStoreAddress');
    Route::post('/StoreAdd/{id}','store');
    Route::get('/store/{id}','edit');
    Route::put('/updatestore/{id}','update');
    Route::delete('/deletestore/{id}','destroy');
});     

Route::controller(ProductController::class)->group(function () {  
    Route::get('/allproduct','index_admin');
    Route::get('/displayproduct/{id}','index_seller'); 
    Route::get('/displaydetailsProduct/{id}', 'displaydetailsProduct');
    Route::post('/ProductAdd/{id}','store');
    Route::get('/product/{id}','edit');
    Route::put('/updateproduct/{id}','update');
    Route::delete('/deleteproduct/{id}','destroy');
});     

Route::controller(SectionController::class)->group(function () {   
    Route::get('/section','index_admin');
    Route::get('/section/{id}','edit'); 
    Route::post('/SectionAdd/{id}','store');
    Route::put('/updatesection/{id}','update');
    Route::delete('/deletesection/{id}','destroy');

});    
Route::controller(BranchController::class)->group(function () {   
    Route::get('/allbranch','index_admin');
    Route::get('/displaybranch/{id}','index_seller'); 
    Route::post('/BranchAdd/{id}','store');
    Route::get('/branch/{id}','edit');
    Route::put('/updatebranch/{id}','update');
    Route::delete('/deletebranch/{id}','destroy');

});  
Route::controller(Store_Section_Controller::class)->group(function () {   
    Route::post('/SectionAddToStore/{id}','store');
    Route::get('/SectionToStore','index');
    Route::get('/SectionToStore/{id}','edit'); 
    Route::put('/UpdateSectionToStore/{id}','update');
    Route::delete('/DeleteSectionToStore/{id}','destroy');
    Route::get('/sections/{type}',  'getSectionsForStore');

});      



Route::controller(SellerController::class)->group(function () {
        Route::get('/seller', 'index');
        Route::get('/seller/{id}','edit');
        Route::put('/updateseller/{id}','update');
        Route::put('/updatesellerprofile/{id}','updatesellerprofile');
        Route::post('/changepassword/{id}','sellerchangePassword');
        Route::delete('/deleteseller/{id}','destroy');
        Route::post('/sellerlogin','Login');
        Route::post('/sellerRegister','Register');
        Route::post('/forgetpassword','sendPasswordResetEmail');
        Route::post('/resetpassword','updatePassword');
        Route::get('/verifyaccountseller/{id}', 'verifyAccountseller');
        Route::get('/seller/notifications/{id}',  'getSellerNotifications');

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/sellerlogout/{id}', 'Logout');
       });
   
});

Route::controller(AdminController::class)->group(function () {
    Route::post('/adminlogin','Login');
    Route::post('/admin','Register');
    Route::get('/admin/{id}','edit');
    Route::put('/updateadminprofile/{id}','updateadminprofile');
    Route::post('/changepassword/{id}','adminchangePassword');
    Route::post('/ForgetPassword','sendPasswordResetEmail');
    Route::post('/ResetPassword','updatePassword');
    Route::get('/verifyaccountadmin/{id}', 'verifyAccountadmin');
    Route::get('/Notification', 'ShowNotifications');
 Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/adminlogout/{id}', 'Logout');
    });

});

Route::controller(NotificationController::class)->group(function () {
    Route::post('/notification/accept/{notification_id}/{notifiable_id}', 'acceptNotification');
    Route::post('/notification/reject/', 'rejectNotification');
    Route::post('/notification/reject-all',  'rejectAllNotifications');
    Route::get('/seller/notifications', 'getSellerNotifications');
    Route::post('/notification/markAllAsRead', 'markAllAsRead');


});

Route::controller(DeliveryController::class)->group(function () {      
    Route::get('/delivery','index');
    Route::post('/deliverylogin','Login');
    Route::post('/deliveryRegister','Register');
    Route::get('/delivery/{id}','edit');
    Route::put('/updatedelivery/{id}','update');
    Route::delete('/deletedelivery/{id}','destroy');
    Route::middleware('auth:api_delivery')->get('/deliveryWokerForEnsure','deliveryWokerForEnsure');
    Route::middleware('auth:sanctum')->post('/LogoutDelivery','deliverylogout');
    Route::post('/delivery-men/status/{id}', 'updateStatus');
    Route::get('/get-connected-workers',  'getConnectedWorkers');
    Route::get('/check-active-order/{deliveryId}',  'checkActiveOrder');

});


Route::controller(DiscountController::class)->group(function () {      
    Route::get('/discount','index');
    Route::post('/discountstore','store');
    Route::get('/discount/{id}','edit');
    Route::put('/updatediscount/{id}','update');
    Route::delete('/deletediscount/{id}','destroy');
    Route::post('/apply-discount', 'applyDiscount');

});

Route::controller(CustomerController::class)->group(function () {      
    Route::post('/StoreUser','store');
    Route::post('/LoginUser','login');
    Route::post('/completeRegistration','completeRegistration');
    Route::post('/verifyemail','verifyEmail');
    Route::middleware('auth:api_customer')->get('/customer','getCustomer');
    Route::middleware('auth:sanctum')->post('/logout',  'logout');
});


Route::controller(CartController::class)->group(function () {      

    Route::post('/cart/add','addToCart');
    Route::post('/CartAddDuringReOrder','addToCartduringReOrder');
    Route::get('/checkCart/{customerId}/{storeId}','checkCart');
    Route::delete('/removeCart/{customerId}/{storeId}', 'removeCart');
    Route::get('/customer/cart/{customerId}/{storeId}',  'DisplayProductInCartForCustomer');
    Route::post('/cart/update-quantity/{id}',  'updateQuantity');
    Route::delete('/cart/remove-item/{id}',  'removeItem');
    Route::get('/savedCarts',  'getSavedCarts');  ///جلب كل  السلات لمستخدم من
    Route::get('/cart/{cartId}',  'getCartDetails'); // جلب تفاصيل السلة معينة 

});


Route::controller(AddressController::class)->group(function () {      

    Route::post('/addresses', 'store');
    Route::get('/addresses/{id}','show');

});

Route::controller(OrderController::class)->group(function () {      

    Route::post('/orders','store');
    Route::get('/orders/{id}','show');
    Route::post('/orders/status/{id}',  'updateStatus'); // Route لتحديث حالة الطلب
    Route::post('/accept-order-creation',  'acceptOrderCreation');
    Route::post('/reject-order-creation',  'rejectOrderCreation');
    Route::get('/ForShowMyOrderToCustomer','ForShowMyOrderToCustomer');
    Route::get('/OrderDetails/{orderId}', 'getOrderDetails');
    Route::post('/accept-order-for-delivery', 'acceptOrderfordelivery');
    Route::post('/reject-order-for-delivery', 'rejectOrderForDelivery');
    Route::post('/cancel-order',  'cancelOrder'); //for delivery worker
    Route::get('/orders/pending/delivery', 'getPendingOrders');
    Route::get('orders/getOrderStatus/{id}',  'getOrderStatus');
    Route::post('orders/rate{id}','rateOrder');

    });

Route::controller(DeliveryMenOrdersController::class)->group(function () {      
    Route::get('/delivery-men/orders/{deliveryMenId}','getOrdersByDeliveryMan');
    Route::get('delivery-men/rates/{id}','showRates');

    });

Route::controller(TransactionController::class)->group(function () {      

    // معاملات عامل التوصيل
    Route::post('transactions/delivery','storeDeliveryTransaction');
    Route::get('transactions/delivery/{deliveryWorkerId}','getDeliveryTransactions');

    // معاملات صاحب المتجر
    Route::post('transactions/store', 'storeStoreTransaction');
    Route::get('transactions/store/{storeId}', 'getStoreTransactions');

    });
Route::controller(FavoriteController::class)->group(function () {      
    Route::post('favorite/getFavorites','getFavorites');
    Route::post('favorite/add', 'addFavorite');
    Route::post('favorite/remove','removeFavorite');
    Route::get('favorite/check/{customerId}/{storeId}','check');

    });
