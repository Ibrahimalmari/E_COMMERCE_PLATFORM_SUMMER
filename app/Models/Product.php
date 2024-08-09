<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'images',
        'quantity',
        'category_id',
        'branch_id',
        'store_id',
    ];

 protected $with = ['category', 'branch', 'store']; 

 public function store(){
    return  $this->belongsTo(store::class ,"store_id" , "id");
 } 

public function category(){
   return  $this->belongsTo(Category::class,"category_id" , "id");
}

public function branch()
{
    return $this->belongsTo(Branch::class ,"branch_id" , "id");
}


public function cartItems()
{
    return $this->hasMany(CartItem::class, 'product_id');
}

 public function create_product_seller()
 {
     return $this->belongsTo(SellerMan::class);
 }


};
