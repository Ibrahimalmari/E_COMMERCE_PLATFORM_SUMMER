<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category_id',
    ];

    protected $with = ['category']; 

    public function category(){
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
    
     public function categoryplus(){
        return  $this->belongsTo(Category::class);
     }

     public function create_branch_seller()
     {
         return $this->belongsTo(SellerMan::class);
     }
}
