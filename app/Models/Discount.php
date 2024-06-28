<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{

    use HasFactory;

    protected $fillable = [
        'name',
        'store_id',
        'code',
        'value',
        'percentage',
        'conditions',
        'start_date',
        'end_date',
    ];


  protected  $with = ['store'];
    // علاقة الخصم بالمتجر
    public function store()
    {
        return $this->belongsTo(Store::class ,"store_id" , "id" );
    }
}
