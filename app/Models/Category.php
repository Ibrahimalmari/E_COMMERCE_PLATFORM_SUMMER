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

  protected $with = ['store']; 


    public function product(){
      return  $this->hasMany(Product::class);
    }

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class , 'store_id' ,'id');
    }
    

}
