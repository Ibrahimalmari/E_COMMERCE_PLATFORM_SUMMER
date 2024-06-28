<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;


    protected $fillable = [
      'name',
      'slug',
      'description',
      'store_id',
  ];

    
    public function product(){
      return  $this->hasMany(Product::class);
    }

    public function branch(){
      return $this->hasMany(Branch::class, 'category_id', 'id');
  }

    protected  $with = ['store'];

    public function store()
    {
        return $this->belongsTo(Store::class , 'store_id' ,'id');
    }
    

}
