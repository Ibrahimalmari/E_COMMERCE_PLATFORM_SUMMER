<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'description',
        'phone',
        'type',
        'coverPhoto',
        'openTime',
        'closeTime',
        'seller_id',
        'created_by',
        'latitude',
        'longitude',
    ];

   

    protected $with = ['seller'];
    public function seller(){
       return  $this->belongsTo(SellerMan::class , "seller_id" ,"id");
    }

    public function product(){
       return   $this->hasMany(Product::class);
    }
    public function order(){
        return $this->hasMany(Order::class);
     }

    public function section(){
        return  $this->belongsToMany(Section::class ,'store_sections');
     } 


     public function create_store_admin()  
     {
        return $this->belongsTo(Admin::class);
     }   

     public function carts()
     {
         return $this->hasMany(Cart::class);
     }

     // علاقة الخصم بالمتجر
    public function discount()
    {
        return $this->hasMany(Discount::class);
    }

    public function category()
    {
        return $this->hasMany(Category::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

}
