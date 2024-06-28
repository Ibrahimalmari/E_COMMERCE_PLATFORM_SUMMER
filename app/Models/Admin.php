<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable
{
    use HasFactory ,HasApiTokens , Notifiable ;
   
    protected $guard ='admin';
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
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

     
      public function create_store()    //كل مشرف يمكن انشاء اكثر من متجر
      {
          return $this->hasMany(Store::class);
      }   
      
      public function create_section()    //كل مشرف يمكن انشاء اكثر من قسم حيث يكون القسم لمتجر 
      {
          return $this->hasMany(Section::class);
      }
      public function create_section_to_store()    //كل مشرف يمكن تضمين اكثر من قسم حيث يكون القسم لمتجر 
      {
          return $this->hasMany(Store_Section::class);
      }


}
