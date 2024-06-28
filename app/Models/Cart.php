<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;


    protected $fillable = [
        'total_price',
        'customer_id',
        'user_note',
    ];

    public function customer(){
        return $this->belongsTo(Customer::class);
     }

     public function cartitem(){
        return $this->hasMany(CartItem::class);
     }

     public function order(){
        return $this->hasMany(Order::class);
     }



   
}
