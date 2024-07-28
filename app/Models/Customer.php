<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable
{
    use HasFactory ,HasApiTokens , Notifiable ;

    protected $guard ='customer';
    protected $fillable = [
        'name',
        'email',
        'city',
        'gender',
        'phone',
        'DateOfBirth',
        'verification_code'
    ];

  
 
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

     protected $hidden = [
        'password',
        'remember_token',
    ];

    public function order(){
        return $this->hasMany(Order::class);
     }

     public function address(){
        return $this->hasOne(Address::class);
     }
     

   

}
