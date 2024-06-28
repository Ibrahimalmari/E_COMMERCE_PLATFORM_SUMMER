<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class SellerMan extends Authenticatable
{
    use HasFactory ,HasApiTokens , Notifiable;

    protected $guard ='seller';
    protected $fillable = [
        'name',
        'email',
        'password',
        'address',
        'gender',
        'phone',
        'PhotoOfPersonalID',
        'DateOfBirth',
        'NationalNumber',
        'role_id',
    ];


    
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function role(){
        return  $this->belongsTo(Role::class);
      }

      public function store(){
        return   $this->hasMany(store::class ,'seller_id' );
    }

    public function create_category()    //كل بائع يمكن انشاء اكثر من فئة
    {
        return $this->hasMany(Category::class);
    }

    
    public function create_branch()    //كل بائع يمكن انشاء اكثر من فرع
    {
        return $this->hasMany(Branch::class);
    }


    public function create_product()    //كل بائع يمكن انشاء اكثر من منتج
    {
        return $this->hasMany(Product::class);
    }   
    


}



