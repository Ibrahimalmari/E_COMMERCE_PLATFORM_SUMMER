<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
     // وظيفة لإضافة معاملة لعامل التوصيل
     public function storeDeliveryTransaction(Request $request)
     {
         $transaction = new Transaction();
         $transaction->delivery_worker_id = $request->delivery_worker_id;
         $transaction->order_id = $request->order_id;
         $transaction->total_amount = $request->total_amount;
         $transaction->delivery_fee = $request->delivery_fee;
         $transaction->amount_paid = $request->amount_paid;
         $transaction->transaction_type = 'delivery';
         $transaction->save();
 
         return response()->json(['message' => 'Delivery transaction created successfully']);
     }
 
     // وظيفة لإضافة معاملة لصاحب المتجر
     public function storeStoreTransaction(Request $request)
     {
         $transaction = new Transaction();
         $transaction->store_id = $request->store_id;
         $transaction->order_id = $request->order_id;
         $transaction->total_amount = $request->total_amount;
         $transaction->delivery_fee = $request->delivery_fee;
         $transaction->amount_paid = $request->amount_paid;
         $transaction->transaction_type = 'store';
         $transaction->save();
 
         return response()->json(['message' => 'Store transaction created successfully']);
     }
 
     public function getDeliveryTransactions($deliveryWorkerId)
     {
         // جلب المعاملات مع بيانات الطلبات المرتبطة بها
         $transactions = Transaction::with('order:id,order_numbers') // استرجع order_numbers فقط
             ->where('delivery_worker_id', $deliveryWorkerId)
             ->where('transaction_type', 'delivery')
             ->get();
     
         // تحويل البيانات لتضمين order_numbers
         $result = $transactions->map(function ($transaction) {
             return [
                 'id' => $transaction->id,
                 'store_id' => $transaction->store_id,
                 'delivery_worker_id' => $transaction->delivery_worker_id,
                 'order_id' => $transaction->order_id,
                 'order_numbers' => $transaction->order->order_numbers, // بيانات الطلب المرتبطة
                 'total_amount' => $transaction->total_amount,
                 'company_percentage' => $transaction->company_percentage,
                 'amount_paid' => $transaction->amount_paid,
                 'delivery_fee' => $transaction->delivery_fee,
                 'transaction_type' => $transaction->transaction_type,
                 'transaction_date' => $transaction->created_at,
                 'created_at' => $transaction->created_at,
                 'updated_at' => $transaction->updated_at,
             ];
         });
     
         return response()->json($result);
     }
     
 
     // وظيفة لعرض معاملات صاحب المتجر
     public function getStoreTransactions($storeId)
     {
         $transactions = Transaction::where('store_id', $storeId)
                                     ->where('transaction_type', 'store')
                                     ->get();
 
         return response()->json($transactions);
     }
}
