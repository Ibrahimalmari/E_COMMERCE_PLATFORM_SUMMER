<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'additional_info',
        'invoice_amount',
        'order_status',
        'pay_way',
        'tax',
        'tip',
        'cart_id',
        'customer_id',
        'store_id',
    ];


    public function customer(){
        return $this->belongsTo(Customer::class);
     }
     public function store(){
        return $this->belongsTo(Store::class);
     }

     public function cart(){
        return $this->belongsTo(Cart::class);
     }

}
