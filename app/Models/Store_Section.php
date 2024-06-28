<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store_Section extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'section_id',
        'created_by',
    ];

 
    protected $with = ['create_section_to_store_admin', 'section', 'store'];

    public function create_section_to_store_admin()
     {
         return $this->belongsTo(Admin::class,"created_by" ,"id");
      } 

     public function section()
      {
            return $this->belongsTo(Section::class, 'section_id', 'id');
        }

    public function store()
     {
            return $this->belongsTo(Store::class, 'store_id', 'id');
        }



}

