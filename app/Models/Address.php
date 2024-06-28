<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;
    protected $fillable = [
        'longitude',
        'latitude',
        'area',
        'street',
        'nearBy',
        'additionalDetails',
        'floor',
        'customer_id',
    ];

     public function customer(){
        return $this->belongsTo(Customer::class);
     }

}
