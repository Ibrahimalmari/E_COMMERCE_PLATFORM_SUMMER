<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'created_by',

    ];
    protected $with = ['create_section_admin']; 


    public function store(){
        return  $this->belongsToMany(store::class , 'store_sections');
     } 

     public function create_section_admin()
     {
         return $this->belongsTo(Admin::class,"created_by" ,"id");
     } 

}
